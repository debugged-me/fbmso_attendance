import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/offline_storage_service.dart';
import '../../../core/services/outbox_service.dart';
import '../../../core/services/roster_service.dart';
import '../../../core/services/scan_ledger_service.dart';
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

  /// Committee dashboard stats — mirrors Page::committee on the web
  /// (open/total activities, today's scans, 14-day trend, recent scans).
  /// Committee-only on the server; other roles get 403.
  Future<CommitteeDashboard> committeeDashboard({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/committee/dashboard';
    final response = await _client.get(
      Uri.parse(url),
      headers: _headers(token),
    );
    final data = _decode(response);
    if (data['ok'] == true) {
      return CommitteeDashboard.fromJson(data);
    }
    throw ApiException((data['message'] ?? 'Failed to load').toString());
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

  /// Scanner consumes a student QR token.
  ///
  /// The QR is resolved against the on-device roster first, so with no signal
  /// the operator still sees who was scanned and whether it is a repeat. The
  /// server stays authoritative: a local answer is provisional until it syncs.
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
    final scannedAt = DateTime.now();
    final occurredAt = scannedAt.toUtc().toIso8601String();

    final match = await RosterService.resolve(activityId, qrToken);
    final meta = await RosterService.metaFor(activityId);
    final online = await _isOnline();

    // Only a COMPLETE snapshot can prove a code is not a student QR. With a
    // partial one, an unmatched code may simply not have downloaded yet, so
    // the scan is queued instead of the student being turned away.
    if (match == null && meta != null && meta.complete) {
      return const CheckResult(
        ok: false,
        mode: 'unknown_qr',
        message: 'Not a student QR for this activity.',
      );
    }

    LocalScanDecision? decision;
    if (match != null) {
      decision = await ScanLedgerService.record(
        activityId: activityId,
        studentNumber: match.studentNumber,
        clientScanId: idemKey,
        sessions: meta?.sessions ?? const {},
        at: scannedAt,
      );

      if (decision.isDuplicate) {
        return CheckResult(
          ok: true,
          mode: 'duplicate',
          studentNumber: match.studentNumber,
          session: decision.session,
          student: match.toStudentPayload(),
          message: decision.previousAt == null
              ? 'Already scanned.'
              : 'Already scanned at ${_clock(decision.previousAt!)}.',
        );
      }
    }

    // Resolved locally so a queued in/out pair replays in the right order.
    final effectiveDirection = decision?.direction ?? direction;
    final payload = {
      'activity_id': activityId,
      'token': qrToken,
      'direction': effectiveDirection,
      'client_submitted_at': occurredAt,
      'client_scan_id': idemKey,
      if (remarks.isNotEmpty) 'remarks': remarks,
    };

    if (online) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._headers(token), 'X-Idempotency-Key': idemKey},
          body: jsonEncode(payload),
        );
        final data = _decode(response);
        await ScanLedgerService.markSynced(idemKey, data);
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
      payload: payload,
      refId: idemKey,
    );

    return CheckResult(
      ok: true,
      mode: decision?.mode ?? 'queued',
      provisional: true,
      studentNumber: match?.studentNumber,
      session: decision?.session,
      student: match?.toStudentPayload(),
      message: 'Saved offline — will sync when you reconnect.',
    );
  }

  static String _clock(DateTime t) {
    final h = t.hour % 12 == 0 ? 12 : t.hour % 12;
    final m = t.minute.toString().padLeft(2, '0');
    return '$h:$m ${t.hour < 12 ? 'AM' : 'PM'}';
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

  /// CSV export of an activity's attendance log — web
  /// AttendanceLogs/export_csv. Returns the raw CSV body.
  Future<String> exportLogsCsv({
    required String baseUrl,
    required String token,
    required int activityId,
  }) async {
    final url =
        '${_normalize(baseUrl)}/api/mobile/attendance/export_csv/$activityId';
    final response = await _client.get(
      Uri.parse(url),
      headers: _headers(token),
    );
    if (response.statusCode == 200) return response.body;
    try {
      final data = _decode(response);
      throw ApiException((data['message'] ?? 'Export failed').toString());
    } catch (_) {
      throw ApiException('Export failed (${response.statusCode})');
    }
  }

  // ─── Activity management (staff) ────────────────────────────────────────

  /// Program choices for the activity form — same `course_table`
  /// CourseDescription list the web create page shows.
  Future<List<String>> activityPrograms({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/activities/programs';
    final response = await _client.get(Uri.parse(url), headers: _headers(token));
    final data = _decode(response);
    if (data['ok'] == true) {
      return (data['programs'] as List? ?? []).map((e) => e.toString()).toList();
    }
    throw ApiException((data['message'] ?? 'Failed to load programs').toString());
  }

  /// Majors for a program — mirrors web `activities/majors?program=…`.
  Future<List<String>> activityMajors({
    required String baseUrl,
    required String token,
    required String program,
  }) async {
    final url =
        '${_normalize(baseUrl)}/api/mobile/activities/majors?program=${Uri.encodeComponent(program)}';
    final response = await _client.get(Uri.parse(url), headers: _headers(token));
    final data = _decode(response);
    if (data['ok'] == true) {
      return (data['majors'] as List? ?? []).map((e) => e.toString()).toList();
    }
    throw ApiException((data['message'] ?? 'Failed to load majors').toString());
  }

  /// Create a new activity. Staff only.
  ///
  /// [sessions] carries the am/pm/eve windows exactly like the web form —
  /// the server stores them in meta.sessions and derives start/end from
  /// earliest-in / latest-out. When [sessions] is empty, [startTime]/[endTime]
  /// are used directly (all-day style, same as web with no windows filled).
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
    ActivitySessions sessions = ActivitySessions.empty,
  }) async {
    final url = '${_normalize(baseUrl)}/api/mobile/activities/create';
    final idemKey = _uuid.v4();
    final payload = {
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
      if (sessions.isNotEmpty) 'sessions': sessions.toJson(),
    };

    if (await _isOnline()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._headers(token), 'X-Idempotency-Key': idemKey},
          body: jsonEncode(payload),
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
      } catch (_) {
        // Fall through to queue.
      }
    }

    await OutboxService.enqueue(
      operation: 'activity_create',
      url: url,
      idemKey: idemKey,
      token: token,
      payload: payload,
    );

    // The activity id is assigned by the server, so there is nothing to scan
    // against until this syncs — say so plainly rather than implying it exists.
    return (
      ok: true,
      message: 'Saved offline. The activity will appear — and become '
          'scannable — once you reconnect.',
      activity: null,
    );
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

    if (await _isOnline()) {
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
      } catch (_) {
        // Fall through to queue.
      }
    }

    await OutboxService.enqueue(
      operation: 'activity_update',
      url: url,
      idemKey: idemKey,
      token: token,
      payload: fields,
    );
    return (
      ok: true,
      message: 'Saved offline — will sync when you reconnect.',
      activity: null,
    );
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

    if (!await _isOnline()) {
      await OutboxService.enqueue(
        operation: 'activity_delete',
        url: url,
        idemKey: idemKey,
        token: token,
      );
      return (
        ok: true,
        message: 'Deletion saved offline — will sync when you reconnect.',
      );
    }

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
