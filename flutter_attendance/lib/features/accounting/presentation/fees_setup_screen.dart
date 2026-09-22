import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../auth/domain/app_session.dart';
import '../data/accounting_api.dart';
import '../domain/accounting_models.dart';

/// Fees Setup — web: Accounting::course_setUp. The fee templates that
/// drive the payment form's description picker.
class FeesSetupScreen extends StatefulWidget {
  const FeesSetupScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<FeesSetupScreen> createState() => _FeesSetupScreenState();
}

class _FeesSetupScreenState extends State<FeesSetupScreen> {
  late final AccountingApi _api;
  List<FeeTemplate> _fees = [];
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
      final list = await _api.fees(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _fees = list;
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

  Future<void> _edit(FeeTemplate? fee) async {
    final descCtrl =
        TextEditingController(text: fee?.description ?? '');
    final amountCtrl = TextEditingController(
        text: fee != null ? fee.amount.toStringAsFixed(2) : '');
    final typeCtrl = TextEditingController(text: fee?.type ?? '');

    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(
            bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius:
                BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.fromLTRB(24, 12, 24, 32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppInk.rule,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                fee == null ? 'Add Fee' : 'Edit Fee',
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: AppInk.heading,
                ),
              ),
              const SizedBox(height: 20),
              AppInput(
                controller: descCtrl,
                label: 'Description',
                prefixIcon: Icons.sell_outlined,
              ),
              const SizedBox(height: 14),
              AppInput(
                controller: amountCtrl,
                label: 'Amount',
                keyboardType: const TextInputType.numberWithOptions(
                    decimal: true),
                prefixIcon: Icons.payments_outlined,
              ),
              const SizedBox(height: 14),
              AppInput(
                controller: typeCtrl,
                label: 'Fee Type',
                prefixIcon: Icons.category_outlined,
              ),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: () => Navigator.pop(ctx, true),
                style: FilledButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  textStyle: const TextStyle(
                      fontSize: 15, fontWeight: FontWeight.w700),
                ),
                child: Text(fee == null ? 'Add Fee' : 'Save Changes'),
              ),
            ],
          ),
        ),
      ),
    );
    if (saved != true) return;

    try {
      final amount = double.tryParse(amountCtrl.text.trim()) ?? 0;
      if (fee == null) {
        await _api.feeCreate(
          baseUrl: widget.session.baseUrl,
          token: widget.session.token,
          description: descCtrl.text.trim(),
          amount: amount,
          feesType: typeCtrl.text.trim(),
        );
      } else {
        await _api.feeUpdate(
          baseUrl: widget.session.baseUrl,
          token: widget.session.token,
          id: fee.id,
          description: descCtrl.text.trim(),
          amount: amount,
          feesType: typeCtrl.text.trim(),
        );
      }
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.toString())));
      }
    }
  }

  Future<bool> _confirmDelete(FeeTemplate f) {
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Fee'),
        content: Text(
            'Delete "${f.description}" (₱${f.amount.toStringAsFixed(2)})?'),
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

  Future<void> _delete(FeeTemplate f) async {
    try {
      await _api.feeDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: f.id,
      );
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.toString())));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _edit(null),
        icon: const Icon(Icons.add_rounded),
        label: const Text('Add Fee'),
      ),
      body: _loading
          ? const ListSkeleton(itemCount: 6)
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
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 88),
                    itemCount: _fees.isEmpty ? 2 : _fees.length + 1,
                    itemBuilder: (context, i) {
                      if (i == 0) {
                        return AppPageHeader(
                          title: 'Fees Setup',
                          icon: Icons.sell_outlined,
                          subtitle:
                              '${_fees.length} fee template${_fees.length == 1 ? '' : 's'} · swipe to edit or delete',
                        );
                      }
                      if (_fees.isEmpty) {
                        return const Padding(
                          padding: EdgeInsets.only(top: 60),
                          child: AppEmptyState(
                            icon: Icons.sell_outlined,
                            title: 'No fees configured',
                            subtitle: 'Tap Add Fee to create one.',
                          ),
                        );
                      }
                      final f = _fees[i - 1];
                      return AppSwipeActions(
                        dismissKey: ValueKey('fee-${f.id}'),
                        confirmDelete: () => _confirmDelete(f),
                        onDeleted: () => _delete(f),
                        onEdit: () => _edit(f),
                        child:
                            _FeeTile(fee: f, onEdit: () => _edit(f)),
                      );
                    },
                  ),
                ),
    );
  }
}

class _FeeTile extends StatelessWidget {
  const _FeeTile({required this.fee, required this.onEdit});

  final FeeTemplate fee;
  final VoidCallback onEdit;

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
                    fee.description,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (fee.type.isNotEmpty)
                    Text(
                      fee.type,
                      style: const TextStyle(
                          fontSize: 11.5, color: AppInk.muted),
                    ),
                ],
              ),
            ),
            Text(
              '₱${fee.amount.toStringAsFixed(2)}',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppInk.accent,
              ),
            ),
            const SizedBox(width: 4),
            IconButton(
              icon: const Icon(Icons.edit_outlined,
                  size: 20, color: AppInk.muted),
              onPressed: onEdit,
              tooltip: 'Edit fee',
              visualDensity: VisualDensity.compact,
            ),
          ],
        ),
      ),
    );
  }
}
