import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Keystore/keychain-backed storage for the bearer token and any other
/// secrets. The SessionStore mirrors the token into SharedPreferences for
/// fast cold-start reads, but the authoritative copy lives here.
class SecureStorageService {
  static const _keyToken = 'bearer_token';
  static const _keyUsername = 'username';

  static final FlutterSecureStorage _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  );

  static Future<void> saveToken(String token) =>
      _storage.write(key: _keyToken, value: token);

  static Future<String?> readToken() => _storage.read(key: _keyToken);

  static Future<void> saveUsername(String username) =>
      _storage.write(key: _keyUsername, value: username);

  static Future<String?> readUsername() => _storage.read(key: _keyUsername);

  static Future<void> clear() async {
    await _storage.delete(key: _keyToken);
    await _storage.delete(key: _keyUsername);
  }

  /// Generic read/write for other secrets (e.g. the biometric-enabled flag).
  static Future<String?> readRaw(String key) => _storage.read(key: key);
  static Future<void> writeRaw(String key, String value) =>
      _storage.write(key: key, value: value);
  static Future<void> deleteRaw(String key) => _storage.delete(key: key);
}
