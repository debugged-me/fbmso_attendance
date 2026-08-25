import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// Read-through cache for offline access. Stores the last successful JSON
/// response of each endpoint so the app renders immediately with no signal.
///
/// Single-document responses (profile, config, my-qr) use SharedPreferences;
/// list responses (activities, announcements, logs) are stored as JSON
/// arrays under one key each. Larger datasets in later phases move to SQLite,
/// but SharedPreferences is enough for the initial cache surface.
class OfflineStorageService {
  static const _prefix = 'offline_';
  static const _keyLastSync = '${_prefix}last_sync';

  // ─── Single-document get/set ────────────────────────────────────────────

  static Future<void> saveDoc(String key, Map<String, dynamic> data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('$_prefix$key', jsonEncode(data));
    await _touchLastSync(prefs);
  }

  static Future<Map<String, dynamic>?> getDoc(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_prefix$key');
    if (raw == null) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  // ─── List get/set ───────────────────────────────────────────────────────

  static Future<void> saveList(String key, List<Map<String, dynamic>> items) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('$_prefix$key', jsonEncode(items));
    await _touchLastSync(prefs);
  }

  static Future<List<Map<String, dynamic>>> getList(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_prefix$key');
    if (raw == null) return [];
    try {
      final list = jsonDecode(raw) as List<dynamic>;
      return list.cast<Map<String, dynamic>>();
    } catch (_) {
      return [];
    }
  }

  // ─── Last-sync stamp ────────────────────────────────────────────────────

  static Future<void> _touchLastSync(SharedPreferences prefs) async {
    await prefs.setInt(_keyLastSync, DateTime.now().millisecondsSinceEpoch);
  }

  static Future<int> lastSyncMillis() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(_keyLastSync) ?? 0;
  }

  static Future<String> lastSyncLabel() async {
    final ms = await lastSyncMillis();
    if (ms == 0) return 'never';
    final age = DateTime.now().millisecondsSinceEpoch - ms;
    final mins = (age / 60000).floor();
    if (mins < 1) return 'just now';
    if (mins < 60) return '$mins min ago';
    final hours = (mins / 60).floor();
    if (hours < 24) return '$hours hr ago';
    final days = (hours / 24).floor();
    return '$days day(s) ago';
  }

  // ─── Clear ──────────────────────────────────────────────────────────────

  static Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    final keys = prefs.getKeys().where((k) => k.startsWith(_prefix));
    for (final k in keys) {
      await prefs.remove(k);
    }
  }
}
