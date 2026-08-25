import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../core/network/api_exception.dart';
import '../domain/app_session.dart';
import '../domain/mobile_config.dart';

/// Talks to the CodeIgniter `/api/mobile/*` layer.
///
/// One app, many clients: the base URL is supplied by the user at runtime
/// (no hardcoded host). All requests target `<baseUrl>/api/mobile/...`.
class AuthApi {
  AuthApi({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  /// Normalize a user-typed URL into a bare `scheme://host[/path]` with no
  /// trailing slash. Adds `https://` when no scheme is present.
  String normalizeBaseUrl(String value) {
    var normalized = value.trim();
    if (normalized.isEmpty) return '';

    if (!normalized.startsWith('http://') &&
        !normalized.startsWith('https://')) {
      normalized = 'https://$normalized';
    }

    return normalized.replaceFirst(RegExp(r'/+$'), '');
  }

  Future<MobileConfig> fetchConfig(String baseUrl) async {
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/config'),
        headers: _jsonHeaders,
      ),
    );
    return MobileConfig.fromJson(_decode(response));
  }

  Future<AppSession> login({
    required String baseUrl,
    required String username,
    required String password,
    String? sy,
    String? semester,
    String? deviceId,
    String? deviceName,
    String? platform,
  }) async {
    final response = await _safeRequest(
      () => _client.post(
        _uri(baseUrl, '/api/mobile/auth/login'),
        headers: _jsonHeaders,
        body: jsonEncode({
          'username': username,
          'password': password,
          if (sy != null && sy.trim().isNotEmpty) 'sy': sy.trim(),
          if (semester != null && semester.trim().isNotEmpty)
            'semester': semester.trim(),
          if (deviceId != null) 'device_id': deviceId,
          if (deviceName != null) 'device_name': deviceName,
          if (platform != null) 'platform': platform,
        }),
      ),
    );

    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException((data['message'] ?? 'Login failed').toString(),
          statusCode: response.statusCode);
    }
    return AppSession.fromLogin(data, baseUrl: normalizeBaseUrl(baseUrl));
  }

  Future<AppSession> fetchCurrentSession({
    required String baseUrl,
    required String token,
  }) async {
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/auth/me'),
        headers: {
          ..._jsonHeaders,
          HttpHeaders.authorizationHeader: 'Bearer $token',
        },
      ),
    );

    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException((data['message'] ?? 'Session expired').toString(),
          statusCode: response.statusCode);
    }
    return AppSession.fromMe(data,
        baseUrl: normalizeBaseUrl(baseUrl), fallbackToken: token);
  }

  Future<void> logout({required String baseUrl, required String token}) async {
    try {
      await _safeRequest(
        () => _client.post(
          _uri(baseUrl, '/api/mobile/auth/logout'),
          headers: {
            ..._jsonHeaders,
            HttpHeaders.authorizationHeader: 'Bearer $token',
          },
        ),
      );
    } catch (_) {
      // Best-effort: a network failure during logout should not block the
      // client from clearing its local session.
    }
  }

  Future<void> changePassword({
    required String baseUrl,
    required String token,
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    final response = await _safeRequest(
      () => _client.post(
        _uri(baseUrl, '/api/mobile/auth/change-password'),
        headers: {
          ..._jsonHeaders,
          HttpHeaders.authorizationHeader: 'Bearer $token',
        },
        body: jsonEncode({
          'current_password': currentPassword,
          'new_password': newPassword,
          'confirm_password': confirmPassword,
        }),
      ),
    );

    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException((data['message'] ?? 'Password change failed').toString(),
          statusCode: response.statusCode);
    }
  }

  Future<void> forgotPassword({
    required String baseUrl,
    required String email,
  }) async {
    final response = await _safeRequest(
      () => _client.post(
        _uri(baseUrl, '/api/mobile/auth/forgot-password'),
        headers: _jsonHeaders,
        body: jsonEncode({'email': email}),
      ),
    );

    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException(
          (data['message'] ?? 'Unable to send reset email').toString(),
          statusCode: response.statusCode);
    }
  }

  // ─── internals ──────────────────────────────────────────────────────────

  static final _jsonHeaders = {
    HttpHeaders.acceptHeader: 'application/json',
    HttpHeaders.contentTypeHeader: 'application/json; charset=utf-8',
  };

  Uri _uri(String baseUrl, String path) {
    final root = normalizeBaseUrl(baseUrl);
    final full = root + path;
    return Uri.parse(full);
  }

  Map<String, dynamic> _decode(http.Response response) {
    final body = response.body;
    if (body.isEmpty) {
      throw ApiException('Empty response from server.',
          statusCode: response.statusCode);
    }

    // Detect the HTML-login-200 trap: a session-expired CI server returns the
    // login page HTML with a 200. Guard against that so the client does not
    // try to jsonDecode a whole web page.
    final trimmed = body.trimLeft();
    if (trimmed.startsWith('<') || trimmed.startsWith('<!')) {
      throw ApiException('Server returned an HTML page instead of JSON.',
          statusCode: response.statusCode);
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is! Map<String, dynamic>) {
        throw ApiException('Unexpected response shape.',
            statusCode: response.statusCode);
      }
      return decoded;
    } on FormatException catch (e) {
      throw ApiException('Malformed JSON: ${e.message}',
          statusCode: response.statusCode);
    }
  }

  Future<http.Response> _safeRequest(
    Future<http.Response> Function() request,
  ) async {
    try {
      final response = await request().timeout(const Duration(seconds: 30));
      return response;
    } on http.ClientException catch (e) {
      throw ApiException(e.message, statusCode: 0);
    } on SocketException catch (e) {
      throw ApiException('Network error: ${e.message}', statusCode: 0);
    } on TimeoutException {
      throw ApiException('The request timed out. Check your connection.',
          statusCode: 0);
    }
  }
}
