import 'dart:async';
import 'dart:convert';
import 'dart:math';

import 'package:crypto/crypto.dart';
import 'package:flutter/foundation.dart' show kIsWeb, visibleForTesting;
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

  /// checked_in | checked_out | duplicate | unverified
  final String mode;

  /// The direction the device expects ('in' or 'out'), for the operator
  /// only. The server is sent 'auto' and decides: this phone knows only its
  /// own scans, and a student may check in and out at different phones.
  final String direction;
  final String session;
  final DateTime scannedAt;

  /// When this student last scanned, for the duplicate warning.
  final DateTime? previousAt;

  bool get isDuplicate => mode == 'duplicate';
}

/// The server's answer for one scan, once its upload got one.
class ScanVerdict {
  const ScanVerdict({
    required this.clientScanId,
    required this.ok,
    required this.mode,
    this.message,
    this.studentNumber,
    this.session,
    this.student,
  });

  final String clientScanId;
  final bool ok;
  final String mode;
  final String? message;
  final String? studentNumber;
  final String? session;
  final Map<String, dynamic>? student;
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

  static final _verdicts = StreamController<ScanVerdict>.broadcast();

  /// Server answers as uploads get them, so an open scanner can update the
  /// scan each one belongs to.
  static Stream<ScanVerdict> get verdicts => _verdicts.stream;

  /// Hook the outbox so a queued scan's server result lands back on its row.
  static void register() {
    OutboxService.registerResultHandler('scanner_consume', reconcile);
  }

  /// Local stand-in for a QR this phone's roster cannot name, so a repeat
  /// read of it can still be caught. Tokens are random 128-bit values, so
  /// the hash does not lead back to one.
  static String localHash(String qrToken) =>
      sha256.convert(utf8.encode('local:$qrToken')).toString();

  // ─── Decide + record ───────────────────────────────────────────────────

  /// Work out what this scan means locally, write it to the ledger, and hand
  /// back the decision. [clientScanId] is the natural key the server dedups on.
  ///
  /// [studentNumber] is empty when the QR is not on this phone's roster; the
  /// scan is then recorded as `unverified` and only [qrHash] (see
  /// [localHash]) catches a repeat read. Pass [txn] to write inside the
  /// caller's transaction.
  static Future<LocalScanDecision> record({
    required int activityId,
    required String studentNumber,
    required String clientScanId,
    required Map<String, dynamic> sessions,
    String qrHash = '',
    DateTime? at,
    DatabaseExecutor? txn,
  }) async {
    final now = at ?? DateTime.now();
    final localDate = _dateKey(now);
    final session = classifySession(sessions, now);
    final db = txn ?? await LocalDb.instance();

    final LocalScanDecision decision;
    if (studentNumber.isNotEmpty) {
      final history = await db.query(
        _table,
        where: 'activity_id = ? AND student_number = ? AND local_date = ?',
        whereArgs: [activityId, studentNumber, localDate],
        orderBy: 'scanned_at_utc DESC',
      );
      decision = decide(
        history: history,
        session: session,
        now: now,
        clientScanId: clientScanId,
      );
    } else {
      final history = qrHash.isEmpty
          ? const <Map<String, dynamic>>[]
          : await db.query(
              _table,
              where: "activity_id = ? AND student_number = '' AND qr_hash = ?"
                  ' AND local_date = ?',
              whereArgs: [activityId, qrHash, localDate],
              orderBy: 'scanned_at_utc DESC',
            );
      decision = decideUnverified(
        history: history,
        session: session,
        now: now,
        clientScanId: clientScanId,
      );
    }

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

  /// What an earlier scan finally counts as: the server's answer once it has
  /// one, this phone's guess until then. A scan the server refused, and one
  /// either side judged a repeat, count for nothing.
  @visibleForTesting
  static String effectiveMode(Map<String, dynamic> row) {
    if (((row['synced'] as int?) ?? 0) == 1) {
      if (((row['server_ok'] as int?) ?? 0) != 1) return 'rejected';
      final mode = (row['server_mode'] ?? '').toString();
      return mode == 'checked_in' || mode == 'checked_out' ? mode : 'duplicate';
    }
    return (row['local_mode'] ?? '').toString();
  }

  /// Port of Activity_attendance_model::consume_token's AUTO toggle over this
  /// phone's scans of the student today ([history], newest first), so the
  /// operator's answer matches what the server will conclude.
  ///
  /// Earlier scans count as the server saw them once it answered: a student
  /// already checked in at another phone is predicted as checking out here
  /// after the first upload, instead of this phone guessing wrong all day.
  @visibleForTesting
  static LocalScanDecision decide({
    required List<Map<String, dynamic>> history,
    required String session,
    required DateTime now,
    required String clientScanId,
  }) {
    final counted = [
      for (final r in history)
        if (effectiveMode(r) case final mode
            when mode == 'checked_in' || mode == 'checked_out')
          (
            mode: mode,
            session: (r['local_session'] ?? '').toString(),
            at: r['scanned_at_utc'] as int,
          ),
    ];

    final nowMs = now.toUtc().millisecondsSinceEpoch;
    final lastAt = counted.isEmpty
        ? null
        : DateTime.fromMillisecondsSinceEpoch(counted.first.at, isUtc: true)
            .toLocal();

    LocalScanDecision build(String mode, String direction) => LocalScanDecision(
          clientScanId: clientScanId,
          mode: mode,
          direction: direction,
          session: session,
          scannedAt: now,
          previousAt: lastAt,
        );

    // An open check-in for the day toggles to a check-out.
    final ins = counted.where((c) => c.mode == 'checked_in').toList();
    final outs = counted.where((c) => c.mode == 'checked_out').toList();
    final hasOpen =
        ins.isNotEmpty && (outs.isEmpty || ins.first.at > outs.first.at);

    if (hasOpen) {
      if (nowMs - ins.first.at <= _autoOutDebounce.inMilliseconds) {
        return build('duplicate', 'in');
      }
      return build('checked_out', 'out');
    }

    // Already completed this session.
    if (outs.any((c) => c.session == session)) return build('duplicate', 'in');

    // A second read of the same QR moments after checking in.
    final recentIn = ins.where((c) => c.session == session).toList();
    if (recentIn.isNotEmpty &&
        nowMs - recentIn.first.at <= _repeatInDebounce.inMilliseconds) {
      return build('duplicate', 'in');
    }

    return build('checked_in', 'in');
  }

  /// A QR this phone's roster cannot name: the server decides everything, so
  /// the only local call is catching the same code read twice in a row.
  @visibleForTesting
  static LocalScanDecision decideUnverified({
    required List<Map<String, dynamic>> history,
    required String session,
    required DateTime now,
    required String clientScanId,
  }) {
    final queued =
        history.where((r) => (r['local_mode'] ?? '') != 'duplicate').toList();
    final lastAt = queued.isEmpty ? null : queued.first['scanned_at_utc'] as int;
    final repeat = lastAt != null &&
        now.toUtc().millisecondsSinceEpoch - lastAt <=
            _autoOutDebounce.inMilliseconds;

    return LocalScanDecision(
      clientScanId: clientScanId,
      mode: repeat ? 'duplicate' : 'unverified',
      direction: 'in',
      session: session,
      scannedAt: now,
      previousAt: lastAt == null
          ? null
          : DateTime.fromMillisecondsSinceEpoch(lastAt, isUtc: true).toLocal(),
    );
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
    final ok = body['ok'] == true;
    final mode = (body['mode'] ?? '').toString();
    final message = (body['message'] ?? '').toString();
    final studentNumber = (body['student_number'] ?? '').toString();

    await db.update(
      _table,
      {
        'synced': 1,
        'server_mode': mode,
        'server_ok': ok ? 1 : 0,
        'server_message': message,
        'reconciled_at': DateTime.now().millisecondsSinceEpoch,
      },
      where: 'client_scan_id = ?',
      whereArgs: [clientScanId],
    );

    // A code this phone could not name: the server says whose it was, so the
    // next scan of that student here is predicted like any other.
    if (studentNumber.isNotEmpty) {
      await db.update(
        _table,
        {'student_number': studentNumber},
        where: "client_scan_id = ? AND student_number = ''",
        whereArgs: [clientScanId],
      );
    }

    final student = body['student'];
    _verdicts.add(ScanVerdict(
      clientScanId: clientScanId,
      ok: ok,
      mode: mode.isEmpty ? (ok ? 'checked_in' : 'err') : mode,
      message: message.isEmpty ? null : message,
      studentNumber: studentNumber.isEmpty ? null : studentNumber,
      session: (body['session'] ?? '').toString().isEmpty
          ? null
          : body['session'].toString(),
      student: student is Map ? student.cast<String, dynamic>() : null,
    ));
  }

  // ─── Reporting ─────────────────────────────────────────────────────────

  /// Who and when for queued or refused scans, keyed by client scan id, so
  /// the Sync report can name the student instead of "Attendance scan".
  static Future<
      Map<String, ({String studentNumber, String name, DateTime scannedAt})>>
      describe(Iterable<String> clientScanIds) async {
    final out =
        <String, ({String studentNumber, String name, DateTime scannedAt})>{};
    if (kIsWeb) return out;
    final ids = clientScanIds.where((id) => id.isNotEmpty).toSet().toList();
    if (ids.isEmpty) return out;

    final db = await LocalDb.instance();
    // SQLite caps bound variables at 999 per statement.
    for (var i = 0; i < ids.length; i += 500) {
      final chunk = ids.sublist(i, min(i + 500, ids.length));
      final rows = await db.rawQuery('''
        SELECT l.client_scan_id, l.student_number, l.scanned_at_utc,
               (SELECT r.name FROM roster_student r
                 WHERE r.activity_id = l.activity_id
                   AND r.student_number = l.student_number
                   AND l.student_number != ''
                 LIMIT 1) AS name
          FROM $_table l
         WHERE l.client_scan_id IN (${List.filled(chunk.length, '?').join(',')})
      ''', chunk);
      for (final r in rows) {
        out[r['client_scan_id'] as String] = (
          studentNumber: (r['student_number'] ?? '').toString(),
          name: (r['name'] ?? '').toString(),
          scannedAt: DateTime.fromMillisecondsSinceEpoch(
                  r['scanned_at_utc'] as int,
                  isUtc: true)
              .toLocal(),
        );
      }
    }
    return out;
  }

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
      "SELECT COUNT(*) AS c FROM $_table WHERE activity_id = ? AND synced = 0"
      " AND local_mode != 'duplicate'",
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
