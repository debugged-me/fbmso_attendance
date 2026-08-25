import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import '../domain/app_session.dart';

/// Persists the session and the user-entered base URL across app launches.
///
/// The token itself is also mirrored through SecureStorageService (see
/// Phase 3) for keystore/keychain-backed protection; here we keep a
/// SharedPreferences copy so a fast cold-start can read it synchronously.
class SessionStore {
  SessionStore(this._preferences);

  final SharedPreferences _preferences;

  static const _sessionKey = 'app_session';
  static const _baseUrlKey = 'app_base_url';
  static const _pairedKey = 'app_is_paired';

  String readBaseUrl() => _preferences.getString(_baseUrlKey) ?? '';

  Future<void> saveBaseUrl(String baseUrl) =>
      _preferences.setString(_baseUrlKey, baseUrl);

  bool readIsPaired() => _preferences.getBool(_pairedKey) ?? false;

  Future<void> savePairedState(bool isPaired) =>
      _preferences.setBool(_pairedKey, isPaired);

  Future<void> savePairing(String baseUrl) async {
    await saveBaseUrl(baseUrl);
    await savePairedState(true);
  }

  AppSession? readSession() {
    final raw = _preferences.getString(_sessionKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return null;
      return AppSession.fromStorage(Map<String, dynamic>.from(decoded));
    } catch (_) {
      return null;
    }
  }

  Future<void> saveSession(AppSession session) async {
    await savePairing(session.baseUrl);
    await _preferences.setString(_sessionKey, jsonEncode(session.toJson()));
  }

  Future<void> clearSession() async {
    await _preferences.remove(_sessionKey);
    await _preferences.setBool(_pairedKey, false);
  }
}
