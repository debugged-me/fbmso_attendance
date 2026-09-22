import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../auth/domain/app_session.dart';
import '../data/accounting_api.dart';
import '../domain/accounting_models.dart';

/// Ledger — web: Accounting::ledger. Collections vs expenses merged into
/// one chronological running balance for a date range.
class LedgerScreen extends StatefulWidget {
  const LedgerScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<LedgerScreen> createState() => _LedgerScreenState();
}

class _LedgerScreenState extends State<LedgerScreen> {
  late final AccountingApi _api;
  List<LedgerRow> _rows = [];
  double _gross = 0;
  double _spent = 0;
  String _from = '';
  String _to = '';
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
      final data = await _api.ledger(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        from: _from,
        to: _to,
      );
      if (!mounted) return;
      setState(() {
        _rows = data.rows;
        _gross = data.gross;
        _spent = data.spent;
        _from = data.rows.isNotEmpty || _from.isEmpty
            ? _from
            : _from;
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
    setState(() => isFrom ? _from = s : _to = s);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final net = _gross - _spent;

    return AppScaffold(
      title: 'Ledger',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
            child: Row(
              children: [
                Expanded(
                  child: _DateButton(
                    label: _from.isEmpty ? 'From' : _from,
                    onTap: () => _pickDate(true),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _DateButton(
                    label: _to.isEmpty ? 'To' : _to,
                    onTap: () => _pickDate(false),
                  ),
                ),
              ],
            ),
          ),
          Expanded(child: _buildBody(net)),
        ],
      ),
    );
  }

  Widget _buildBody(double net) {
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
            title: 'Ledger',
            subtitle:
                '₱${_gross.toStringAsFixed(2)} in · ₱${_spent.toStringAsFixed(2)} out',
          ),

          // Gross / Spent / Net — same figures as the web ledger header.
          Row(
            children: [
              _SummaryPill(
                  label: 'Gross',
                  value: _gross,
                  color: AppInk.positive),
              const SizedBox(width: 8),
              _SummaryPill(
                  label: 'Spent',
                  value: _spent,
                  color: AppInk.critical),
              const SizedBox(width: 8),
              _SummaryPill(
                  label: 'Net',
                  value: net,
                  color: net >= 0 ? AppInk.accent : AppInk.critical),
            ],
          ),
          const SizedBox(height: 14),

          if (_rows.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 60),
              child: AppEmptyState(
                icon: Icons.book_outlined,
                title: 'No entries this period',
                subtitle:
                    'Collections and expenses in range appear here.',
              ),
            )
          else
            ..._rows.map((r) => _LedgerTile(row: r)),
        ],
      ),
    );
  }
}

class _DateButton extends StatelessWidget {
  const _DateButton({required this.label, required this.onTap});
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
              const Icon(Icons.event_rounded,
                  size: 15, color: AppInk.muted),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(
                    fontSize: 12,
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

class _SummaryPill extends StatelessWidget {
  const _SummaryPill({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final double value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Text(
              label.toUpperCase(),
              style: TextStyle(
                fontSize: 9.5,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.5,
                color: color,
              ),
            ),
            const SizedBox(height: 3),
            Text(
              '₱${value.toStringAsFixed(0)}',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: color,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

class _LedgerTile extends StatelessWidget {
  const _LedgerTile({required this.row});

  final LedgerRow row;

  @override
  Widget build(BuildContext context) {
    final color = row.isIncome ? AppInk.positive : AppInk.critical;

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.10),
                shape: BoxShape.circle,
              ),
              child: Icon(
                row.isIncome
                    ? Icons.south_east_rounded
                    : Icons.north_east_rounded,
                size: 16,
                color: color,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    row.description,
                    style: const TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w600,
                      color: AppInk.heading,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    '${row.date} · ${row.ref}',
                    style: const TextStyle(
                        fontSize: 11, color: AppInk.muted),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  '${row.isIncome ? '+' : '−'}₱${row.amount.toStringAsFixed(2)}',
                  style: TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
                Text(
                  'bal ₱${row.balance.toStringAsFixed(2)}',
                  style: const TextStyle(
                      fontSize: 10.5, color: AppInk.muted),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
