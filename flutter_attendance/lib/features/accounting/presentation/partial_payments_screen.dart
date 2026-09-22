import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../auth/domain/app_session.dart';
import '../data/accounting_api.dart';
import '../domain/accounting_models.dart';

/// Partial Payments — web: Accounting::partialPayments. Per (student, fee)
/// balance remaining for the active term, for follow-up.
class PartialPaymentsScreen extends StatefulWidget {
  const PartialPaymentsScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<PartialPaymentsScreen> createState() =>
      _PartialPaymentsScreenState();
}

class _PartialPaymentsScreenState extends State<PartialPaymentsScreen> {
  late final AccountingApi _api;
  List<PartialPaymentRow> _rows = [];
  String _sem = '';
  String _sy = '';
  double _totalOutstanding = 0;
  int _studentCount = 0;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = AccountingApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await _api.partialPayments(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _rows = data.rows;
        _sem = data.sem;
        _sy = data.sy;
        _totalOutstanding = data.totalOutstanding;
        _studentCount = data.studentCount;
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
    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      body: _loading
          ? const ListSkeleton(itemCount: 6)
          : _error != null
              ? ListView(
                  children: [
                    const SizedBox(height: 80),
                    AppEmptyState(
                      icon: Icons.cloud_off_rounded,
                      title: 'Failed to load',
                      subtitle: _error,
                      action: 'Retry',
                      onAction: _load,
                    ),
                  ],
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                    itemCount: _rows.isEmpty ? 2 : _rows.length + 1,
                    itemBuilder: (context, i) {
                      if (i == 0) {
                        return AppPageHeader(
                          title: 'Partial Payments',
                          icon: Icons.pie_chart_outline_rounded,
                          iconColor: AppInk.caution,
                          subtitle:
                              '₱${_totalOutstanding.toStringAsFixed(2)} outstanding · $_studentCount students · $_sem $_sy',
                        );
                      }
                      if (_rows.isEmpty) {
                        return const Padding(
                          padding: EdgeInsets.only(top: 60),
                          child: AppEmptyState(
                            icon: Icons.check_circle_outline_rounded,
                            title: 'No partial payments',
                            subtitle:
                                'Everyone is fully paid this term.',
                          ),
                        );
                      }
                      return _PartialRow(row: _rows[i - 1]);
                    },
                  ),
                ),
    );
  }
}

class _PartialRow extends StatelessWidget {
  const _PartialRow({required this.row});

  final PartialPaymentRow row;

  @override
  Widget build(BuildContext context) {
    final progress =
        row.fullAmount > 0 ? (row.paidAmount / row.fullAmount) : 0.0;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        row.studentName,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: AppInk.heading,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${row.studentNumber} · ${row.description}',
                        style: const TextStyle(
                            fontSize: 12, color: AppInk.muted),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  '₱${row.outstanding.toStringAsFixed(2)} left',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: AppInk.caution,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: progress.clamp(0.0, 1.0),
                minHeight: 6,
                backgroundColor: AppInk.rule,
                valueColor:
                    const AlwaysStoppedAnimation<Color>(AppInk.accent),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              '₱${row.paidAmount.toStringAsFixed(2)} of ₱${row.fullAmount.toStringAsFixed(2)} paid'
              '${row.lastPaymentDate.isNotEmpty ? ' · last ${row.lastPaymentDate}' : ''}',
              style: const TextStyle(fontSize: 11, color: AppInk.muted),
            ),
          ],
        ),
      ),
    );
  }
}
