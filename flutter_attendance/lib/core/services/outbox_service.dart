import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math';

import 'package:flutter/foundation.dart' show kIsWeb, visibleForTesting;
import 'package:http/http.dart' as http;
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';

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
  ///
  /// With [txn] the row is written inside the caller's transaction — so it
  /// lands together with the caller's own rows or not at all — and nothing is
  /// sent until the caller commits and calls [flushIfConnected].
  static Future<int> enqueue({
    required String operation,
    required String url,
    required String idemKey,
    required String token,
    String method = 'POST',
    Map<String, dynamic>? payload,
    String contentType = 'application/json',
    String? refId,
    DatabaseExecutor? txn,
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

    final db = txn ?? await _database();
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
    if (txn == null) await flushIfConnected();
    return id;
  }

  // ─── Flush ──────────────────────────────────────────────────────────────

  /// Start a drain in the background when a network is up. Never waits on the
  /// network itself, so it is safe to call from the scan path.
  static Future<void> flushIfConnected() async {
    if (kIsWeb) return;
    try {
      if (await ConnectivityService.isConnected()) flush();
    } catch (_) {
      // Connectivity unknown: the sync poll will try again.
    }
  }

  /// Drain all queued rows in FIFO order. Safe to call repeatedly; concurrent
  /// calls are coalesced via [_flushing].
  ///
  /// [force] is the user asking for it ("Sync now"): rows waiting out a
  /// backoff are sent now instead of when their retry comes due.
  static Future<void> flush({bool force = false}) async {
    if (kIsWeb) return;
    if (_flushing) return;
    _flushing = true;
    try {
      final db = await _database();
      final token = tokenProvider?.call();

      if (force) {
        await db.update(_table, {'next_attempt_at': 0},
            where: "status = 'queued'");
      }

      var announced = false;
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

        // Only once something is really being sent: the sync poll calls
        // flush() every few seconds, mostly while the head is backing off.
        if (!announced) {
          onFlushStart?.call();
          announced = true;
        }
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
      result = _SendResult.failed(e.toString());
    }

    if (result.outcome == SendOutcome.done ||
        result.outcome == SendOutcome.rejected) {
      await _notifyResult(row, result);
    }

    switch (result.outcome) {
      case SendOutcome.done:
        await db.delete(_table, where: 'id = ?', whereArgs: [id]);
        return true;

      case SendOutcome.authFailed:
        // Retrying a 401 forever never re-auths. Park the row until the user
        // signs in again, then resumeAfterAuth() puts it back in the queue.
        await db.update(
          _table,
          {
            'status': 'auth_blocked',
            'last_error': readableError(result.statusCode, result.body),
            'last_attempt_at': now,
            'retry_count': retryCount + 1,
          },
          where: 'id = ?',
          whereArgs: [id],
        );
        return false;

      case SendOutcome.rejected:
        // The server refused this for good. Stop retrying and keep it for
        // the user to see, retry or discard, rather than dropping it.
        await db.update(
          _table,
          {
            'status': 'conflict',
            'last_error': readableError(result.statusCode, result.body),
            'last_attempt_at': now,
            'retry_count': retryCount + 1,
          },
          where: 'id = ?',
          whereArgs: [id],
        );
        return true;

      case SendOutcome.retry:
        // Transient (network/5xx/not the API answering): stay queued, but
        // behind a backoff.
        await db.update(
          _table,
          {
            'last_error': readableError(result.statusCode, result.body),
            'last_attempt_at': now,
            'next_attempt_at': now + _backoffMillis(retryCount),
            'retry_count': retryCount + 1,
          },
          where: 'id = ?',
          whereArgs: [id],
        );
        return false;
    }
  }

  /// What a response means for the queued row it answers.
  ///
  /// A 2xx is only done when the API itself answered: it always replies in
  /// JSON, so an HTML page with a 200 is a captive portal or proxy in the way
  /// and the row is sent again. A JSON reply with "ok": false is the server
  /// refusing the write (a scan outside the activity's dates, a closed
  /// activity), as is any 4xx but a timeout or rate limit: set aside for the
  /// user instead of retried forever at the head of the queue.
  @visibleForTesting
  static SendOutcome classify(int statusCode, String body) {
    if (statusCode == 401 || statusCode == 403) return SendOutcome.authFailed;
    if (statusCode >= 200 && statusCode < 300) {
      if (body.trim().isEmpty) return SendOutcome.done;
      final Object? decoded;
      try {
        decoded = jsonDecode(body);
      } catch (_) {
        return SendOutcome.retry;
      }
      if (decoded is! Map) return SendOutcome.retry;
      return decoded['ok'] == false ? SendOutcome.rejected : SendOutcome.done;
    }
    if (statusCode == 408 || statusCode == 429) return SendOutcome.retry;
    if (statusCode >= 400 && statusCode < 500) return SendOutcome.rejected;
    return SendOutcome.retry;
  }

  /// The server's own message when it sent one, else a short status line.
  static String readableError(int statusCode, String body) {
    try {
      final decoded = jsonDecode(body);
      if (decoded is Map && (decoded['message'] ?? '').toString().isNotEmpty) {
        return decoded['message'].toString();
      }
    } catch (_) {
      // Not JSON: fall through.
    }
    final flat = body.replaceAll(RegExp(r'\s+'), ' ').trim();
    if (statusCode == 0) return flat.isEmpty ? 'No connection' : flat;
    final looksLikePage = flat.startsWith('<');
    final detail = looksLikePage || flat.isEmpty
        ? ''
        : ': ${flat.length > 120 ? '${flat.substring(0, 120)}…' : flat}';
    return looksLikePage
        ? 'HTTP $statusCode — a web page answered instead of the server'
        : 'HTTP $statusCode$detail';
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

    return _SendResult(
      outcome: classify(response.statusCode, response.body),
      statusCode: response.statusCode,
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

  /// Queued rows, when the oldest was queued, and the most retries any has
  /// needed — enough to tell a scan that is simply mid-upload from a queue
  /// that is stuck.
  static Future<({int count, int oldestQueuedAt, int maxRetries})>
      queuedSummary() async {
    if (kIsWeb) return (count: 0, oldestQueuedAt: 0, maxRetries: 0);
    final db = await _database();
    final row = (await db.rawQuery(
      'SELECT COUNT(*) AS c, MIN(queued_at) AS oldest, '
      "MAX(retry_count) AS retries FROM $_table WHERE status = 'queued'",
    ))
        .first;
    return (
      count: (row['c'] as int?) ?? 0,
      oldestQueuedAt: (row['oldest'] as int?) ?? 0,
      maxRetries: (row['retries'] as int?) ?? 0,
    );
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

  /// Send a refused row again, e.g. once an admin has reopened the activity.
  ///
  /// It goes out under a new idempotency key: the server keeps its first
  /// answer per key for a day and would otherwise just replay the refusal.
  /// That is safe because a refused write was never carried out, and scans
  /// cannot double-count either way — the server also dedups them on their
  /// client_scan_id.
  static Future<void> retryConflict(int id) async {
    final db = await _database();
    await db.update(
        _table,
        {
          'status': 'queued',
          'last_error': null,
          'next_attempt_at': 0,
          'idem_key': const Uuid().v4(),
        },
        where: 'id = ?',
        whereArgs: [id]);
    flush();
  }

  /// [retryConflict] for every refused row, e.g. after the admin fixed what
  /// the server objected to for a whole batch of scans.
  static Future<void> retryAllConflicts() async {
    final db = await _database();
    final rows = await db.query(_table,
        columns: ['id'], where: "status = 'conflict'", orderBy: 'id ASC');
    final batch = db.batch();
    for (final r in rows) {
      batch.update(
        _table,
        {
          'status': 'queued',
          'last_error': null,
          'next_attempt_at': 0,
          'idem_key': const Uuid().v4(),
        },
        where: 'id = ?',
        whereArgs: [r['id']],
      );
    }
    await batch.commit(noResult: true);
    flush();
  }

  static Future<void> dispose() async {
    await _connectivitySub?.cancel();
    await LocalDb.close();
  }
}

/// What became of one attempt to send a queued row.
enum SendOutcome {
  /// The server accepted it: drop the row.
  done,

  /// The server refused it for good: set it aside for the user.
  rejected,

  /// The session is no longer valid: park it until the next sign-in.
  authFailed,

  /// No answer, or not the API answering: try again after a backoff.
  retry,
}

class _SendResult {
  _SendResult({
    required this.outcome,
    required this.statusCode,
    required this.body,
  });

  /// Network-level failure: no status code, always retryable.
  factory _SendResult.failed(String body) => _SendResult(
        outcome: SendOutcome.retry,
        statusCode: 0,
        body: body,
      );

  final SendOutcome outcome;
  final int statusCode;
  final String body;
}
