import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../data/auth_api.dart';
import '../data/session_store.dart';
import '../domain/app_session.dart';
import '../domain/mobile_config.dart';

/// Owns the auth state machine. The app widget holds one instance and
/// rebuilds the tree when [session] / [config] change.
class AuthController extends ChangeNotifier {
  AuthController({required AuthApi api, required SessionStore store})
      : _api = api,
        _store = store;

  final AuthApi _api;
  final SessionStore _store;

  AppSession? _session;
  MobileConfig? _config;
  String _baseUrl = '';
  bool _bootstrapping = true;
  String? _error;

  AppSession? get session => _session;
  MobileConfig? get config => _config;
  String get baseUrl => _baseUrl;
  bool get bootstrapping => _bootstrapping;
  bool get isAuthenticated => _session != null;
  String? get error => _error;

  /// On cold start: restore the saved base URL + session, then verify the
  /// token is still valid via `/auth/me`. Falls back to login on any failure.
  Future<void> bootstrap() async {
    _baseUrl = _store.readBaseUrl();
    final saved = _store.readSession();

    if (_baseUrl.isEmpty || saved == null) {
      _bootstrapping = false;
      notifyListeners();
      return;
    }

    try {
      _config = await _api.fetchConfig(_baseUrl);
    } catch (_) {
      // Config fetch failure is non-fatal during bootstrap; the user can
      // still attempt to log in.
    }

    try {
      _session = await _api.fetchCurrentSession(
        baseUrl: _baseUrl,
        token: saved.token,
      );
      await _store.saveSession(_session!);
    } catch (_) {
      // Token expired or unreachable — clear and require a fresh login.
      await _store.clearSession();
      _session = null;
    }

    _bootstrapping = false;
    notifyListeners();
  }

  /// Load `/config` for a freshly typed base URL (used by the welcome screen
  /// to show the school name/logo before login).
  Future<void> loadConfig(String baseUrl) async {
    _error = null;
    final normalized = _api.normalizeBaseUrl(baseUrl);
    if (normalized.isEmpty) {
      _config = null;
      notifyListeners();
      return;
    }
    try {
      _config = await _api.fetchConfig(normalized);
      _baseUrl = normalized;
      await _store.saveBaseUrl(normalized);
    } on ApiException catch (e) {
      _error = e.message;
      _config = null;
    } catch (e) {
      _error = e.toString();
      _config = null;
    }
    notifyListeners();
  }

  Future<bool> login({
    required String username,
    required String password,
    String? sy,
    String? semester,
  }) async {
    _error = null;
    if (_baseUrl.isEmpty) {
      _error = 'Please enter your school URL first.';
      notifyListeners();
      return false;
    }
    try {
      _session = await _api.login(
        baseUrl: _baseUrl,
        username: username.trim(),
        password: password,
        sy: sy,
        semester: semester,
        platform: defaultTargetPlatform.name,
      );
      await _store.saveSession(_session!);
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      notifyListeners();
      return false;
    } catch (e) {
      _error = e.toString();
      notifyListeners();
      return false;
    }
  }

  Future<String?> forgotPassword(String email) async {
    _error = null;
    if (_baseUrl.isEmpty) {
      return 'No school URL set. Go back and enter your school URL.';
    }
    try {
      await _api.forgotPassword(baseUrl: _baseUrl, email: email.trim());
      return null; // success
    } on ApiException catch (e) {
      return e.message;
    } catch (e) {
      return e.toString();
    }
  }

  Future<void> logout() async {
    final s = _session;
    if (s != null) {
      await _api.logout(baseUrl: s.baseUrl, token: s.token);
    }
    await _store.clearSession();
    _session = null;
    notifyListeners();
  }

  void clearError() {
    if (_error != null) {
      _error = null;
      notifyListeners();
    }
  }
}
