import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/offline_storage_service.dart';
import '../../../core/services/outbox_service.dart';
import '../domain/student_models.dart';

/// Student module API: profile, my QR, payments.
///
/// Reads are cache-first. QR issue/revoke routes through the outbox when
/// offline.
class StudentApi {
  StudentApi({http.Client? client, Uuid? uuid})
      : _client = client ?? http.Client(),
        _uuid = uuid ?? const Uuid();

  final http.Client _client;
  final Uuid _uuid;

  static const _cacheProfile = 'student_profile';
  static const _cacheQr = 'student_qr';
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

  /// Flagged-account state — never cached, fails soft to "not flagged" so a
  /// hiccup never hides or fabricates a hold.
  Future<FlagStatus> flagStatus({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/student/status';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) return FlagStatus.fromJson(data);
    } catch (_) {}
    return FlagStatus.fromJson(const {'is_flagged': false});
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
