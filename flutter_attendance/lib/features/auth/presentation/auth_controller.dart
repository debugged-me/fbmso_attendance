import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/outbox_service.dart';
import '../../../core/services/roster_service.dart';
import '../../../core/services/scan_ledger_service.dart';
import '../../../core/services/update_service.dart';
import '../data/auth_api.dart';
import '../data/session_store.dart';
import '../domain/app_session.dart';
import '../domain/mobile_config.dart';

/// Owns the auth state machine. The app widget holds one instance and
/// rebuilds the tree when [session] / [config] change.
class AuthController extends ChangeNotifier {
  AuthController({
    required AuthApi api,
    required SessionStore store,
    Future<bool> Function()? isOnline,
  })  : _api = api,
        _store = store,
        _isOnline = isOnline ?? _interfaceUp {
    // Queued writes authenticate with the live token, not the one captured
    // when they were enqueued.
    OutboxService.tokenProvider = () => _session?.token;
    ConnectivityService.probeBaseUrl = _store.readBaseUrl();
  }

  final AuthApi _api;
  final SessionStore _store;
  final Future<bool> Function() _isOnline;

  /// How long a cold start waits on the server before carrying on with the
  /// saved session. A dead one-bar signal must not hold the splash screen.
  static const _bootTimeout = Duration(seconds: 8);

  static Future<bool> _interfaceUp() async {
    try {
      return await ConnectivityService.isConnected();
    } catch (_) {
      return true; // unknown: let the request itself find out
    }
  }

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
  /// token is still valid via `/auth/me`.
  ///
  /// Only the server refusing the token signs the user out. With no signal the
  /// saved session is used as-is: signing in again needs the server, so
  /// dropping it would leave a phone restarted at a venue unable to scan. The
  /// token is checked again by the first request that reaches the server, and
  /// writes queued meanwhile wait for a sign-in if it was refused.
  Future<void> bootstrap() async {
    _baseUrl = _store.readBaseUrl();
    final saved = _store.readSession();

    if (_baseUrl.isEmpty || saved == null) {
      _bootstrapping = false;
      notifyListeners();
      return;
    }

    if (!await _isOnline()) {
      _session = saved;
      _bootstrapping = false;
      notifyListeners();
      return;
    }

    try {
      _config = await _api.fetchConfig(_baseUrl, timeout: _bootTimeout);
      unawaited(UpdateService.instance.evaluate(_config!));
    } catch (_) {
      // Config fetch failure is non-fatal during bootstrap; the user can
      // still attempt to log in.
    }

    try {
      _session = await _api.fetchCurrentSession(
        baseUrl: _baseUrl,
        token: saved.token,
        timeout: _bootTimeout,
      );
      await _store.saveSession(_session!);
    } on ApiException catch (e) {
      if (_refusedByServer(e)) {
        await _store.clearSession();
        _session = null;
      } else {
        _session = saved;
      }
    } catch (_) {
      _session = saved;
    }

    _bootstrapping = false;
    notifyListeners();
  }

  /// `/auth/me` answered about this token or account: invalid, expired or
  /// revoked (401), inactive or forced to change its password (403), deleted
  /// (404). No network, a timeout, a 5xx or a captive portal's HTML page are
  /// not answers.
  static bool _refusedByServer(ApiException e) =>
      e.statusCode == 401 || e.statusCode == 403 || e.statusCode == 404;

  /// Load `/config` for a freshly typed base URL (used by the welcome screen
  /// to show the school name/logo before login).
  ///
  /// People paste whatever page they had open — /login, a deep link, an
  /// index.php path. When nothing answers at the pasted address, the probe
  /// climbs one folder at a time until a portal answers or the host root is
  /// reached, and the address that answered becomes the saved base URL.
  Future<void> loadConfig(String baseUrl) async {
    _error = null;
    final normalized = _api.normalizeBaseUrl(baseUrl);
    if (normalized.isEmpty) {
      _config = null;
      notifyListeners();
      return;
    }
    final problem = _api.checkPortalUrl(normalized);
    if (problem != null) {
      _error = problem;
      _config = null;
      notifyListeners();
      return;
    }
    try {
      ApiException? failure;
      for (final candidate in _api.portalCandidates(normalized)) {
        try {
          _config = await _api.fetchConfig(candidate);
          _baseUrl = candidate;
          ConnectivityService.probeBaseUrl = candidate;
          await _store.saveBaseUrl(candidate);
          unawaited(UpdateService.instance.evaluate(_config!));
          failure = null;
          break;
        } on ApiException catch (e) {
          failure = e;
          // Only "nothing here" answers justify looking one level up — a
          // dead connection or a server error will not be fixed by it.
          if (e.statusCode != 404 && !e.message.contains('HTML page')) break;
        }
      }
      if (failure != null) {
        _error = connectErrorMessage(normalized, failure, isWeb: kIsWeb);
        _config = null;
      }
    } catch (e) {
      _error = e.toString();
      _config = null;
    }
    notifyListeners();
  }

  /// Turns a failed `/config` call into advice the person typing the
  /// address can act on.
  @visibleForTesting
  static String connectErrorMessage(String baseUrl, ApiException e,
      {required bool isWeb}) {
    if (e.statusCode == 0) {
      final host = Uri.tryParse(baseUrl)?.host ?? '';
      final onThisDevice =
          host == 'localhost' || host == '127.0.0.1' || host == '::1';
      if (onThisDevice && !isWeb) {
        return "Can't reach $baseUrl. On a phone, “localhost” is the phone "
            "itself. Use your computer's Wi-Fi IP address instead, e.g. "
            'http://192.168.1.10/fbmso_attendance, with both on the same Wi-Fi.';
      }
      if (isWeb) {
        return "Can't reach $baseUrl. Check that the server is running and "
            'that the address starts with the right http:// or https://.';
      }
      return "Can't reach $baseUrl. Check the address and your connection.";
    }
    if (e.statusCode == 404 || e.message.contains('HTML page')) {
      return 'No attendance portal answered at $baseUrl. Check the address — '
          'it is usually just the school portal site, e.g. '
          'https://portal.yourschool.edu.';
    }
    return e.message;
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
      // Writes parked on an expired session can go out again now. Draining
      // happens in the background — it must never delay or fail a sign-in.
      unawaited(OutboxService.resumeAfterAuth().catchError((_) {}));
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

  /// Fetch registration form options (courses, year levels, sections).
  ({List<String> courses, List<String> yearLevels, List<String> sections})?
      _regOptionsCache;

  Future<
      ({
        List<String> courses,
        List<String> yearLevels,
        List<String> sections
      })> registrationOptions() async {
    if (_regOptionsCache != null) return _regOptionsCache!;
    final result = await _api.registrationOptions(baseUrl: _baseUrl);
    _regOptionsCache = result;
    return result;
  }

  /// Fetch sections for a specific course + year level.
  Future<List<String>> registrationSections({
    required String course,
    required String yearLevel,
  }) async {
    return _api.registrationSections(
      baseUrl: _baseUrl,
      course: course,
      yearLevel: yearLevel,
    );
  }

  /// Check if a Student ID or email already exists.
  Future<({bool exists, String message})> checkAvailability({
    required String field,
    required String value,
  }) async {
    return _api.checkAvailability(
      baseUrl: _baseUrl,
      field: field,
      value: value,
    );
  }

  Future<String?> register({
    required String studentNumber,
    required String firstName,
    String middleName = '',
    required String lastName,
    String nameExtn = '',
    String sex = '',
    String birthDate = '',
    required String email,
    String contactNo = '',
    String course1 = '',
    String major1 = '',
    required String yearLevel,
    String section = '',
    required String password,
    required String confirmPassword,
  }) async {
    _error = null;
    if (_baseUrl.isEmpty) {
      return 'No school URL set. Go back and enter your school URL.';
    }
    try {
      await _api.register(
        baseUrl: _baseUrl,
        studentNumber: studentNumber,
        firstName: firstName,
        middleName: middleName,
        lastName: lastName,
        nameExtn: nameExtn,
        sex: sex,
        birthDate: birthDate,
        email: email,
        contactNo: contactNo,
        course1: course1,
        major1: major1,
        yearLevel: yearLevel,
        section: section,
        password: password,
        confirmPassword: confirmPassword,
      );
      return null;
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
    _clearOfflineScanData();
    _session = null;
    notifyListeners();
  }

  /// A scanner phone is not necessarily the same operator's next time, so the
  /// cached roster (names, photos, student numbers) must not outlive the
  /// session that downloaded it.
  ///
  /// Deliberately not awaited: the wipe still runs, but a slow or stuck local
  /// database must never leave the user stranded on a half-finished sign-out.
  void _clearOfflineScanData() {
    unawaited(Future(() async {
      await RosterService.clearAll();
      await ScanLedgerService.clearAll();
    }).catchError((_) {}));
  }

  /// Forget the paired school: clears the session, base URL and cached config
  /// so the root flow falls back to the welcome/URL-entry screen.
  ///
  /// This is a STATE change, not a navigation — the root [ListenableBuilder]
  /// swaps the screen. Screens must not push/replace the root route themselves
  /// or they detach the tree from this controller.
  Future<void> unpair() async {
    final s = _session;
    if (s != null) {
      try {
        await _api.logout(baseUrl: s.baseUrl, token: s.token);
      } catch (_) {
        // Best effort — we're forgetting this server anyway.
      }
    }
    await _store.clearPairing();
    _clearOfflineScanData();
    _session = null;
    _config = null;
    _baseUrl = '';
    _error = null;
    _regOptionsCache = null;
    notifyListeners();
  }

  void clearError() {
    if (_error != null) {
      _error = null;
      notifyListeners();
    }
  }
}
