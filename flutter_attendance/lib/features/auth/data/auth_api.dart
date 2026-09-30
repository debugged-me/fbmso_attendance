import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

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
  /// trailing slash. With no scheme, a local address (localhost, a LAN IP,
  /// a `.local` name) gets `http://`, since a dev PC or a school LAN server
  /// rarely has a certificate. Anything else gets `https://`.
  String normalizeBaseUrl(String value) {
    var normalized = value.trim();
    if (normalized.isEmpty) return '';

    // A page URL pasted from the browser carries ?query and #fragment bits
    // (e.g. /login?next=…) that are never part of the portal root.
    final cut = normalized.indexOf(RegExp(r'[?#]'));
    if (cut != -1) normalized = normalized.substring(0, cut);
    if (normalized.isEmpty) return '';

    if (!RegExp(r'^https?://', caseSensitive: false).hasMatch(normalized)) {
      final host = normalized.startsWith('[')
          ? normalized.substring(0, normalized.indexOf(']') + 1)
          : normalized.split(RegExp(r'[/:?#]')).first;
      normalized = '${isLocalHost(host) ? 'http' : 'https'}://$normalized';
    }

    return normalized.replaceFirst(RegExp(r'/+$'), '');
  }

  /// Every address worth probing, starting with [baseUrl] itself and then
  /// each parent path up to the host root. People paste whatever page they
  /// had open — /login, a deep link, an index.php path — so a portal that
  /// lives a folder or two above the pasted URL is still found.
  List<String> portalCandidates(String baseUrl) {
    final out = <String>[baseUrl];
    var rest = baseUrl;
    final hostEnd = rest.indexOf('://') + 3;
    while (true) {
      final slash = rest.lastIndexOf('/');
      if (slash < hostEnd) break;
      rest = rest.substring(0, slash);
      out.add(rest);
    }
    return out;
  }

  /// Why [baseUrl] (already normalized) can't be used, or null when it can.
  /// Plain `http://` is only accepted for a server on this device or the
  /// local network: a public portal must use https, so passwords and tokens
  /// never cross the internet unencrypted.
  String? checkPortalUrl(String baseUrl) {
    final uri = Uri.tryParse(baseUrl);
    if (uri == null || uri.host.isEmpty) {
      return "That doesn't look like a web address. "
          'Example: https://portal.yourschool.edu';
    }
    if (uri.scheme == 'http' && !isLocalHost(uri.host)) {
      return 'Use https:// for this address. Plain http:// only works for '
          'a server on your own network, such as http://192.168.1.10/fbmso_attendance.';
    }
    return null;
  }

  /// True for hosts that only exist on this device or the local network:
  /// localhost, loopback and private IPv4 ranges (which include the Android
  /// emulator's 10.0.2.2), link-local, `.local`/`.lan`/`.home.arpa`/
  /// `.internal`/`.test` names and bare machine names like `office-pc`.
  static bool isLocalHost(String host) {
    final h = host.toLowerCase().replaceAll(RegExp(r'^\[|\]$'), '');
    if (h.isEmpty) return false;
    if (h == 'localhost' || h.endsWith('.localhost') || h == '::1') {
      return true;
    }
    const localSuffixes = ['.local', '.lan', '.home.arpa', '.internal', '.test'];
    if (localSuffixes.any(h.endsWith)) return true;

    final ip = RegExp(r'^(\d{1,3})\.(\d{1,3})\.\d{1,3}\.\d{1,3}$').firstMatch(h);
    if (ip != null) {
      final a = int.parse(ip[1]!);
      final b = int.parse(ip[2]!);
      return a == 127 ||
          a == 10 ||
          (a == 192 && b == 168) ||
          (a == 172 && b >= 16 && b <= 31) ||
          (a == 169 && b == 254);
    }
    return !h.contains('.') && !h.contains(':');
  }

  Future<MobileConfig> fetchConfig(String baseUrl, {Duration? timeout}) async {
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/config'),
        headers: _jsonHeaders,
      ),
      timeout: timeout,
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
    Duration? timeout,
  }) async {
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/auth/me'),
        headers: {
          ..._jsonHeaders,
          HttpHeaders.authorizationHeader: 'Bearer $token',
        },
      ),
      timeout: timeout,
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

  /// Fetch registration form options (courses, year levels, sections).
  Future<({List<String> courses, List<String> yearLevels, List<String> sections})>
      registrationOptions({required String baseUrl}) async {
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/registration/options'),
        headers: _jsonHeaders,
      ),
    );
    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException(
          (data['message'] ?? 'Failed to load options').toString(),
          statusCode: response.statusCode);
    }
    return (
      courses: (data['courses'] as List? ?? [])
          .map((e) => e.toString())
          .where((s) => s.isNotEmpty)
          .toList(),
      yearLevels: (data['year_levels'] as List? ?? [])
          .map((e) => e.toString())
          .where((s) => s.isNotEmpty)
          .toList(),
      sections: (data['sections'] as List? ?? [])
          .map((e) => e.toString())
          .where((s) => s.isNotEmpty)
          .toList(),
    );
  }

  /// Fetch sections for a specific course + year level (cascading dropdown).
  Future<List<String>> registrationSections({
    required String baseUrl,
    required String course,
    required String yearLevel,
  }) async {
    final qs =
        'course=${Uri.encodeComponent(course)}&year_level=${Uri.encodeComponent(yearLevel)}';
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/registration/sections?$qs'),
        headers: _jsonHeaders,
      ),
    );
    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException(
          (data['message'] ?? 'Failed to load sections').toString(),
          statusCode: response.statusCode);
    }
    return (data['sections'] as List? ?? [])
        .map((e) => e.toString())
        .where((s) => s.isNotEmpty)
        .toList();
  }

  /// Check if a Student ID or email already exists.
  /// Returns (exists, message).
  Future<({bool exists, String message})> checkAvailability({
    required String baseUrl,
    required String field,
    required String value,
  }) async {
    final qs =
        'field=${Uri.encodeComponent(field)}&value=${Uri.encodeComponent(value)}';
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/registration/check-availability?$qs'),
        headers: _jsonHeaders,
      ),
    );
    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException(
          (data['message'] ?? 'Check failed').toString(),
          statusCode: response.statusCode);
    }
    return (
      exists: (data['exists'] as bool?) ?? false,
      message: (data['message'] ?? '').toString(),
    );
  }

  /// Register a new student account.
  Future<void> register({
    required String baseUrl,
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
    final response = await _safeRequest(
      () => _client.post(
        _uri(baseUrl, '/api/mobile/auth/register'),
        headers: _jsonHeaders,
        body: jsonEncode({
          'StudentNumber': studentNumber,
          'FirstName': firstName,
          'MiddleName': middleName,
          'LastName': lastName,
          'nameExtn': nameExtn,
          'Sex': sex,
          'birthDate': birthDate,
          'email': email,
          'contactNo': contactNo,
          'Course1': course1,
          'Major1': major1,
          'yearLevel': yearLevel,
          'section': section,
          'password': password,
          'confirm_password': confirmPassword,
        }),
      ),
    );

    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException(
          (data['message'] ?? 'Registration failed').toString(),
          statusCode: response.statusCode);
    }
  }

  /// Fetch the current user's avatar URL from `GET /api/mobile/auth/avatar`.
  Future<String> fetchAvatar({
    required String baseUrl,
    required String token,
  }) async {
    final response = await _safeRequest(
      () => _client.get(
        _uri(baseUrl, '/api/mobile/auth/avatar'),
        headers: {
          ..._jsonHeaders,
          HttpHeaders.authorizationHeader: 'Bearer $token',
        },
      ),
    );

    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException(
          (data['message'] ?? 'Failed to fetch avatar').toString(),
          statusCode: response.statusCode);
    }
    return (data['avatar'] ?? data['avatar_url'] ?? '').toString();
  }

  /// Upload a new avatar image via `POST /api/mobile/auth/change-avatar`
  /// (multipart form-data). Returns the new avatar URL on success.
  Future<String> changeAvatar({
    required String baseUrl,
    required String token,
    required XFile file,
  }) async {
    final url = _uri(baseUrl, '/api/mobile/auth/change-avatar');
    final request = http.MultipartRequest('POST', url)
      ..headers.addAll({
        HttpHeaders.authorizationHeader: 'Bearer $token',
        HttpHeaders.acceptHeader: 'application/json',
      })
      ..files.add(
          await http.MultipartFile.fromPath('avatar', file.path));

    final streamed = await _client.send(request);
    final response = await http.Response.fromStream(streamed);
    final data = _decode(response);
    if (data['ok'] != true) {
      throw ApiException(
          (data['message'] ?? 'Avatar upload failed').toString(),
          statusCode: response.statusCode);
    }
    return (data['avatar'] ?? data['avatar_url'] ?? '').toString();
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
    Future<http.Response> Function() request, {
    Duration? timeout,
  }) async {
    try {
      final response =
          await request().timeout(timeout ?? const Duration(seconds: 30));
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
