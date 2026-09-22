import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/device_identity.dart';
import '../../../core/services/or_block_service.dart';
import '../../../core/services/outbox_service.dart';
import '../domain/accounting_models.dart';

/// Cashier accounting API — mirrors the web Accounting controller
/// (Admin + Cashier only on the server; everything else gets 403).
///
/// Writes send X-Idempotency-Key, which on the server plays the role the
/// web's payment_submit_token plays — a retried request replays the first
/// response instead of double-posting a payment.
class AccountingApi {
  AccountingApi({http.Client? client, Uuid? uuid})
      : _client = client ?? http.Client(),
        _uuid = uuid ?? const Uuid();

  final http.Client _client;
  final Uuid _uuid;

  // ─── Dashboard (Page::accounting) ───────────────────────────────────────

  Future<AccountingDashboard> dashboard({
    required String baseUrl,
    required String token,
  }) async {
    final data = await _get(baseUrl, token, 'accounting/dashboard');
    return AccountingDashboard.fromJson(data);
  }

  // ─── Payment Entry (Accounting::Payment) ────────────────────────────────

  /// Form context: payable students, fee templates, payment dates, today's
  /// payments and the next O.R. number — one round-trip like the web GET.
  Future<({
    String today,
    String sem,
    String sy,
    String nextOrNumber,
    List<PayableStudent> students,
    List<FeeTemplate> fees,
    List<String> paymentDates,
    List<PaymentEntry> recentPayments,
  })> paymentContext({
    required String baseUrl,
    required String token,
  }) async {
    final data = await _get(baseUrl, token, 'accounting/payment/context');
    return (
      today: (data['today'] ?? '').toString(),
      sem: (data['sem'] ?? '').toString(),
      sy: (data['sy'] ?? '').toString(),
      nextOrNumber: (data['next_or_number'] ?? '').toString(),
      students: ((data['students'] as List?) ?? [])
          .map((e) => PayableStudent.fromJson(e as Map<String, dynamic>))
          .toList(),
      fees: ((data['fees'] as List?) ?? [])
          .map((e) => FeeTemplate.fromJson(e as Map<String, dynamic>))
          .toList(),
      paymentDates: ((data['payment_dates'] as List?) ?? [])
          .map((e) => e.toString())
          .toList(),
      recentPayments: ((data['recent_payments'] as List?) ?? [])
          .map((e) => PaymentEntry.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  /// Payments for a date, or all when [date] is 'all' (web "Payments on").
  Future<List<PaymentEntry>> payments({
    required String baseUrl,
    required String token,
    String date = '',
  }) async {
    final data = await _get(
        baseUrl, token, 'accounting/payments${date.isNotEmpty ? '?date=${Uri.encodeComponent(date)}' : ''}');
    return ((data['payments'] as List?) ?? [])
        .map((e) => PaymentEntry.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// Record a payment — same fields as the web Payment form.
  ///
  /// Offline, the O.R. number comes from the block this device reserved while
  /// it still had signal, so the cashier can hand over a real receipt number
  /// at the window. [queued] says the payment has not reached the server yet.
  Future<({String orNumber, int paymentId, bool queued})> paymentCreate({
    required String baseUrl,
    required String token,
    required String studentNumber,
    required String description,
    required double amount,
    required String pDate,
    String paymentType = 'Cash',
    String checkNumber = '',
    String bank = '',
    String refNo = '',
  }) async {
    final clientPaymentId = _uuid.v4();
    final deviceId = await DeviceIdentity.id();

    final payload = <String, dynamic>{
      'StudentNumber': studentNumber,
      'description': description,
      'Amount': amount,
      'PDate': pDate,
      'PaymentType': paymentType,
      'CheckNumber': checkNumber,
      'Bank': bank,
      'refNo': refNo,
      'client_payment_id': clientPaymentId,
      'device_id': deviceId,
    };

    if (await ConnectivityService.isConnected()) {
      try {
        final data = await _post(
            baseUrl, token, 'accounting/payment/create', payload,
            idemKey: clientPaymentId);
        return (
          orNumber: (data['or_number'] ?? '').toString(),
          paymentId: (data['payment_id'] as num?)?.toInt() ?? 0,
          queued: false,
        );
      } on ApiException {
        // A refusal from the server (validation, permissions) is a real
        // answer, not a connectivity problem — do not queue it.
        rethrow;
      } catch (_) {
        // Network failure: fall through and queue.
      }
    }

    final reserved = await OrBlockService.take(pDate);
    if (reserved == null) {
      throw ApiException(
          'No signal and no reserved O.R. numbers left for $pDate. '
          'Reconnect once to reserve more before taking payments offline.');
    }
    payload['or_number'] = reserved;

    await OutboxService.enqueue(
      operation: 'payment_create',
      url: '${_n(baseUrl)}/api/mobile/accounting/payment/create',
      idemKey: clientPaymentId,
      token: token,
      payload: payload,
      refId: clientPaymentId,
    );

    return (orNumber: reserved, paymentId: 0, queued: true);
  }

  /// Claim receipt numbers for offline use. Call while the cashier screen is
  /// open and online; it is a no-op when the device still has plenty.
  Future<void> ensureOrNumbers({
    required String baseUrl,
    required String token,
    required String date,
  }) =>
      OrBlockService.ensureStocked(
          baseUrl: baseUrl, token: token, date: date);

  /// Reserved receipt numbers still available offline for [date].
  Future<int> remainingOrNumbers(String date) =>
      OrBlockService.remaining(date);

  /// Void a payment — web deletePayment: VALID Student's-Account rows only,
  /// ledger recompute + audit row on the server.
  Future<void> paymentDelete({
    required String baseUrl,
    required String token,
    required int id,
  }) async {
    await _post(baseUrl, token, 'accounting/payment/delete', {'id': id});
  }

  // ─── Payment Activity Log (Accounting::paymentAuditLog) ─────────────────

  Future<List<PaymentAuditEntry>> paymentAuditLog({
    required String baseUrl,
    required String token,
  }) async {
    final data = await _get(baseUrl, token, 'accounting/payment/audit-log');
    return ((data['entries'] as List?) ?? [])
        .map((e) => PaymentAuditEntry.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  // ─── Partial Payments (Accounting::partialPayments) ─────────────────────

  Future<({
    String sem,
    String sy,
    List<PartialPaymentRow> rows,
    double totalOutstanding,
    int studentCount,
  })> partialPayments({
    required String baseUrl,
    required String token,
  }) async {
    final data = await _get(baseUrl, token, 'accounting/partial-payments');
    return (
      sem: (data['sem'] ?? '').toString(),
      sy: (data['sy'] ?? '').toString(),
      rows: ((data['rows'] as List?) ?? [])
          .map((e) => PartialPaymentRow.fromJson(e as Map<String, dynamic>))
          .toList(),
      totalOutstanding:
          (data['total_outstanding'] as num?)?.toDouble() ?? 0,
      studentCount: (data['student_count'] as num?)?.toInt() ?? 0,
    );
  }

  // ─── Collection Report (Accounting::collectionReport) ───────────────────

  Future<({
    List<PaymentEntry> rows,
    double total,
    int count,
    String from,
    String to,
  })> collectionReport({
    required String baseUrl,
    required String token,
    String from = '',
    String to = '',
    String term = '',
  }) async {
    final params = <String, String>{
      if (from.isNotEmpty) 'from': from,
      if (to.isNotEmpty) 'to': to,
      if (term.isNotEmpty) 'term': term,
    };
    final qs = params.entries
        .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
        .join('&');
    final data = await _get(
        baseUrl, token, 'accounting/collection-report${qs.isNotEmpty ? '?$qs' : ''}');
    return (
      rows: ((data['rows'] as List?) ?? [])
          .map((e) => PaymentEntry.fromJson(e as Map<String, dynamic>))
          .toList(),
      total: (data['total'] as num?)?.toDouble() ?? 0,
      count: (data['count'] as num?)?.toInt() ?? 0,
      from: (data['from'] ?? '').toString(),
      to: (data['to'] ?? '').toString(),
    );
  }

  Future<List<TermOption>> terms({
    required String baseUrl,
    required String token,
  }) async {
    final data = await _get(baseUrl, token, 'accounting/terms');
    return ((data['terms'] as List?) ?? [])
        .map((e) => TermOption.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  // ─── Ledger (Accounting::ledger) ────────────────────────────────────────

  Future<({
    List<LedgerRow> rows,
    double gross,
    double spent,
    double net,
  })> ledger({
    required String baseUrl,
    required String token,
    String from = '',
    String to = '',
  }) async {
    final params = <String, String>{
      if (from.isNotEmpty) 'from': from,
      if (to.isNotEmpty) 'to': to,
    };
    final qs = params.entries
        .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
        .join('&');
    final data = await _get(
        baseUrl, token, 'accounting/ledger${qs.isNotEmpty ? '?$qs' : ''}');
    return (
      rows: ((data['rows'] as List?) ?? [])
          .map((e) => LedgerRow.fromJson(e as Map<String, dynamic>))
          .toList(),
      gross: (data['gross'] as num?)?.toDouble() ?? 0,
      spent: (data['spent'] as num?)?.toDouble() ?? 0,
      net: (data['net'] as num?)?.toDouble() ?? 0,
    );
  }

  // ─── Expenses Report (Accounting::expenseSGenerate) ─────────────────────

  Future<({
    List<ExpenseReportRow> rows,
    double total,
  })> expensesReport({
    required String baseUrl,
    required String token,
    String category = '',
    String from = '',
    String to = '',
  }) async {
    final params = <String, String>{
      if (category.isNotEmpty) 'category': category,
      if (from.isNotEmpty) 'from': from,
      if (to.isNotEmpty) 'to': to,
    };
    final qs = params.entries
        .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
        .join('&');
    final data = await _get(
        baseUrl, token, 'accounting/expenses-report${qs.isNotEmpty ? '?$qs' : ''}');
    return (
      rows: ((data['rows'] as List?) ?? [])
          .map((e) => ExpenseReportRow.fromJson(e as Map<String, dynamic>))
          .toList(),
      total: (data['total'] as num?)?.toDouble() ?? 0,
    );
  }

  // ─── Fees Setup (Accounting::course_setUp) ──────────────────────────────

  Future<List<FeeTemplate>> fees({
    required String baseUrl,
    required String token,
  }) async {
    final data = await _get(baseUrl, token, 'accounting/fees');
    return ((data['fees'] as List?) ?? [])
        .map((e) => FeeTemplate.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<void> feeCreate({
    required String baseUrl,
    required String token,
    required String description,
    required double amount,
    String feesType = '',
  }) async {
    await _post(baseUrl, token, 'accounting/fees/create', {
      'description': description,
      'amount': amount,
      'fees_type': feesType,
    });
  }

  Future<void> feeUpdate({
    required String baseUrl,
    required String token,
    required int id,
    required String description,
    required double amount,
    String feesType = '',
  }) async {
    await _post(baseUrl, token, 'accounting/fees/update', {
      'id': id,
      'description': description,
      'amount': amount,
      'fees_type': feesType,
    });
  }

  Future<void> feeDelete({
    required String baseUrl,
    required String token,
    required int id,
  }) async {
    await _post(baseUrl, token, 'accounting/fees/delete', {'id': id});
  }

  // ─── Transport ──────────────────────────────────────────────────────────

  String _n(String baseUrl) =>
      baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;

  Map<String, String> _h(String token) => {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      };

  Map<String, dynamic> _decode(http.Response r) {
    try {
      final d = jsonDecode(r.body);
      return d is Map<String, dynamic> ? d : <String, dynamic>{};
    } catch (_) {
      return <String, dynamic>{};
    }
  }

  Future<Map<String, dynamic>> _get(
      String baseUrl, String token, String path) async {
    final response = await _client.get(
      Uri.parse('${_n(baseUrl)}/api/mobile/$path'),
      headers: _h(token),
    );
    final data = _decode(response);
    if (data['ok'] == true) return data;
    throw ApiException(
        (data['message'] ?? 'Request failed (${response.statusCode})')
            .toString());
  }

  Future<Map<String, dynamic>> _post(String baseUrl, String token, String path,
      Map<String, dynamic> payload, {String? idemKey}) async {
    final response = await _client.post(
      Uri.parse('${_n(baseUrl)}/api/mobile/$path'),
      headers: {..._h(token), 'X-Idempotency-Key': idemKey ?? _uuid.v4()},
      body: jsonEncode(payload),
    );
    final data = _decode(response);
    if (data['ok'] == true) return data;
    throw ApiException(
        (data['message'] ?? 'Request failed (${response.statusCode})')
            .toString());
  }
}
