import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../auth/domain/app_session.dart';
import '../data/accounting_api.dart';
import '../domain/accounting_models.dart';

/// Payment Activity Log — web: Accounting::paymentAuditLog. Who edited or
/// deleted a payment, with the amount and O.R. it touched.
class PaymentAuditLogScreen extends StatefulWidget {
  const PaymentAuditLogScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<PaymentAuditLogScreen> createState() => _PaymentAuditLogScreenState();
}

class _PaymentAuditLogScreenState extends State<PaymentAuditLogScreen> {
  late final AccountingApi _api;
  List<PaymentAuditEntry> _entries = [];
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
      final list = await _api.paymentAuditLog(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _entries = list;
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
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(32),
                    child: AppEmptyState(
            icon: Icons.cloud_off_rounded,
            title: 'Failed to load',
            subtitle: _error,
            action: 'Retry',
            onAction: _load,
          ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                    itemCount: _entries.length + 1,
                    itemBuilder: (context, i) {
                      if (i == 0) {
                        return AppPageHeader(
                          title: 'Payment Activity Log',
                          icon: Icons.history_rounded,
                          subtitle:
                              '${_entries.length} entr${_entries.length == 1 ? 'y' : 'ies'} · edits & deletions',
                        );
                      }
                      if (_entries.isEmpty) {
                        return const Padding(
                          padding: EdgeInsets.only(top: 60),
                          child: AppEmptyState(
                            icon: Icons.history_rounded,
                            title: 'No activity yet',
                            subtitle:
                                'Payment edits and deletions appear here.',
                          ),
                        );
                      }
                      return _AuditTile(entry: _entries[i - 1]);
                    },
                  ),
                ),
    );
  }
}

class _AuditTile extends StatelessWidget {
  const _AuditTile({required this.entry});

  final PaymentAuditEntry entry;

  @override
  Widget build(BuildContext context) {
    final isDelete = entry.action.toLowerCase() == 'delete';
    final tone = isDelete ? AppInk.critical : AppInk.caution;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: tone.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                isDelete
                    ? Icons.delete_outline_rounded
                    : Icons.edit_outlined,
                color: tone,
                size: 19,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          entry.studentName,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: AppInk.heading,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        '₱${entry.amount.toStringAsFixed(2)}',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: tone,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 3),
                  Text(
                    '${entry.description} · O.R. ${entry.orNumber}',
                    style:
                        const TextStyle(fontSize: 12, color: AppInk.muted),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: tone.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          entry.action.toUpperCase(),
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.4,
                            color: tone,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '${entry.changedBy} · ${entry.changedAt}',
                          style: const TextStyle(
                              fontSize: 11, color: AppInk.muted),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
