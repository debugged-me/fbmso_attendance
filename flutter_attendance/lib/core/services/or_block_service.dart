import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:sqflite/sqflite.dart';

import 'device_identity.dart';
import 'local_db.dart';

/// Holds the receipt numbers this device reserved for offline use.
///
/// O.R. numbers are allocated server-side, so a cashier with no signal could
/// otherwise only promise a number later — useless at the window. The device
/// claims a contiguous block while online and spends it offline; the server
/// rejects any number this device was not given.
class OrBlockService {
  static const _table = 'or_block';
  static const _timeout = Duration(seconds: 20);

  /// Pull a fresh block when fewer than this many numbers remain.
  static const lowWaterMark = 10;
  static const defaultBlockSize = 50;

  static final http.Client _http = http.Client();

  // ─── Local block ───────────────────────────────────────────────────────

  /// Numbers still unspent for [date] (yyyy-MM-dd).
  static Future<int> remaining(String date) async {
    if (kIsWeb) return 0;
    final row = await _row(date);
    if (row == null) return 0;
    return ((row['seq_end'] as int) - (row['next_seq'] as int) + 1)
        .clamp(0, 1 << 30);
  }

  /// Take the next reserved number, or null when the block is exhausted.
  ///
  /// The number is consumed even if the payment later fails to sync — a
  /// receipt with that number may already be in the student's hand, so it must
  /// never be handed to someone else.
  static Future<String?> take(String date) async {
    if (kIsWeb) return null;

    final db = await LocalDb.instance();
    return db.transaction<String?>((txn) async {
      final rows = await txn.query(_table,
          where: 'payment_date = ?', whereArgs: [date], limit: 1);
      if (rows.isEmpty) return null;

      final row = rows.first;
      final next = row['next_seq'] as int;
      if (next > (row['seq_end'] as int)) return null;

      await txn.update(
        _table,
        {'next_seq': next + 1},
        where: 'payment_date = ?',
        whereArgs: [date],
      );
      return _format(row['prefix'] as String, next);
    });
  }

  // ─── Reservation ───────────────────────────────────────────────────────

  /// Reserve a block from the server if this device is running low. Safe to
  /// call whenever the cashier screen is open and online.
  static Future<void> ensureStocked({
    required String baseUrl,
    required String token,
    required String date,
    int count = defaultBlockSize,
  }) async {
    if (kIsWeb) return;
    if (await remaining(date) >= lowWaterMark) return;
    await reserve(baseUrl: baseUrl, token: token, date: date, count: count);
  }

  /// Claim [count] numbers for [date] and store them locally.
  static Future<int> reserve({
    required String baseUrl,
    required String token,
    required String date,
    int count = defaultBlockSize,
  }) async {
    if (kIsWeb) return 0;

    final deviceId = await DeviceIdentity.id();
    final base = _normalize(baseUrl);

    final response = await _http
        .post(
          Uri.parse('$base/api/mobile/accounting/or_block/reserve'),
          headers: {
            HttpHeaders.acceptHeader: 'application/json',
            HttpHeaders.contentTypeHeader: 'application/json; charset=utf-8',
            HttpHeaders.authorizationHeader: 'Bearer $token',
          },
          body: jsonEncode({
            'device_id': deviceId,
            'date': date,
            'count': count,
          }),
        )
        .timeout(_timeout);

    final decoded = jsonDecode(response.body);
    if (decoded is! Map<String, dynamic> || decoded['ok'] != true) {
      throw Exception(
          (decoded is Map ? decoded['message'] : null)?.toString() ??
              'Could not reserve O.R. numbers.');
    }

    final start = (decoded['seq_start'] as num).toInt();
    final end = (decoded['seq_end'] as num).toInt();

    final db = await LocalDb.instance();
    await db.insert(
      _table,
      {
        'payment_date': date,
        'prefix': (decoded['prefix'] ?? '').toString(),
        'seq_start': start,
        'seq_end': end,
        'next_seq': start,
        'reserved_at': DateTime.now().millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );

    return end - start + 1;
  }

  // ─── Housekeeping ──────────────────────────────────────────────────────

  /// Drop blocks for past dates. Their numbers are gone either way; keeping
  /// them would only risk spending a stale one on the wrong day.
  static Future<void> pruneBefore(String date) async {
    if (kIsWeb) return;
    final db = await LocalDb.instance();
    await db.delete(_table, where: 'payment_date < ?', whereArgs: [date]);
  }

  static Future<void> clearAll() async {
    if (kIsWeb) return;
    final db = await LocalDb.instance();
    await db.delete(_table);
  }

  static Future<Map<String, Object?>?> _row(String date) async {
    final db = await LocalDb.instance();
    final rows = await db.query(_table,
        where: 'payment_date = ?', whereArgs: [date], limit: 1);
    return rows.isEmpty ? null : rows.first;
  }

  static String _format(String prefix, int sequence) =>
      '$prefix-${sequence.toString().padLeft(4, '0')}';

  static String _normalize(String baseUrl) {
    var b = baseUrl.trim();
    while (b.endsWith('/')) {
      b = b.substring(0, b.length - 1);
    }
    return b;
  }
}
