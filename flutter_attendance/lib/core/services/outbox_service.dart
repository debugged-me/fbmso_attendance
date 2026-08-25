import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';

import 'connectivity_service.dart';

/// Queues write operations when offline and flushes them when connectivity
/// returns. SQLite-backed so queued items survive app kills and reboots.
///
/// EVERY write in the app funnels through [enqueue] instead of hitting the
/// network directly. This is what delivers "all of it offline" — attendance
/// check-in, note edits, profile updates, accounting payments, everything.
///
/// Idempotency keys (client-generated UUIDs) prevent duplicate execution on
/// retry: the server records each key in `o_mobile_outbox` and replays the
/// first response for any retry with the same key.
class OutboxService {
  static Database? _db;
  static const _dbName = 'fbmsO_outbox.db';
  static const _table = 'outbox';
  static StreamSubscription<bool>? _connectivitySub;
  static bool _flushing = false;

  /// Called once at startup to open the DB and begin auto-flushing.
  static Future<void> initialize() async {
    await _database();
    _connectivitySub?.cancel();
    _connectivitySub = ConnectivityService.connectionStream.listen((online) {
      if (online) flush();
    });
  }

  // ─── Schema ─────────────────────────────────────────────────────────────

  static Future<Database> _database() async {
    if (_db != null) return _db!;
    final dir = await getApplicationDocumentsDirectory();
    final path = p.join(dir.path, _dbName);
    _db = await openDatabase(
      path,
      version: 1,
      onCreate: (db, _) async {
        await db.execute('''
          CREATE TABLE $_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            operation TEXT NOT NULL,
            url TEXT NOT NULL,
            method TEXT NOT NULL DEFAULT 'POST',
            payload TEXT NOT NULL,
            idem_key TEXT NOT NULL,
            token TEXT NOT NULL,
            content_type TEXT NOT NULL DEFAULT 'application/json',
            client_submitted_at INTEGER NOT NULL,
            queued_at INTEGER NOT NULL,
            retry_count INTEGER DEFAULT 0,
            last_error TEXT,
            last_attempt_at INTEGER,
            status TEXT NOT NULL DEFAULT 'queued'
          )
        ''');
        await db.execute(
            'CREATE INDEX idx_outbox_status ON $_table (status)');
      },
    );
    return _db!;
  }

  // ─── Enqueue ────────────────────────────────────────────────────────────

  /// Queue a write. Returns the row id. If online, [flush] is triggered
  /// immediately so the request still goes out in near-real-time.
  static Future<int> enqueue({
    required String operation,
    required String url,
    required String idemKey,
    required String token,
    String method = 'POST',
    Map<String, dynamic>? payload,
    String contentType = 'application/json',
  }) async {
    final db = await _database();
    final now = DateTime.now().millisecondsSinceEpoch;
    final id = await db.insert(_table, {
      'operation': operation,
      'url': url,
      'method': method.toUpperCase(),
      'payload': jsonEncode(payload ?? {}),
      'idem_key': idemKey,
      'token': token,
      'content_type': contentType,
      'client_submitted_at': now,
      'queued_at': now,
      'retry_count': 0,
      'last_error': null,
      'last_attempt_at': null,
      'status': 'queued',
    });

    // If we happen to be online, try to send it right away.
    if (await ConnectivityService.isConnected()) {
      flush();
    }
    return id;
  }

  // ─── Flush ──────────────────────────────────────────────────────────────

  /// Drain all queued rows in FIFO order. Safe to call repeatedly; concurrent
  /// calls are coalesced via [_flushing].
  static Future<void> flush() async {
    if (_flushing) return;
    _flushing = true;
    try {
      final db = await _database();
      while (true) {
        final rows = await db.query(
          _table,
          where: "status = 'queued'",
          orderBy: 'id ASC',
          limit: 1,
        );
        if (rows.isEmpty) break;
        await _sendOne(db, rows.first);
      }
    } finally {
      _flushing = false;
    }
  }

  static Future<void> _sendOne(Database db, Map<String, dynamic> row) async {
    final id = row['id'] as int;
    final url = row['url'] as String;
    final method = row['method'] as String;
    final token = row['token'] as String;
    final idemKey = row['idem_key'] as String;
    final contentType = row['content_type'] as String;
    Map<String, dynamic> payload;
    try {
      payload = jsonDecode(row['payload'] as String) as Map<String, dynamic>;
    } catch (_) {
      payload = {};
    }

    try {
      final result = await _send(
        method: method,
        url: url,
        token: token,
        idemKey: idemKey,
        payload: payload,
        contentType: contentType,
      );

      if (result.success) {
        await db.delete(_table, where: 'id = ?', whereArgs: [id]);
      } else if (result.conflict) {
        // 409/410 — server rejected permanently. Mark failed so it stops
        // retrying and surfaces in the outbox viewer.
        await db.update(
          _table,
          {
            'status': 'conflict',
            'last_error': result.body,
            'last_attempt_at': DateTime.now().millisecondsSinceEpoch,
            'retry_count': (row['retry_count'] as int) + 1,
          },
          where: 'id = ?',
          whereArgs: [id],
        );
      } else {
        // Transient failure (network/5xx) — leave as queued for next flush.
        await db.update(
          _table,
          {
            'last_error': result.body,
            'last_attempt_at': DateTime.now().millisecondsSinceEpoch,
            'retry_count': (row['retry_count'] as int) + 1,
          },
          where: 'id = ?',
          whereArgs: [id],
        );
      }
    } catch (e) {
      await db.update(
        _table,
        {
          'last_error': e.toString(),
          'last_attempt_at': DateTime.now().millisecondsSinceEpoch,
          'retry_count': (row['retry_count'] as int) + 1,
        },
        where: 'id = ?',
        whereArgs: [id],
      );
    }
  }

  /// Perform a single HTTP request with the bearer token + idempotency key.
  static Future<_SendResult> _send({
    required String method,
    required String url,
    required String token,
    required String idemKey,
    required Map<String, dynamic> payload,
    required String contentType,
  }) async {
    final headers = <String, String>{
      HttpHeaders.acceptHeader: 'application/json',
      HttpHeaders.contentTypeHeader: '$contentType; charset=utf-8',
      HttpHeaders.authorizationHeader: 'Bearer $token',
      'X-Idempotency-Key': idemKey,
    };

    http.Response response;
    try {
      final req = http.Request(method, Uri.parse(url));
      req.headers.addAll(headers);
      req.body = jsonEncode(payload);
      final streamed = await http.Client().send(req);
      response = await http.Response.fromStream(streamed);
    } on http.ClientException catch (e) {
      return _SendResult(success: false, conflict: false, body: e.message);
    } on SocketException catch (e) {
      return _SendResult(success: false, conflict: false, body: e.message);
    } on TimeoutException {
      return _SendResult(
          success: false, conflict: false, body: 'Request timed out');
    }

    final ok = response.statusCode >= 200 && response.statusCode < 300;
    final conflict = response.statusCode == 409 || response.statusCode == 410;
    return _SendResult(
      success: ok,
      conflict: conflict,
      body: response.body,
    );
  }

  // ─── Introspection (for the outbox viewer UI) ───────────────────────────

  static Future<int> queuedCount() async {
    final db = await _database();
    final rows = await db.rawQuery(
        "SELECT COUNT(*) AS c FROM $_table WHERE status = 'queued'");
    return Sqflite.firstIntValue(rows) ?? 0;
  }

  static Future<int> conflictCount() async {
    final db = await _database();
    final rows = await db.rawQuery(
        "SELECT COUNT(*) AS c FROM $_table WHERE status = 'conflict'");
    return Sqflite.firstIntValue(rows) ?? 0;
  }

  static Future<List<Map<String, dynamic>>> allRows() async {
    final db = await _database();
    return db.query(_table, orderBy: 'id ASC');
  }

  static Future<void> dismissConflict(int id) async {
    final db = await _database();
    await db.delete(_table, where: 'id = ?', whereArgs: [id]);
  }

  static Future<void> retryConflict(int id) async {
    final db = await _database();
    await db.update(_table, {'status': 'queued', 'last_error': null},
        where: 'id = ?', whereArgs: [id]);
    flush();
  }

  static Future<void> dispose() async {
    await _connectivitySub?.cancel();
    await _db?.close();
    _db = null;
  }
}

class _SendResult {
  _SendResult({required this.success, required this.conflict, required this.body});
  final bool success;
  final bool conflict;
  final String body;
}
