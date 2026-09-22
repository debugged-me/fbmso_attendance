import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:sqflite/sqflite.dart';

import 'local_db.dart';
import 'outbox_service.dart';

/// What the device decided about a scan before the server saw it.
class LocalScanDecision {
  const LocalScanDecision({
    required this.clientScanId,
    required this.mode,
    required this.direction,
    required this.session,
    required this.scannedAt,
    this.previousAt,
  });

  final String clientScanId;

  /// checked_in | checked_out | duplicate
  final String mode;

  /// The direction sent to the server. Resolved here rather than left as
  /// 'auto' so a queued pair replays deterministically in FIFO order.
  final String direction;
  final String session;
  final DateTime scannedAt;

  /// When this student last scanned, for the duplicate warning.
  final DateTime? previousAt;

  bool get isDuplicate => mode == 'duplicate';
}

/// The device's own record of every scan, and the local rules that mirror the
/// server's check-in/check-out toggle.
///
/// Two jobs: let the operator see a duplicate immediately instead of hours
/// later at sync, and keep the server's eventual verdict attached to the scan
/// it belongs to so a rejection is never lost.
class ScanLedgerService {
  static const _table = 'scan_ledger';

  /// Mirrors AUTO_OUT_DEBOUNCE_SEC in Activity_attendance_model — a camera
  /// reading the same QR twice must not create a 2-second in/out pair.
  static const _autoOutDebounce = Duration(seconds: 10);
  static const _repeatInDebounce = Duration(seconds: 5);

  /// Hook the outbox so a queued scan's server result lands back on its row.
  static void register() {
    OutboxService.registerResultHandler('scanner_consume', reconcile);
  }

  // ─── Decide + record ───────────────────────────────────────────────────

  /// Work out what this scan means locally, write it to the ledger, and hand
  /// back the decision. [clientScanId] is the natural key the server dedups on.
  static Future<LocalScanDecision> record({
    required int activityId,
    required String studentNumber,
    required String clientScanId,
    required Map<String, dynamic> sessions,
    String qrHash = '',
    DateTime? at,
  }) async {
    final now = at ?? DateTime.now();
    final localDate = _dateKey(now);
    final session = classifySession(sessions, now);

    final db = await LocalDb.instance();
    final decision = await _decide(
      db: db,
      activityId: activityId,
      studentNumber: studentNumber,
      localDate: localDate,
      session: session,
      now: now,
      clientScanId: clientScanId,
    );

    await db.insert(_table, {
      'client_scan_id': clientScanId,
      'activity_id': activityId,
      'student_number': studentNumber,
      'qr_hash': qrHash,
      'direction': decision.direction,
      'scanned_at_utc': now.toUtc().millisecondsSinceEpoch,
      'tz_offset_min': now.timeZoneOffset.inMinutes,
      'local_date': localDate,
      'local_session': session,
      'local_mode': decision.mode,
      'synced': 0,
    }, conflictAlgorithm: ConflictAlgorithm.ignore);

    return decision;
  }

  /// Port of Activity_attendance_model::consume_token's auto-toggle so the
  /// offline answer matches what the server will conclude at sync.
  static Future<LocalScanDecision> _decide({
    required Database db,
    required int activityId,
    required String studentNumber,
    required String localDate,
    required String session,
    required DateTime now,
    required String clientScanId,
  }) async {
    final rows = await db.query(
      _table,
      where: 'activity_id = ? AND student_number = ? AND local_date = ?'
          " AND local_mode != 'duplicate'",
      whereArgs: [activityId, studentNumber, localDate],
      orderBy: 'scanned_at_utc DESC',
    );

    DateTime? lastAt;
    if (rows.isNotEmpty) {
      lastAt = DateTime.fromMillisecondsSinceEpoch(
          rows.first['scanned_at_utc'] as int,
          isUtc: true);
    }

    LocalScanDecision build(String mode, String direction) => LocalScanDecision(
          clientScanId: clientScanId,
          mode: mode,
          direction: direction,
          session: session,
          scannedAt: now,
          previousAt: lastAt?.toLocal(),
        );

    // An open check-in for the day toggles to a check-out.
    final open = rows.where((r) => r['local_mode'] == 'checked_in').toList();
    final closed = rows.where((r) => r['local_mode'] == 'checked_out').toList();
    final hasOpen = open.isNotEmpty &&
        (closed.isEmpty ||
            (open.first['scanned_at_utc'] as int) >
                (closed.first['scanned_at_utc'] as int));

    if (hasOpen) {
      final inAt = DateTime.fromMillisecondsSinceEpoch(
          open.first['scanned_at_utc'] as int,
          isUtc: true);
      if (now.toUtc().difference(inAt) <= _autoOutDebounce) {
        return build('duplicate', 'in');
      }
      return build('checked_out', 'out');
    }

    // Already completed this session.
    final doneThisSession = closed.any((r) => r['local_session'] == session);
    if (doneThisSession) return build('duplicate', 'in');

    // A second read of the same QR moments after checking in.
    final recentIn = open.where((r) => r['local_session'] == session).toList();
    if (recentIn.isNotEmpty) {
      final inAt = DateTime.fromMillisecondsSinceEpoch(
          recentIn.first['scanned_at_utc'] as int,
          isUtc: true);
      if (now.toUtc().difference(inAt) <= _repeatInDebounce) {
        return build('duplicate', 'in');
      }
    }

    return build('checked_in', 'in');
  }

  // ─── Session classification ────────────────────────────────────────────

  /// Port of Activity_attendance_model::classify_session. [sessions] is the
  /// activity's own am/pm/eve windows as shipped in the roster manifest.
  static String classifySession(Map<String, dynamic> sessions, DateTime at) {
    final defined = <String, ({int? inMin, int? outMin})>{};

    sessions.forEach((key, value) {
      if (value is! Map) return;
      final inMin = _toMinutes(value['in']?.toString());
      final outMin = _toMinutes(value['out']?.toString());
      if (inMin == null && outMin == null) return;
      defined[key] = (inMin: inMin, outMin: outMin);
    });

    if (defined.isEmpty) {
      defined['am'] = (inMin: 8 * 60, outMin: 12 * 60);
      defined['pm'] = (inMin: 13 * 60, outMin: 18 * 60);
    }

    final nowMin = at.hour * 60 + at.minute;

    for (final entry in defined.entries) {
      final inOk = entry.value.inMin == null || nowMin >= entry.value.inMin!;
      final outOk = entry.value.outMin == null || nowMin < entry.value.outMin!;
      if (inOk && outOk) return entry.key;
    }

    // Outside every window: clamp to the nearest one, like the server does.
    final sorted = defined.entries.toList()
      ..sort((a, b) => (a.value.inMin ?? -1).compareTo(b.value.inMin ?? -1));

    for (final entry in sorted) {
      if (entry.value.inMin != null && nowMin < entry.value.inMin!) {
        return entry.key;
      }
    }
    return sorted.isNotEmpty ? sorted.last.key : 'am';
  }

  static int? _toMinutes(String? hhmm) {
    if (hhmm == null || hhmm.length < 4) return null;
    final parts = hhmm.split(':');
    if (parts.length < 2) return null;
    final h = int.tryParse(parts[0]);
    final m = int.tryParse(parts[1]);
    if (h == null || m == null) return null;
    return h * 60 + m;
  }

  // ─── Reconciliation ────────────────────────────────────────────────────

  /// Attach the server's verdict to the scan it belongs to. Called by the
  /// outbox once a queued scan finally gets a response.
  static Future<void> reconcile(
      String clientScanId, int status, Map<String, dynamic> body) async {
    if (kIsWeb) return;
    final db = await LocalDb.instance();
    await db.update(
      _table,
      {
        'synced': 1,
        'server_mode': (body['mode'] ?? '').toString(),
        'server_ok': body['ok'] == true ? 1 : 0,
        'server_message': (body['message'] ?? '').toString(),
        'reconciled_at': DateTime.now().millisecondsSinceEpoch,
      },
      where: 'client_scan_id = ?',
      whereArgs: [clientScanId],
    );
  }

  /// Mark a scan that went straight out over the network, so the local
  /// duplicate check stays accurate after the signal drops.
  static Future<void> markSynced(
      String clientScanId, Map<String, dynamic> body) =>
      reconcile(clientScanId, 200, body);

  // ─── Reporting ─────────────────────────────────────────────────────────

  /// Scans the server disagreed with — a rejection must never be lost in a
  /// toast the operator missed hours ago.
  static Future<List<Map<String, dynamic>>> rejected(int activityId) async {
    if (kIsWeb) return [];
    final db = await LocalDb.instance();
    return db.query(
      _table,
      where: 'activity_id = ? AND synced = 1 AND server_ok = 0',
      whereArgs: [activityId],
      orderBy: 'scanned_at_utc DESC',
    );
  }

  static Future<int> pendingCount(int activityId) async {
    if (kIsWeb) return 0;
    final db = await LocalDb.instance();
    final rows = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM $_table WHERE activity_id = ? AND synced = 0',
      [activityId],
    );
    return Sqflite.firstIntValue(rows) ?? 0;
  }

  static Future<void> clearAll() async {
    if (kIsWeb) return;
    final db = await LocalDb.instance();
    await db.delete(_table);
  }

  static String _dateKey(DateTime at) =>
      '${at.year.toString().padLeft(4, '0')}-'
      '${at.month.toString().padLeft(2, '0')}-'
      '${at.day.toString().padLeft(2, '0')}';
}
