import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../auth/domain/app_session.dart';
import '../data/accounting_api.dart';
import '../domain/accounting_models.dart';

/// Payment Entry — the cashier's daily driver, mirroring the web
/// Accounting/Payment page: pick a student, a fee (or free text), an
/// amount, and the server issues the date-scoped O.R. number. The list
/// below defaults to today's payments, like the web "Payments on" filter.
class PaymentEntryScreen extends StatefulWidget {
  const PaymentEntryScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<PaymentEntryScreen> createState() => _PaymentEntryScreenState();
}

class _PaymentEntryScreenState extends State<PaymentEntryScreen> {
  late final AccountingApi _api;

  List<PaymentEntry> _payments = [];
  List<String> _paymentDates = [];
  String _dateFilter = '';
  String _today = '';
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
      final ctx = await _api.paymentContext(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _today = ctx.today;
        _dateFilter = ctx.today;
        _paymentDates = ctx.paymentDates;
        _payments = ctx.recentPayments;
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

  Future<void> _loadPayments(String date) async {
    setState(() => _dateFilter = date);
    try {
      final list = await _api.payments(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        date: date,
      );
      if (!mounted) return;
      setState(() => _payments = list);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  Future<bool> _confirmDelete(PaymentEntry p) {
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Payment'),
        content: Text(
            'Delete O.R. ${p.orNumber} for ${p.studentName} (₱${p.amount.toStringAsFixed(2)})?\nThis recomputes the student\'s ledger and writes an audit row.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Delete')),
        ],
      ),
    ).then((v) => v == true);
  }

  Future<void> _delete(PaymentEntry p) async {
    try {
      await _api.paymentDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: p.id,
      );
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.toString())));
      }
    }
  }

  Future<void> _openForm() async {
    final saved = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (_) => _PaymentFormScreen(session: widget.session, api: _api),
      ),
    );
    if (saved == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    final dateTotal =
        _payments.fold<double>(0, (s, p) => s + p.amount);

    return AppScaffold(
      title: 'Payment Entry',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openForm,
        icon: const Icon(Icons.add_rounded),
        label: const Text('New Payment'),
      ),
      body: _loading
          ? const ListSkeleton(itemCount: 5)
          : _error != null
              ? Center(
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
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 88),
                    children: [
                      AppPageHeader(
                        title: 'Payment Entry',
                        subtitle:
                            '₱${dateTotal.toStringAsFixed(2)} collected ${_dateFilter == 'all' ? 'overall' : 'on $_dateFilter'}',
                      ),

                      // Date chips — the web "Payments on" filter, which
                      // only lists dates that actually have entries.
                      SizedBox(
                        height: 38,
                        child: ListView(
                          scrollDirection: Axis.horizontal,
                          children: [
                            _DateChip(
                              label: 'Today',
                              selected: _dateFilter == _today,
                              onTap: () => _loadPayments(_today),
                            ),
                            _DateChip(
                              label: 'All dates',
                              selected: _dateFilter == 'all',
                              onTap: () => _loadPayments('all'),
                            ),
                            ..._paymentDates
                                .where((d) => d != _today)
                                .map((d) => _DateChip(
                                      label: d,
                                      selected: _dateFilter == d,
                                      onTap: () => _loadPayments(d),
                                    )),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),

                      if (_payments.isEmpty)
                        const Padding(
                          padding: EdgeInsets.only(top: 60),
                          child: AppEmptyState(
                            icon: Icons.payments_outlined,
                            title: 'No payments on this date',
                            subtitle:
                                'Tap New Payment to record a collection.',
                          ),
                        )
                      else
                        ..._payments.map((p) => AppSwipeActions(
                              dismissKey: ValueKey('payment-${p.id}'),
                              confirmDelete: () => _confirmDelete(p),
                              onDeleted: () => _delete(p),
                              child: _PaymentTile(payment: p),
                            )),
                    ],
                  ),
                ),
    );
  }
}

class _DateChip extends StatelessWidget {
  const _DateChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

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
        labelStyle: TextStyle(
          fontSize: 12.5,
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
        showCheckmark: false,
      ),
    );
  }
}

class _PaymentTile extends StatelessWidget {
  const _PaymentTile({required this.payment});

  final PaymentEntry payment;

  @override
  Widget build(BuildContext context) {
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
                        payment.studentName,
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
                        '${payment.studentNumber} · ${payment.description}',
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
                  '₱${payment.amount.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: AppInk.positive,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            const AppRule(),
            const SizedBox(height: 8),
            Row(
              children: [
                _Pill(label: 'O.R. ${payment.orNumber}'),
                const SizedBox(width: 6),
                if (payment.paymentType.isNotEmpty)
                  _Pill(label: payment.paymentType),
                const Spacer(),
                Text(
                  payment.time,
                  style: const TextStyle(fontSize: 11, color: AppInk.muted),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: AppInk.accent.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 10.5,
          fontWeight: FontWeight.w700,
          color: AppInk.accent,
        ),
      ),
    );
  }
}

/// Full-screen payment form (modal route). Mirrors the web payment modal:
/// student, fee/amount, payment type, date, ref no — O.R. is issued by the
/// server on save, never typed.
class _PaymentFormScreen extends StatefulWidget {
  const _PaymentFormScreen({required this.session, required this.api});

  final AppSession session;
  final AccountingApi api;

  @override
  State<_PaymentFormScreen> createState() => _PaymentFormScreenState();
}

class _PaymentFormScreenState extends State<_PaymentFormScreen> {
  bool _loading = true;
  bool _saving = false;
  String? _error;

  List<PayableStudent> _students = [];
  List<FeeTemplate> _fees = [];
  String _nextOr = '';

  PayableStudent? _student;
  FeeTemplate? _fee;
  final _descriptionCtrl = TextEditingController();
  final _amountCtrl = TextEditingController();
  final _dateCtrl = TextEditingController();
  final _checkNoCtrl = TextEditingController();
  final _bankCtrl = TextEditingController();
  final _refCtrl = TextEditingController();
  String _paymentType = 'Cash';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final ctx = await widget.api.paymentContext(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _students = ctx.students;
        _fees = ctx.fees;
        _nextOr = ctx.nextOrNumber;
        _dateCtrl.text = ctx.today;
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

  Future<void> _pickStudent() async {
    final picked = await showModalBottomSheet<PayableStudent>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _StudentPicker(students: _students),
    );
    if (picked != null) setState(() => _student = picked);
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _dateCtrl.text.isNotEmpty
          ? (DateTime.tryParse(_dateCtrl.text) ?? now)
          : now,
      firstDate: DateTime(now.year - 5),
      lastDate: DateTime(now.year + 1),
    );
    if (picked != null) {
      _dateCtrl.text =
          '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    }
  }

  Future<void> _save() async {
    if (_student == null) {
      _toast('Pick a student first.');
      return;
    }
    if (_descriptionCtrl.text.trim().isEmpty) {
      _toast('Description is required.');
      return;
    }
    final amount = double.tryParse(_amountCtrl.text.trim());
    if (amount == null || amount <= 0) {
      _toast('Amount must be greater than 0.');
      return;
    }

    setState(() => _saving = true);
    try {
      final result = await widget.api.paymentCreate(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        studentNumber: _student!.studentNumber,
        description: _descriptionCtrl.text.trim(),
        amount: amount,
        pDate: _dateCtrl.text.trim(),
        paymentType: _paymentType,
        checkNumber: _checkNoCtrl.text.trim(),
        bank: _bankCtrl.text.trim(),
        refNo: _refCtrl.text.trim(),
      );
      if (!mounted) return;
      HapticFeedback.mediumImpact();
      await showDialog<void>(
        context: context,
        builder: (ctx) => AlertDialog(
          icon: const Icon(Icons.check_circle_rounded,
              color: AppInk.positive, size: 40),
          title: const Text('Payment saved'),
          content: Text('O.R. #${result.orNumber}'),
          actions: [
            FilledButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Done'),
            ),
          ],
        ),
      );
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      _toast(e.message);
    } catch (e) {
      _toast(e.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _toast(String msg) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  void dispose() {
    _descriptionCtrl.dispose();
    _amountCtrl.dispose();
    _dateCtrl.dispose();
    _checkNoCtrl.dispose();
    _bankCtrl.dispose();
    _refCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'New Payment',
      body: _loading
          ? const ListSkeleton(itemCount: 4)
          : _error != null
              ? Center(
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
                )
              : ListView(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                  children: [
                    // O.R. preview — the server issues this on save.
                    AppCard(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          const Icon(Icons.receipt_long_rounded,
                              color: AppInk.accent),
                          const SizedBox(width: 10),
                          const Expanded(
                            child: Text(
                              'Next O.R. number',
                              style: TextStyle(
                                  fontSize: 13, color: AppInk.muted),
                            ),
                          ),
                          Text(
                            _nextOr,
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w800,
                              color: AppInk.heading,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Student picker
                    const _FieldLabel('Student'),
                    AppCard(
                      onTap: _pickStudent,
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          Expanded(
                            child: _student == null
                                ? const Text(
                                    'Tap to choose a student',
                                    style: TextStyle(
                                        color: AppInk.muted, fontSize: 14),
                                  )
                                : Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        _student!.fullName,
                                        style: const TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w700,
                                          color: AppInk.heading,
                                        ),
                                      ),
                                      Text(
                                        '${_student!.studentNumber} · ${_student!.course} ${_student!.yearLevel}',
                                        style: const TextStyle(
                                            fontSize: 12,
                                            color: AppInk.muted),
                                      ),
                                    ],
                                  ),
                          ),
                          const Icon(Icons.search_rounded,
                              color: AppInk.muted, size: 20),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Fee templates — picking one fills description+amount,
                    // same as the web fee dropdown.
                    const _FieldLabel('Fee (optional shortcut)'),
                    SizedBox(
                      height: 38,
                      child: ListView(
                        scrollDirection: Axis.horizontal,
                        children: _fees
                            .map((f) => Padding(
                                  padding: const EdgeInsets.only(right: 8),
                                  child: ChoiceChip(
                                    label: Text(
                                        '${f.description} · ₱${f.amount.toStringAsFixed(0)}'),
                                    selected: _fee?.id == f.id,
                                    showCheckmark: false,
                                    onSelected: (_) => setState(() {
                                      _fee = f;
                                      _descriptionCtrl.text = f.description;
                                      _amountCtrl.text =
                                          f.amount.toStringAsFixed(2);
                                    }),
                                  ),
                                ))
                            .toList(),
                      ),
                    ),
                    const SizedBox(height: 16),

                    const _FieldLabel('Description'),
                    AppInput(
                      controller: _descriptionCtrl,
                      hint: 'e.g. Tuition Fee',
                    ),
                    const SizedBox(height: 12),

                    const _FieldLabel('Amount'),
                    AppInput(
                      controller: _amountCtrl,
                      hint: '0.00',
                      keyboardType: const TextInputType.numberWithOptions(
                          decimal: true),
                      prefixIcon: Icons.payments_outlined,
                    ),
                    const SizedBox(height: 12),

                    const _FieldLabel('Payment Date'),
                    AppInput(
                      controller: _dateCtrl,
                      hint: 'YYYY-MM-DD',
                      readOnly: true,
                      onTap: _pickDate,
                      prefixIcon: Icons.event_rounded,
                    ),
                    const SizedBox(height: 12),

                    const _FieldLabel('Payment Type'),
                    SegmentedButton<String>(
                      segments: const [
                        ButtonSegment(value: 'Cash', label: Text('Cash')),
                        ButtonSegment(value: 'Check', label: Text('Check')),
                        ButtonSegment(
                            value: 'Online', label: Text('Online')),
                      ],
                      selected: {_paymentType},
                      onSelectionChanged: (s) =>
                          setState(() => _paymentType = s.first),
                    ),
                    const SizedBox(height: 12),

                    if (_paymentType == 'Check') ...[
                      const _FieldLabel('Check Number'),
                      AppInput(
                          controller: _checkNoCtrl, hint: 'Check no.'),
                      const SizedBox(height: 12),
                      const _FieldLabel('Bank'),
                      AppInput(controller: _bankCtrl, hint: 'Bank name'),
                      const SizedBox(height: 12),
                    ],

                    const _FieldLabel('Reference No. (optional)'),
                    AppInput(controller: _refCtrl, hint: 'Ref no.'),
                    const SizedBox(height: 24),

                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _saving ? null : _save,
                        icon: _saving
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                    strokeWidth: 2, color: Colors.white),
                              )
                            : const Icon(Icons.check_rounded),
                        label: Text(_saving ? 'Saving…' : 'Save Payment'),
                        style: FilledButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          textStyle: const TextStyle(
                              fontSize: 15, fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }
}

class _FieldLabel extends StatelessWidget {
  const _FieldLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(
        text.toUpperCase(),
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.6,
          color: AppInk.muted,
        ),
      ),
    );
  }
}

/// Searchable student picker — the web form's select2 equivalent.
class _StudentPicker extends StatefulWidget {
  const _StudentPicker({required this.students});
  final List<PayableStudent> students;

  @override
  State<_StudentPicker> createState() => _StudentPickerState();
}

class _StudentPickerState extends State<_StudentPicker> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final q = _query.toLowerCase();
    final filtered = widget.students
        .where((s) =>
            s.fullName.toLowerCase().contains(q) ||
            s.studentNumber.toLowerCase().contains(q))
        .toList();

    return DraggableScrollableSheet(
      initialChildSize: 0.85,
      maxChildSize: 0.95,
      minChildSize: 0.5,
      expand: false,
      builder: (context, scroll) => Column(
        children: [
          const SizedBox(height: 8),
          Container(
            width: 36,
            height: 4,
            decoration: BoxDecoration(
              color: AppInk.rule,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              autofocus: true,
              decoration: InputDecoration(
                hintText: 'Search name or student number',
                prefixIcon: const Icon(Icons.search_rounded, size: 20),
                filled: true,
                fillColor: AppInk.muted.withValues(alpha: 0.08),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
                contentPadding:
                    const EdgeInsets.symmetric(vertical: 12),
              ),
              onChanged: (v) => setState(() => _query = v),
            ),
          ),
          Expanded(
            child: ListView.builder(
              controller: scroll,
              itemCount: filtered.length,
              itemBuilder: (context, i) {
                final s = filtered[i];
                return ListTile(
                  title: Text(
                    s.fullName.isNotEmpty ? s.fullName : s.studentNumber,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(
                    '${s.studentNumber} · ${s.course} ${s.yearLevel}'
                        .trim(),
                    style:
                        const TextStyle(fontSize: 12, color: AppInk.muted),
                  ),
                  onTap: () => Navigator.of(context).pop(s),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
