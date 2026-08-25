import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:uuid/uuid.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/offline_storage_service.dart';
import '../../../core/services/outbox_service.dart';
import '../domain/student_models.dart';

/// Student module API: profile, my QR, requirements, grades, enrolled subjects.
///
/// Reads are cache-first. QR issue/revoke and requirement uploads route
/// through the outbox when offline.
class StudentApi {
  StudentApi({http.Client? client, Uuid? uuid})
      : _client = client ?? http.Client(),
        _uuid = uuid ?? const Uuid();

  final http.Client _client;
  final Uuid _uuid;

  static const _cacheProfile = 'student_profile';
  static const _cacheQr = 'student_qr';
  static const _cacheRequirements = 'student_requirements';
  static const _cacheGrades = 'student_grades';
  static const _cacheEnrolled = 'student_enrolled';
  static const _cachePayments = 'student_payments';

  // ─── Profile ────────────────────────────────────────────────────────────

  Future<StudentProfile> profile({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/profile';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true && data['profile'] != null) {
        final p = StudentProfile.fromJson(data['profile'] as Map<String, dynamic>);
        await OfflineStorageService.saveDoc(_cacheProfile, data['profile'] as Map<String, dynamic>);
        return p;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getDoc(_cacheProfile);
      if (cached != null) return StudentProfile.fromJson(cached);
      rethrow;
    }
  }

  // ─── My QR ──────────────────────────────────────────────────────────────

  Future<StudentQr> myQr({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/my_qr';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final qr = StudentQr.fromJson(data);
        await OfflineStorageService.saveDoc(_cacheQr, data);
        return qr;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getDoc(_cacheQr);
      if (cached != null) return StudentQr.fromJson(cached);
      rethrow;
    }
  }

  Future<void> issueQr({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/my_qr/issue';
    final idem = _uuid.v4();
    if (await ConnectivityService.isConnected()) {
      try {
        await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
        );
        return;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'issue_qr',
      url: url,
      idemKey: idem,
      token: token,
    );
  }

  Future<void> revokeQr({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/my_qr/revoke';
    final idem = _uuid.v4();
    if (await ConnectivityService.isConnected()) {
      try {
        await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
        );
        return;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'revoke_qr',
      url: url,
      idemKey: idem,
      token: token,
    );
  }

  // ─── Requirements ───────────────────────────────────────────────────────

  Future<List<Requirement>> requirements({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/requirements';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['requirements'] as List? ?? [])
            .map((e) => Requirement.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cacheRequirements, list.map((r) => r.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getList(_cacheRequirements);
      return cached.map((m) => Requirement.fromJson(m)).toList();
    }
  }

  /// Upload a requirement file. Uses multipart form-data.
  /// When offline, the upload is NOT queued (file uploads can't be serialized
  /// to the outbox easily); the user is told to retry when online.
  Future<bool> uploadRequirement({
    required String baseUrl,
    required String token,
    required int requirementId,
    required XFile file,
  }) async {
    if (!await ConnectivityService.isConnected()) {
      throw ApiException('You are offline. Please reconnect to upload.');
    }

    final url = '${_n(baseUrl)}/api/mobile/student/requirements/upload';
    final idem = _uuid.v4();

    final request = http.MultipartRequest('POST', Uri.parse(url))
      ..headers.addAll({
        HttpHeaders.authorizationHeader: 'Bearer $token',
        'X-Idempotency-Key': idem,
      })
      ..fields['requirement_id'] = requirementId.toString()
      ..files.add(await http.MultipartFile.fromPath('requirement_file', file.path));

    final streamed = await _client.send(request);
    final response = await http.Response.fromStream(streamed);
    final data = _decode(response);
    return data['ok'] == true;
  }

  // ─── Grades ─────────────────────────────────────────────────────────────

  Future<List<Grade>> grades({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/grades';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['grades'] as List? ?? [])
            .map((e) => Grade.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cacheGrades, list.map((g) => g.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getList(_cacheGrades);
      return cached.map((m) => Grade.fromJson(m)).toList();
    }
  }

  // ─── Enrolled subjects (COR) ────────────────────────────────────────────

  Future<({List<EnrolledSubject> subjects, double totalUnits, String sy, String sem})>
      enrolledSubjects({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/enrolled_subjects';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['subjects'] as List? ?? [])
            .map((e) => EnrolledSubject.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveDoc(_cacheEnrolled, data);
        return (
          subjects: list,
          totalUnits: (data['total_units'] as num?)?.toDouble() ?? 0,
          sy: (data['sy'] ?? '').toString(),
          sem: (data['sem'] ?? '').toString(),
        );
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getDoc(_cacheEnrolled);
      if (cached != null) {
        final list = (cached['subjects'] as List? ?? [])
            .map((e) => EnrolledSubject.fromJson(e as Map<String, dynamic>))
            .toList();
        return (
          subjects: list,
          totalUnits: (cached['total_units'] as num?)?.toDouble() ?? 0,
          sy: (cached['sy'] ?? '').toString(),
          sem: (cached['sem'] ?? '').toString(),
        );
      }
      rethrow;
    }
  }

  // ─── Payments ───────────────────────────────────────────────────────────

  Future<List<Payment>> payments({
    required String baseUrl,
    required String token,
    String? sy,
    String? sem,
  }) async {
    var path = '/api/mobile/student/payments';
    final query = <String>[];
    if (sy != null && sy.trim().isNotEmpty) query.add('sy=${sy.trim()}');
    if (sem != null && sem.trim().isNotEmpty) query.add('sem=${sem.trim()}');
    if (query.isNotEmpty) path += '?${query.join('&')}';
    final url = '${_n(baseUrl)}$path';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['payments'] as List? ?? [])
            .map((e) => Payment.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cachePayments, list.map((p) => p.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getList(_cachePayments);
      return cached.map((m) => Payment.fromJson(m)).toList();
    }
  }

  // ─── internals ──────────────────────────────────────────────────────────

  Map<String, String> _h(String token) => {
        HttpHeaders.acceptHeader: 'application/json',
        HttpHeaders.authorizationHeader: 'Bearer $token',
      };

  String _n(String baseUrl) => baseUrl.replaceFirst(RegExp(r'/+$'), '');

  Map<String, dynamic> _decode(http.Response response) {
    final body = response.body;
    if (body.isEmpty) throw ApiException('Empty response', statusCode: response.statusCode);
    if (body.trimLeft().startsWith('<')) {
      throw ApiException('Server returned HTML', statusCode: response.statusCode);
    }
    try {
      return jsonDecode(body) as Map<String, dynamic>;
    } catch (e) {
      throw ApiException('Malformed JSON', statusCode: response.statusCode);
    }
  }
}
