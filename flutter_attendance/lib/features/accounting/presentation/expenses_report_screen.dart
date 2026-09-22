import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../auth/domain/app_session.dart';
import '../../misc/data/misc_api.dart';
import '../data/accounting_api.dart';
import '../domain/accounting_models.dart';

/// Expenses Report — web: Accounting::expenseSGenerate. Category + date
/// range filter over the expenses table with a running total.
class ExpensesReportScreen extends StatefulWidget {
  const ExpensesReportScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<ExpensesReportScreen> createState() => _ExpensesReportScreenState();
}

class _ExpensesReportScreenState extends State<ExpensesReportScreen> {
  late final AccountingApi _api;
  late final MiscApi _miscApi;
  List<ExpenseReportRow> _rows = [];
  List<String> _categories = [];
  double _total = 0;
  String _category = '';
  String _from = '';
  String _to = '';
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = AccountingApi();
    _miscApi = MiscApi();
    _load();
    _loadCategories();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await _api.expensesReport(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        category: _category,
        from: _from,
        to: _to,
      );
      if (!mounted) return;
      setState(() {
        _rows = data.rows;
        _total = data.total;
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

  Future<void> _loadCategories() async {
    try {
      final cats = await _miscApi.expenseCategories(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() =>
          _categories = cats.map((c) => c.category).toList());
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
    setState(() => isFrom ? _from = s : _to = s);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      body: Column(
        children: [
          // Filters: category chips + date range — same filters as the web
          // expensesReport page.
          SizedBox(
            height: 38,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                _Chip(
                  label: 'All categories',
                  selected: _category.isEmpty,
                  onTap: () {
                    setState(() => _category = '');
                    _load();
                  },
                ),
                ..._categories.map((c) => _Chip(
                      label: c,
                      selected: _category == c,
                      onTap: () {
                        setState(() => _category = c);
                        _load();
                      },
                    )),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                Expanded(
                  child: _DateBtn(
                    label: _from.isEmpty ? 'From' : _from,
                    onTap: () => _pickDate(true),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _DateBtn(
                    label: _to.isEmpty ? 'To' : _to,
                    onTap: () => _pickDate(false),
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

  Widget _buildBody() {
    if (_loading) return const ListSkeleton(itemCount: 6);
    if (_error != null) {
      return Center(
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
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
        children: [
          AppPageHeader(
            title: 'Expenses Report',
            icon: Icons.receipt_long_outlined,
            subtitle:
                '${_rows.length} entries · ₱${_total.toStringAsFixed(2)} total',
          ),
          if (_rows.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 60),
              child: AppEmptyState(
                icon: Icons.receipt_outlined,
                title: 'No expenses matched',
                subtitle: 'Adjust the category or date filters.',
              ),
            )
          else
            ..._rows.map((r) => _Row(r: r)),
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip(
      {required this.label, required this.selected, required this.onTap});
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => onTap(),
        showCheckmark: false,
        labelStyle: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: selected ? Colors.white : AppInk.body,
        ),
        selectedColor: AppInk.accent,
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
          side: BorderSide(
              color: selected ? AppInk.accent : AppInk.rule),
        ),
      ),
    );
  }
}

class _DateBtn extends StatelessWidget {
  const _DateBtn({required this.label, required this.onTap});
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
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.r});
  final ExpenseReportRow r;

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
                    r.description,
                    style: const TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    '${r.date} · ${r.category}${r.responsible.isNotEmpty ? ' · ${r.responsible}' : ''}',
                    style: const TextStyle(
                        fontSize: 11.5, color: AppInk.muted),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Text(
              '₱${r.amount.toStringAsFixed(2)}',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppInk.critical,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
