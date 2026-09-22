import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../auth/domain/app_session.dart';
import '../data/accounting_api.dart';
import '../domain/accounting_models.dart';

/// Collection Report — web: Accounting::collectionReport. Date-range +
/// term filter over valid paymentsaccounts rows, with a total.
class CollectionReportScreen extends StatefulWidget {
  const CollectionReportScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<CollectionReportScreen> createState() =>
      _CollectionReportScreenState();
}

class _CollectionReportScreenState extends State<CollectionReportScreen> {
  late final AccountingApi _api;
  List<PaymentEntry> _rows = [];
  List<TermOption> _terms = [];
  double _total = 0;
  String _from = '';
  String _to = '';
  String _term = '';
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = AccountingApi();
    _load();
    _loadTerms();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await _api.collectionReport(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        from: _from,
        to: _to,
        term: _term,
      );
      if (!mounted) return;
      setState(() {
        _rows = data.rows;
        _total = data.total;
        _from = data.from;
        _to = data.to;
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

  Future<void> _loadTerms() async {
    try {
      final terms = await _api.terms(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() => _terms = terms);
    } catch (_) {}
  }

  Future<void> _pickDate(bool isFrom) async {
    final now = DateTime.now();
    final initial = DateTime.tryParse(isFrom ? _from : _to) ?? now;
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(now.year - 5),
      lastDate: DateTime(now.year + 1),
    );
    if (picked == null) return;
    final s =
        '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    setState(() {
      if (isFrom) {
        _from = s;
      } else {
        _to = s;
      }
    });
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      body: Column(
        children: [
          // Filter row — From / To / Term, matching the web filter bar.
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
            child: Row(
              children: [
                Expanded(
                  child: _FilterButton(
                    icon: Icons.event_rounded,
                    label: _from.isEmpty ? 'From' : _from,
                    onTap: () => _pickDate(true),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _FilterButton(
                    icon: Icons.event_rounded,
                    label: _to.isEmpty ? 'To' : _to,
                    onTap: () => _pickDate(false),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _FilterButton(
                    icon: Icons.school_outlined,
                    label: _term.isEmpty ? 'All terms' : _term.split('|').join(' '),
                    onTap: _pickTerm,
                  ),
                ),
              ],
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Future<void> _pickTerm() async {
    final picked = await showModalBottomSheet<String>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              title: const Text('All terms'),
              onTap: () => Navigator.pop(ctx, ''),
            ),
            ..._terms.map((t) => ListTile(
                  title: Text(t.label),
                  onTap: () => Navigator.pop(ctx, t.value),
                )),
          ],
        ),
      ),
    );
    if (picked != null) {
      setState(() => _term = picked);
      _load();
    }
  }

  Widget _buildBody() {
    if (_loading) return const ListSkeleton(itemCount: 6);
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 16),
              AppButton(label: 'Retry', onTap: _load),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
        children: [
          AppPageHeader(
            title: 'Collection Report',
            icon: Icons.summarize_outlined,
            subtitle:
                '${_rows.length} payments · ₱${_total.toStringAsFixed(2)} total',
          ),
          if (_rows.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 60),
              child: AppEmptyState(
                icon: Icons.receipt_long_outlined,
                title: 'No payments in this period',
                subtitle: 'Widen the date range or clear the term filter.',
              ),
            )
          else ...[
            ..._rows.map((r) => _CollectionRow(row: r)),
            const SizedBox(height: 8),
            AppCard.elevated(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  const Expanded(
                    child: Text(
                      'TOTAL COLLECTION',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.6,
                        color: AppInk.muted,
                      ),
                    ),
                  ),
                  Text(
                    '₱${_total.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                      color: AppInk.positive,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _FilterButton extends StatelessWidget {
  const _FilterButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
          decoration: BoxDecoration(
            border: Border.all(color: AppInk.rule),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Row(
            children: [
              Icon(icon, size: 15, color: AppInk.muted),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w600,
                    color: AppInk.body,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CollectionRow extends StatelessWidget {
  const _CollectionRow({required this.row});

  final PaymentEntry row;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    row.studentName,
                    style: const TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${row.date} · O.R. ${row.orNumber} · ${row.description}',
                    style:
                        const TextStyle(fontSize: 11.5, color: AppInk.muted),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    '${row.paymentType} · ${row.cashier}',
                    style:
                        const TextStyle(fontSize: 11, color: AppInk.muted),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Text(
              '₱${row.amount.toStringAsFixed(2)}',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppInk.positive,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
