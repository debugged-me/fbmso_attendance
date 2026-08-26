import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/offline_storage_service.dart';
import '../../../core/services/outbox_service.dart';
import '../domain/attendance_models.dart';

/// Attendance + activities API.
///
/// Reads are cache-first (served from [OfflineStorageService], refreshed in
/// the background). Writes (check-in, consume) funnel through the
/// [OutboxService] so they queue when offline and sync on reconnect — the
/// idempotency key prevents double check-ins on retry.
class AttendanceApi {
  AttendanceApi({http.Client? client, Uuid? uuid})
      : _client = client ?? http.Client(),
        _uuid = uuid ?? const Uuid();

  final http.Client _client;
  final Uuid _uuid;

  static const _cacheActivities = 'activities';
  static const _cacheMyLogs = 'my_logs';

  /// List activities. Returns the cached list immediately when offline.
  Future<List<Activity>> activities({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/activities';
    try {
      final response = await _client.get(
        Uri.parse(url),
        headers: _headers(token),
      );
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['activities'] as List? ?? [])
            .map((e) => Activity.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cacheActivities, list.map((a) => a.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed to load activities').toString());
    } catch (e) {
      // Fall back to cache.
      final cached = await OfflineStorageService.getList(_cacheActivities);
      return cached.map((m) => Activity.fromJson(m)).toList();
    }
  }

  /// Get the current poster mode state (on/off).
  Future<bool> posterMode({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/activities/poster_mode';
    try {
      final response = await _client.get(
        Uri.parse(url),
        headers: _headers(token),
      );
      final data = _decode(response);
      if (data['ok'] == true) {
        return (data['poster_mode'] ?? 'off') == 'on';
      }
      return false;
    } catch (_) {
      return false;
    }
  }

  /// Set poster mode on/off. Admin only.
  Future<bool> setPosterMode({
    required String baseUrl,
    required String token,
    required bool on,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/activities/set_poster_mode';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _headers(token),
        body: jsonEncode({'mode': on ? 'on' : 'off'}),
      );
      final data = _decode(response);
      if (data['ok'] == true) {
        return (data['poster_mode'] ?? 'off') == 'on';
      }
      throw ApiException((data['message'] ?? 'Failed to set poster mode').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Get the poster QR data (check-in URL) for an activity.
  Future<({String checkinUrl, String title, String activityDate, String location, String program})> posterQr({
    required String baseUrl,
    required String token,
    required int activityId,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/activities/poster_qr/$activityId';
    try {
      final response = await _client.get(
        Uri.parse(url),
        headers: _headers(token),
      );
      final data = _decode(response);
      if (data['ok'] == true) {
        return (
          checkinUrl: (data['checkin_url'] ?? '').toString(),
          title: (data['title'] ?? '').toString(),
          activityDate: (data['activity_date'] ?? '').toString(),
          location: (data['location'] ?? '').toString(),
          program: (data['program'] ?? '').toString(),
        );
      }
      throw ApiException((data['message'] ?? 'Failed to get poster QR').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Student's own attendance log. Cache-first when offline.
  Future<List<AttendanceLog>> myLogs({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/attendance/my_logs';
    try {
      final response = await _client.get(
        Uri.parse(url),
        headers: _headers(token),
      );
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['rows'] as List? ?? [])
            .map((e) => AttendanceLog.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cacheMyLogs, list.map((l) => l.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed to load logs').toString());
    } catch (e) {
      final cached = await OfflineStorageService.getList(_cacheMyLogs);
      return cached.map((m) => AttendanceLog.fromJson(m)).toList();
    }
  }

  /// Student self check-in/out. Queues to the outbox when offline.
  ///
  /// Returns the [CheckResult] immediately when online; when offline, returns
  /// a synthetic "queued" result so the UI can confirm the action.
  Future<CheckResult> selfCheckin({
    required String baseUrl,
    required String token,
    required int activityId,
    required String direction,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/attendance/checkin/$activityId';
    final idemKey = _uuid.v4();

    // When this scan actually happened. The server judges the activity's
    // auto-close window against it, so a scan queued inside the window is still
    // accepted if the outbox only syncs after the window has closed.
    final occurredAt = DateTime.now().toUtc().toIso8601String();

    // Try online first for immediate feedback.
    if (await _isOnline()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._headers(token), 'X-Idempotency-Key': idemKey},
          body: jsonEncode({
            'direction': direction,
            'client_submitted_at': occurredAt,
          }),
        );
        final data = _decode(response);
        return CheckResult.fromJson(data);
      } catch (_) {
        // Fall through to queue.
      }
    }

    // Offline (or network failed) → queue.
    await OutboxService.enqueue(
      operation: 'self_checkin',
      url: url,
      idemKey: idemKey,
      token: token,
      payload: {
        'direction': direction,
        'client_submitted_at': occurredAt,
      },
    );
    return CheckResult(
      ok: true,
      mode: 'queued',
      message: 'Saved offline — will sync when you reconnect.',
    );
  }

  /// Scanner consumes a student QR token. Queues when offline.
  Future<CheckResult> consume({
    required String baseUrl,
    required String token,
    required int activityId,
    required String qrToken,
    String direction = 'auto',
    String remarks = '',
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/attendance/consume';
    final idemKey = _uuid.v4();
    final occurredAt = DateTime.now().toUtc().toIso8601String();

    if (await _isOnline()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._headers(token), 'X-Idempotency-Key': idemKey},
          body: jsonEncode({
            'activity_id': activityId,
            'token': qrToken,
            'direction': direction,
            'client_submitted_at': occurredAt,
            if (remarks.isNotEmpty) 'remarks': remarks,
          }),
        );
        final data = _decode(response);
        return CheckResult.fromJson(data);
      } catch (_) {
        // Fall through to queue.
      }
    }

    await OutboxService.enqueue(
      operation: 'scanner_consume',
      url: url,
      idemKey: idemKey,
      token: token,
      payload: {
        'activity_id': activityId,
        'token': qrToken,
        'direction': direction,
        'client_submitted_at': occurredAt,
        if (remarks.isNotEmpty) 'remarks': remarks,
      },
    );
    return CheckResult(
      ok: true,
      mode: 'queued',
      message: 'Saved offline — will sync when you reconnect.',
    );
  }

  /// Per-activity attendance log (staff). Returns raw rows.
  Future<({int total, List<Map<String, dynamic>> rows})> activityLogs({
    required String baseUrl,
    required String token,
    required int activityId,
    int limit = 0,
    int offset = 0,
    String search = '',
  }) async {
    final params = <String, String>{
      if (limit > 0) 'limit': '$limit',
      if (offset > 0) 'offset': '$offset',
      if (search.isNotEmpty) 'search': search,
    };
    final qs = params.entries
        .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
        .join('&');
    final url =
        '${_normalize(baseUrl)}/api/mobile/attendance/logs/$activityId${qs.isNotEmpty ? '?$qs' : ''}';
    try {
      final response = await _client.get(
        Uri.parse(url),
        headers: _headers(token),
      );
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['rows'] as List? ?? [])
            .map((e) => e as Map<String, dynamic>)
            .toList();
        return (
          total: (data['total'] as num?)?.toInt() ?? list.length,
          rows: list,
        );
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Activity management (staff) ────────────────────────────────────────

  /// Create a new activity. Staff only.
  Future<({bool ok, String message, Activity? activity})> createActivity({
    required String baseUrl,
    required String token,
    required String title,
    required String activityDate,
    String startTime = '',
    String endTime = '',
    String location = '',
    String program = '',
    String description = '',
    ActivityStatus status = ActivityStatus.open,
    bool autoClose = true,
    int graceMinutes = 15,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/activities/create';
    final idemKey = _uuid.v4();
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: {..._headers(token), 'X-Idempotency-Key': idemKey},
        body: jsonEncode({
          'title': title,
          'activity_date': activityDate,
          if (startTime.isNotEmpty) 'start_time': startTime,
          if (endTime.isNotEmpty) 'end_time': endTime,
          if (location.isNotEmpty) 'location': location,
          if (program.isNotEmpty) 'program': program,
          if (description.isNotEmpty) 'description': description,
          'status': status.value,
          'auto_close': autoClose,
          'grace_minutes': graceMinutes,
        }),
      );
      final data = _decode(response);
      final act = data['activity'] != null
          ? Activity.fromJson(data['activity'] as Map<String, dynamic>)
          : null;
      return (
        ok: data['ok'] == true,
        message: (data['message'] ?? '').toString(),
        activity: act,
      );
    } on ApiException catch (e) {
      return (ok: false, message: e.message, activity: null);
    } catch (e) {
      return (ok: false, message: e.toString(), activity: null);
    }
  }

  /// Update an existing activity. Staff only.
  Future<({bool ok, String message, Activity? activity})> updateActivity({
    required String baseUrl,
    required String token,
    required int activityId,
    Map<String, dynamic> fields = const {},
  }) async {
    final url =
        '${_normalize(baseUrl)}/api/mobile/activities/update/$activityId';
    final idemKey = _uuid.v4();
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: {..._headers(token), 'X-Idempotency-Key': idemKey},
        body: jsonEncode(fields),
      );
      final data = _decode(response);
      final act = data['activity'] != null
          ? Activity.fromJson(data['activity'] as Map<String, dynamic>)
          : null;
      return (
        ok: data['ok'] == true,
        message: (data['message'] ?? '').toString(),
        activity: act,
      );
    } on ApiException catch (e) {
      return (ok: false, message: e.message, activity: null);
    } catch (e) {
      return (ok: false, message: e.toString(), activity: null);
    }
  }

  /// Flip an activity's manual open/closed override without opening the form.
  ///
  /// Reopening one the clock already closed also turns auto-close off, otherwise
  /// the time window would immediately close it again — same rule the web UI uses.
  Future<({bool ok, String message, Activity? activity})> setActivityStatus({
    required String baseUrl,
    required String token,
    required int activityId,
    required ActivityStatus status,
    bool liftAutoClose = false,
  }) {
    return updateActivity(
      baseUrl: baseUrl,
      token: token,
      activityId: activityId,
      fields: {
        'status': status.value,
        if (liftAutoClose) 'auto_close': false,
      },
    );
  }

  /// Delete an activity. Staff only.
  Future<({bool ok, String message})> deleteActivity({
    required String baseUrl,
    required String token,
    required int activityId,
  }) async {
    final url =
        '${_normalize(baseUrl)}/api/mobile/activities/delete/$activityId';
    final idemKey = _uuid.v4();
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: {..._headers(token), 'X-Idempotency-Key': idemKey},
      );
      final data = _decode(response);
      return (
        ok: data['ok'] == true,
        message: (data['message'] ?? '').toString(),
      );
    } on ApiException catch (e) {
      return (ok: false, message: e.message);
    } catch (e) {
      return (ok: false, message: e.toString());
    }
  }

  // ─── internals ──────────────────────────────────────────────────────────

  Map<String, String> _headers(String token) => {
        HttpHeaders.acceptHeader: 'application/json',
        HttpHeaders.contentTypeHeader: 'application/json; charset=utf-8',
        HttpHeaders.authorizationHeader: 'Bearer $token',
      };

  String _normalize(String baseUrl) =>
      baseUrl.replaceFirst(RegExp(r'/+$'), '');

  Map<String, dynamic> _decode(http.Response response) {
    final body = response.body;
    if (body.isEmpty) {
      throw ApiException('Empty response', statusCode: response.statusCode);
    }
    final trimmed = body.trimLeft();
    if (trimmed.startsWith('<')) {
      throw ApiException('Server returned HTML', statusCode: response.statusCode);
    }
    try {
      return jsonDecode(body) as Map<String, dynamic>;
    } catch (e) {
      throw ApiException('Malformed JSON', statusCode: response.statusCode);
    }
  }

  Future<bool> _isOnline() => ConnectivityService.isConnected();
}
