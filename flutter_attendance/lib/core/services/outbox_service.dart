import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math';

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:sqflite/sqflite.dart';

import 'connectivity_service.dart';
import 'local_db.dart';

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
  static const _table = 'outbox';
  static StreamSubscription<bool>? _connectivitySub;
  static bool _flushing = false;

  /// One client for the whole app. A per-request client leaked a socket on
  /// every queued write, which matters most on the long offline→online drain.
  static final http.Client _http = http.Client();

  /// A dead-but-associated connection (one bar of signal at a venue) otherwise
  /// hangs the drain forever, since no bytes ever arrive.
  static const _requestTimeout = Duration(seconds: 20);

  static const _maxBackoff = Duration(minutes: 5);
  static final _random = Random();

  /// Called when a drain begins, so the UI can show a spinner.
  static void Function()? onFlushStart;

  /// Supplies the bearer token to use at flush time. A row carries the token
  /// that was current when it was queued, which can be stale by the time the
  /// queue drains; the live session's token is the one that will authenticate.
  static String? Function()? tokenProvider;

  /// Per-operation hooks that receive the server's answer before the queued row
  /// is dropped. Without this the response body is discarded and the server's
  /// verdict on an offline write never reaches the UI.
  static final Map<String,
      Future<void> Function(String refId, int status, Map<String, dynamic> body)>
      _resultHandlers = {};

  static void registerResultHandler(
    String operation,
    Future<void> Function(String refId, int status, Map<String, dynamic> body) handler,
  ) {
    _resultHandlers[operation] = handler;
  }

  /// Called once at startup to open the DB and begin auto-flushing.
  ///
  /// On web (used only for development preview) sqflite is unavailable, so
  /// the outbox is disabled — writes go straight to the network instead.
  static Future<void> initialize() async {
    if (kIsWeb) return;
    await _database();
    _connectivitySub?.cancel();
    _connectivitySub = ConnectivityService.connectionStream.listen((online) {
      if (online) flush();
    });
  }

  // ─── Schema ─────────────────────────────────────────────────────────────

  static Future<Database> _database() => LocalDb.instance();

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
    String? refId,
  }) async {
    // Web preview: no SQLite — send directly, drop on failure.
    if (kIsWeb) {
      await _send(
        method: method.toUpperCase(),
        url: url,
        token: token,
        idemKey: idemKey,
        payload: payload ?? {},
        contentType: contentType,
      );
      return 0;
    }

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
      'next_attempt_at': 0,
      'ref_id': refId,
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
    if (kIsWeb) return;
    if (_flushing) return;
    _flushing = true;
    onFlushStart?.call();
    try {
      final db = await _database();
      final token = tokenProvider?.call();

      while (true) {
        final rows = await db.query(
          _table,
          where: "status = 'queued'",
          orderBy: 'id ASC',
          limit: 1,
        );
        if (rows.isEmpty) break;

        final row = rows.first;
        final dueAt = (row['next_attempt_at'] as int?) ?? 0;
        // Strict FIFO: a backoff on the head of the queue holds everything
        // behind it, so a check-out can never overtake its own check-in.
        if (dueAt > DateTime.now().millisecondsSinceEpoch) break;

        if (!await _sendOne(db, row, token)) break;
      }
    } finally {
      _flushing = false;
    }
  }

  /// Exponential backoff with jitter, so a venue coming back online does not
  /// have every queued device retry in lockstep.
  static int _backoffMillis(int retryCount) {
    final base = 1000 * pow(2, retryCount.clamp(0, 10)).toInt();
    final capped = min(base, _maxBackoff.inMilliseconds);
    return capped ~/ 2 + _random.nextInt(capped ~/ 2 + 1);
  }

  /// Send one queued row. Returns true when the drain may continue, false when
  /// it should stop (the head of the queue is now backing off or needs a login).
  static Future<bool> _sendOne(
    Database db,
    Map<String, dynamic> row,
    String? liveToken,
  ) async {
    final id = row['id'] as int;
    final url = row['url'] as String;
    final method = row['method'] as String;
    final idemKey = row['idem_key'] as String;
    final contentType = row['content_type'] as String;
    final retryCount = (row['retry_count'] as int?) ?? 0;
    final token = (liveToken != null && liveToken.isNotEmpty)
        ? liveToken
        : row['token'] as String;

    Map<String, dynamic> payload;
    try {
      payload = jsonDecode(row['payload'] as String) as Map<String, dynamic>;
    } catch (_) {
      payload = {};
    }

    final now = DateTime.now().millisecondsSinceEpoch;

    _SendResult result;
    try {
      result = await _send(
        method: method,
        url: url,
        token: token,
        idemKey: idemKey,
        payload: payload,
        contentType: contentType,
      );
    } catch (e) {
      result = _SendResult(
          success: false, conflict: false, authFailed: false,
          statusCode: 0, body: e.toString());
    }

    if (result.success || result.conflict) {
      await _notifyResult(row, result);
    }

    if (result.success) {
      await db.delete(_table, where: 'id = ?', whereArgs: [id]);
      return true;
    }

    if (result.authFailed) {
      // Retrying a 401 forever never re-auths. Park the row until the user
      // signs in again, then resumeAfterAuth() puts it back in the queue.
      await db.update(
        _table,
        {
          'status': 'auth_blocked',
          'last_error': result.body,
          'last_attempt_at': now,
          'retry_count': retryCount + 1,
        },
        where: 'id = ?',
        whereArgs: [id],
      );
      return false;
    }

    if (result.conflict) {
      // 409/410 — the server rejected this permanently. Stop retrying and
      // surface it for manual action rather than dropping it.
      await db.update(
        _table,
        {
          'status': 'conflict',
          'last_error': result.body,
          'last_attempt_at': now,
          'retry_count': retryCount + 1,
        },
        where: 'id = ?',
        whereArgs: [id],
      );
      return true;
    }

    // Transient (network/5xx): stay queued, but behind a backoff.
    await db.update(
      _table,
      {
        'last_error': result.body,
        'last_attempt_at': now,
        'next_attempt_at': now + _backoffMillis(retryCount),
        'retry_count': retryCount + 1,
      },
      where: 'id = ?',
      whereArgs: [id],
    );
    return false;
  }

  static Future<void> _notifyResult(
      Map<String, dynamic> row, _SendResult result) async {
    final refId = row['ref_id'] as String?;
    if (refId == null || refId.isEmpty) return;

    final handler = _resultHandlers[row['operation'] as String];
    if (handler == null) return;

    Map<String, dynamic> body;
    try {
      final decoded = jsonDecode(result.body);
      body = decoded is Map<String, dynamic> ? decoded : {};
    } catch (_) {
      body = {};
    }

    try {
      await handler(refId, result.statusCode, body);
    } catch (_) {
      // A reconciliation hook must never block the queue from draining.
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
      final streamed = await _http.send(req).timeout(_requestTimeout);
      response =
          await http.Response.fromStream(streamed).timeout(_requestTimeout);
    } on http.ClientException catch (e) {
      return _SendResult.failed(e.message);
    } on SocketException catch (e) {
      return _SendResult.failed(e.message);
    } on TimeoutException {
      return _SendResult.failed('Request timed out');
    }

    final code = response.statusCode;
    return _SendResult(
      success: code >= 200 && code < 300,
      conflict: code == 409 || code == 410,
      authFailed: code == 401 || code == 403,
      statusCode: code,
      body: response.body,
    );
  }

  // ─── Introspection (for the outbox viewer UI) ───────────────────────────

  static Future<int> queuedCount() async {
    if (kIsWeb) return 0;
    final db = await _database();
    final rows = await db.rawQuery(
        "SELECT COUNT(*) AS c FROM $_table WHERE status = 'queued'");
    return Sqflite.firstIntValue(rows) ?? 0;
  }

  static Future<int> conflictCount() async {
    if (kIsWeb) return 0;
    final db = await _database();
    final rows = await db.rawQuery(
        "SELECT COUNT(*) AS c FROM $_table WHERE status = 'conflict'");
    return Sqflite.firstIntValue(rows) ?? 0;
  }

  static Future<int> authBlockedCount() async {
    if (kIsWeb) return 0;
    final db = await _database();
    final rows = await db.rawQuery(
        "SELECT COUNT(*) AS c FROM $_table WHERE status = 'auth_blocked'");
    return Sqflite.firstIntValue(rows) ?? 0;
  }

  /// Put rows parked on a 401 back in the queue. Call after a successful login.
  static Future<void> resumeAfterAuth() async {
    if (kIsWeb) return;
    final db = await _database();
    await db.update(
      _table,
      {'status': 'queued', 'last_error': null, 'next_attempt_at': 0},
      where: "status = 'auth_blocked'",
    );
    flush();
  }

  static Future<List<Map<String, dynamic>>> allRows() async {
    if (kIsWeb) return [];
    final db = await _database();
    return db.query(_table, orderBy: 'id ASC');
  }

  static Future<void> dismissConflict(int id) async {
    final db = await _database();
    await db.delete(_table, where: 'id = ?', whereArgs: [id]);
  }

  static Future<void> retryConflict(int id) async {
    final db = await _database();
    await db.update(
        _table,
        {'status': 'queued', 'last_error': null, 'next_attempt_at': 0},
        where: 'id = ?',
        whereArgs: [id]);
    flush();
  }

  static Future<void> dispose() async {
    await _connectivitySub?.cancel();
    await LocalDb.close();
  }
}

class _SendResult {
  _SendResult({
    required this.success,
    required this.conflict,
    required this.authFailed,
    required this.statusCode,
    required this.body,
  });

  /// Network-level failure: no status code, always retryable.
  factory _SendResult.failed(String body) => _SendResult(
        success: false,
        conflict: false,
        authFailed: false,
        statusCode: 0,
        body: body,
      );

  final bool success;
  final bool conflict;
  final bool authFailed;
  final int statusCode;
  final String body;
}
