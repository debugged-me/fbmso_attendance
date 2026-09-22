import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../misc/data/misc_api.dart';
import '../../misc/domain/misc_models.dart';

/// Expenses management screen — admin can view, create, edit, delete expenses
/// and manage categories. Mirrors the web Accounting/expenses page.
class ExpensesScreen extends StatefulWidget {
  const ExpensesScreen({super.key, required this.session, this.menuButton});

  final AppSession session;
  final Widget? menuButton;

  @override
  State<ExpensesScreen> createState() => _ExpensesScreenState();
}

class _ExpensesScreenState extends State<ExpensesScreen> {
  late final MiscApi _api;
  List<ExpenseEntry> _expenses = [];
  List<ExpenseCategory> _categories = [];
  int _selectedCategory = 0; // 0 = All
  bool _loading = true;
  String? _error;

  List<ExpenseEntry> get _visible {
    if (_selectedCategory == 0) return _expenses;
    final cat = _categories[_selectedCategory - 1].category;
    return _expenses.where((e) => e.category == cat).toList();
  }

  double get _total => _visible.fold(
      0,
      (s, e) =>
          s + (double.tryParse(e.amount.replaceAll(RegExp(r'[^0-9.]'), '')) ?? 0));

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait([
        _api.expenses(baseUrl: widget.session.baseUrl, token: widget.session.token),
        _api.expenseCategories(baseUrl: widget.session.baseUrl, token: widget.session.token),
      ]);
      if (!mounted) return;
      setState(() {
        _expenses = results[0] as List<ExpenseEntry>;
        _categories = results[1] as List<ExpenseCategory>;
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

  /// Left-swipe confirm — dismisses the row only when the user confirms.
  Future<bool> _confirmDelete(ExpenseEntry e) {
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Expense'),
        content: Text('Delete "${e.description}"? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
        ],
      ),
    ).then((v) => v == true);
  }

  Future<void> _delete(ExpenseEntry e) async {
    try {
      await _api.expenseDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        expensesid: e.id,
      );
      _load();
    } catch (err) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(err.toString())),
        );
      }
    }
  }

  void _showForm([ExpenseEntry? existing]) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _ExpenseForm(
        api: _api,
        session: widget.session,
        categories: _categories,
        existing: existing,
        onSaved: _load,
      ),
    );
  }

  void _showCategories() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _CategoriesSheet(
        api: _api,
        session: widget.session,
        categories: _categories,
        onSaved: _load,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final visible = _visible;
    final chips = ['All', ..._categories.map((c) => c.category)];

    return AppScaffold(
      title: 'Expenses',
      showBackButton: widget.menuButton == null,
      leading: widget.menuButton,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showForm(),
        icon: const Icon(Icons.add_rounded),
        label: const Text('Add Expense'),
      ),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          AppEmptyState(
                            icon: Icons.cloud_off_rounded,
                            title: 'Failed to load',
                            subtitle: _error,
                            action: 'Retry',
                            onAction: _load,
                          ),
                        ])
                      : ListView.builder(
                          padding:
                              const EdgeInsets.fromLTRB(16, 4, 16, 88),
                          itemCount: (visible.isEmpty ? 1 : visible.length) + 2,
                          itemBuilder: (context, i) {
                            if (i == 0) {
                              return AppPageHeader(
                                title: 'Expenses',
                                subtitle:
                                    '₱${_total.toStringAsFixed(2)} · ${visible.length} record${visible.length == 1 ? '' : 's'}',
                              );
                            }
                            if (i == 1) {
                              return AppFilterChips(
                                labels: chips,
                                selected: _selectedCategory
                                    .clamp(0, chips.length - 1),
                                onSelected: (v) =>
                                    setState(() => _selectedCategory = v),
                                manageLabel: 'Manage',
                                onManage: _showCategories,
                                padding: const EdgeInsets.only(bottom: 12),
                              );
                            }
                            if (visible.isEmpty) {
                              return const AppEmptyState(
                                icon: Icons.receipt_long_outlined,
                                title: 'No expenses yet',
                                subtitle: 'Tap Add Expense to record one.',
                              );
                            }
                            final e = visible[i - 2];
                            return AppSwipeActions(
                              dismissKey: ValueKey('expense-${e.id}'),
                              onEdit: () => _showForm(e),
                              confirmDelete: () => _confirmDelete(e),
                              onDeleted: () => _delete(e),
                              child: _ExpenseCard(expense: e),
                            );
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ExpenseCard extends StatelessWidget {
  const _ExpenseCard({required this.expense});
  final ExpenseEntry expense;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: AppInk.critical.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.receipt_long_rounded,
                  color: AppInk.critical, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    expense.description.isEmpty
                        ? '(no description)'
                        : expense.description,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Wrap(
                    spacing: 8,
                    runSpacing: 4,
                    children: [
                      if (expense.category.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppInk.accent.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(expense.category,
                              style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: AppInk.accent)),
                        ),
                      if (expense.date.isNotEmpty)
                        Text(expense.date,
                            style: const TextStyle(
                                fontSize: 12, color: AppInk.muted)),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  '₱${(double.tryParse(expense.amount.replaceAll(RegExp(r'[^0-9.]'), '')) ?? 0).toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: AppInk.heading,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ExpenseForm extends StatefulWidget {
  const _ExpenseForm({
    required this.api,
    required this.session,
    required this.categories,
    this.existing,
    required this.onSaved,
  });

  final MiscApi api;
  final AppSession session;
  final List<ExpenseCategory> categories;
  final ExpenseEntry? existing;
  final VoidCallback onSaved;

  @override
  State<_ExpenseForm> createState() => _ExpenseFormState();
}

class _ExpenseFormState extends State<_ExpenseForm> {
  late final TextEditingController _desc;
  late final TextEditingController _amount;
  late final TextEditingController _responsible;
  late final TextEditingController _date;
  String _category = '';
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _desc = TextEditingController(text: widget.existing?.description ?? '');
    _amount = TextEditingController(text: widget.existing?.amount ?? '');
    _responsible = TextEditingController(text: '');
    _date = TextEditingController(text: widget.existing?.date ?? '');
    _category = widget.existing?.category ?? '';
  }

  @override
  void dispose() {
    _desc.dispose();
    _amount.dispose();
    _responsible.dispose();
    _date.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: now,
      firstDate: DateTime(2020),
      lastDate: DateTime(now.year + 1),
    );
    if (picked != null) {
      _date.text =
          '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    }
  }

  Future<void> _save() async {
    if (_desc.text.trim().isEmpty ||
        _amount.text.trim().isEmpty ||
        _date.text.trim().isEmpty) {
      setState(() => _error = 'Description, amount, and date are required.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      if (widget.existing != null) {
        await widget.api.expenseUpdate(
          baseUrl: widget.session.baseUrl,
          token: widget.session.token,
          expensesid: widget.existing!.id,
          description: _desc.text.trim(),
          amount: _amount.text.trim(),
          responsible: _responsible.text.trim(),
          expenseDate: _date.text.trim(),
          category: _category,
        );
      } else {
        await widget.api.expenseCreate(
          baseUrl: widget.session.baseUrl,
          token: widget.session.token,
          description: _desc.text.trim(),
          amount: _amount.text.trim(),
          responsible: _responsible.text.trim(),
          expenseDate: _date.text.trim(),
          category: _category,
        );
      }
      if (!mounted) return;
      widget.onSaved();
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = e.toString();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(
        24, 12, 24,
        32 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color: AppInk.rule,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              widget.existing != null ? 'Edit Expense' : 'Add Expense',
              style: const TextStyle(
                  fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading),
            ),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
              const SizedBox(height: 12),
            ],
            AppInput(
              controller: _desc,
              label: 'Description *',
              hint: 'Enter description',
              prefixIcon: Icons.description_outlined,
            ),
            const SizedBox(height: 14),
            AppInput(
              controller: _amount,
              label: 'Amount *',
              hint: '0.00',
              prefixIcon: Icons.payments_outlined,
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 14),
            AppInput(
              controller: _responsible,
              label: 'Responsible',
              hint: 'Person responsible',
              prefixIcon: Icons.person_outline_rounded,
            ),
            const SizedBox(height: 14),
            GestureDetector(
              onTap: _pickDate,
              child: AbsorbPointer(
                child: AppInput(
                  controller: _date,
                  label: 'Expense Date *',
                  hint: 'Tap to select date',
                  prefixIcon: Icons.calendar_today_rounded,
                ),
              ),
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _category.isEmpty ? null : _category,
              decoration: InputDecoration(
                labelText: 'Category',
                prefixIcon: const Icon(Icons.category_outlined, size: 20, color: AppInk.muted),
                filled: true, fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
              ),
              items: widget.categories
                  .map((c) => DropdownMenuItem(value: c.category, child: Text(c.category)))
                  .toList(),
              onChanged: (v) => setState(() => _category = v ?? ''),
            ),
            const SizedBox(height: 20),
            AppButton(
              label: widget.existing != null ? 'Update' : 'Save',
              fullWidth: true,
              size: AppButtonSize.lg,
              loading: _saving,
              disabled: _saving,
              onTap: _save,
            ),
          ],
        ),
      ),
    );
  }
}

class _CategoriesSheet extends StatefulWidget {
  const _CategoriesSheet({
    required this.api,
    required this.session,
    required this.categories,
    required this.onSaved,
  });

  final MiscApi api;
  final AppSession session;
  final List<ExpenseCategory> categories;
  final VoidCallback onSaved;

  @override
  State<_CategoriesSheet> createState() => _CategoriesSheetState();
}

class _CategoriesSheetState extends State<_CategoriesSheet> {
  late final TextEditingController _newCategory;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _newCategory = TextEditingController();
  }

  @override
  void dispose() {
    _newCategory.dispose();
    super.dispose();
  }

  Future<void> _add() async {
    if (_newCategory.text.trim().isEmpty) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.api.expenseCategoryCreate(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        category: _newCategory.text.trim(),
      );
      _newCategory.clear();
      widget.onSaved();
      setState(() => _busy = false);
    } catch (e) {
      setState(() {
        _error = e.toString();
        _busy = false;
      });
    }
  }

  Future<void> _delete(ExpenseCategory c) async {
    try {
      await widget.api.expenseCategoryDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        categoryID: c.id,
      );
      widget.onSaved();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(
        24, 12, 24,
        32 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              width: 40, height: 4,
              decoration: BoxDecoration(
                color: AppInk.rule,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
          ),
          const SizedBox(height: 20),
          const Text('Expense Categories',
              style: TextStyle(
                  fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading)),
          const SizedBox(height: 16),
          if (_error != null) ...[
            Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
            const SizedBox(height: 12),
          ],
          Row(
            children: [
              Expanded(
                child: AppInput(
                  controller: _newCategory,
                  label: 'New Category',
                  hint: 'Enter category name',
                  prefixIcon: Icons.add_outlined,
                ),
              ),
              const SizedBox(width: 8),
              AppButton(
                label: 'Add',
                loading: _busy,
                disabled: _busy,
                onTap: _add,
              ),
            ],
          ),
          const SizedBox(height: 16),
          Flexible(
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: widget.categories.length,
              itemBuilder: (context, i) {
                final c = widget.categories[i];
                return ListTile(
                  leading: const Icon(Icons.label_outline_rounded,
                      color: AppInk.accent, size: 20),
                  title: Text(c.category,
                      style: const TextStyle(
                          fontSize: 14, fontWeight: FontWeight.w600)),
                  trailing: IconButton(
                    icon: const Icon(Icons.delete_outline_rounded,
                        color: AppInk.critical, size: 20),
                    onPressed: () => _delete(c),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
