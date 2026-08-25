import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Finance / Accounting screen. Shows the student's payment records with a
/// summary of valid and total payments at the top. Cache-first so it renders
/// even with no signal.
class FinanceScreen extends StatefulWidget {
  const FinanceScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<FinanceScreen> createState() => _FinanceScreenState();
}

class _FinanceScreenState extends State<FinanceScreen> {
  late final StudentApi _api;
  List<Payment> _payments = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = StudentApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await _api.payments(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _payments = list;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final validPayments = _payments.where((p) => p.isValid).toList();
    final totalValid = validPayments.fold<double>(0, (s, p) => s + p.amount);
    final totalAll = _payments.fold<double>(0, (s, p) => s + p.amount);

    return AppScaffold(
      title: 'Finance',
      showBackButton: false,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? _ErrorView(message: _error!, onRetry: _load)
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
                          children: [
                            // ── Summary cards ────────────────────────────
                            Row(
                              children: [
                                Expanded(
                                  child: _SummaryCard(
                                    label: 'Valid Payments',
                                    value: _formatAmount(totalValid),
                                    tone: AppInk.positive,
                                    icon: Icons.check_circle_outline,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: _SummaryCard(
                                    label: 'Total Payments',
                                    value: _formatAmount(totalAll),
                                    tone: AppInk.accent,
                                    icon: Icons.account_balance_wallet_outlined,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 24),

                            // ── Payment records ──────────────────────────
                            AppSectionHeader(
                              title:
                                  '${_payments.length} Payment${_payments.length == 1 ? '' : 's'}',
                            ),
                            if (_payments.isEmpty)
                              const Padding(
                                padding: EdgeInsets.only(top: 60),
                                child: AppEmptyState(
                                  icon: Icons.receipt_long_outlined,
                                  title: 'No payment records',
                                  subtitle:
                                      'Your payment history will appear here.',
                                ),
                              )
                            else
                              ..._payments.map((p) => _PaymentCard(payment: p)),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatAmount(double value) {
    if (value == value.roundToDouble()) {
      return value.toStringAsFixed(0);
    }
    return value.toStringAsFixed(2);
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.label,
    required this.value,
    required this.tone,
    required this.icon,
  });

  final String label;
  final String value;
  final Color tone;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return AppCard.elevated(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: tone.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 20, color: tone),
          ),
          const SizedBox(height: 14),
          Text(
            label.toUpperCase(),
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: AppInk.muted,
              letterSpacing: 0.6,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: AppInk.heading,
            ),
          ),
        ],
      ),
    );
  }
}

class _PaymentCard extends StatelessWidget {
  const _PaymentCard({required this.payment});
  final Payment payment;

  @override
  Widget build(BuildContext context) {
    final statusColor = payment.isValid ? AppInk.positive : AppInk.caution;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    payment.description.isNotEmpty
                        ? payment.description
                        : 'Payment',
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                      height: 1.3,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(width: 10),
                AppChip(label: payment.orStatus, tone: statusColor),
              ],
            ),
            const SizedBox(height: 12),
            const AppRule(),
            const SizedBox(height: 12),
            Wrap(
              spacing: 16,
              runSpacing: 8,
              children: [
                if (payment.date.isNotEmpty)
                  _MetaItem(
                      icon: Icons.event_rounded, label: 'Date', value: payment.date),
                if (payment.orNumber.isNotEmpty)
                  _MetaItem(
                      icon: Icons.receipt_rounded,
                      label: 'OR #',
                      value: payment.orNumber),
                if (payment.amount > 0)
                  _MetaItem(
                      icon: Icons.payments_outlined,
                      label: 'Amount',
                      value: payment.amount.toStringAsFixed(2)),
                if (payment.paymentType.isNotEmpty)
                  _MetaItem(
                      icon: Icons.account_balance_outlined,
                      label: 'Type',
                      value: payment.paymentType),
                if (payment.collectionSource.isNotEmpty)
                  _MetaItem(
                      icon: Icons.source_outlined,
                      label: 'Source',
                      value: payment.collectionSource),
                if (payment.refNo.isNotEmpty)
                  _MetaItem(
                      icon: Icons.tag_rounded,
                      label: 'Ref',
                      value: payment.refNo),
                if (payment.sy.isNotEmpty)
                  _MetaItem(
                      icon: Icons.school_outlined, label: 'SY', value: payment.sy),
                if (payment.sem.isNotEmpty)
                  _MetaItem(
                      icon: Icons.calendar_view_week_outlined,
                      label: 'Sem',
                      value: payment.sem),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _MetaItem extends StatelessWidget {
  const _MetaItem({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: AppInk.muted),
        const SizedBox(width: 5),
        Text(
          '$label: ',
          style: const TextStyle(
            fontSize: 12.5,
            color: AppInk.muted,
            fontWeight: FontWeight.w500,
          ),
        ),
        Text(
          value,
          style: const TextStyle(
            fontSize: 12.5,
            color: AppInk.heading,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            AppButton(label: 'Retry', onTap: onRetry),
          ],
        ),
      ),
    );
  }
}
