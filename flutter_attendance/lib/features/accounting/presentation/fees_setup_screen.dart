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

    final saved = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(fee == null ? 'Add Fee' : 'Edit Fee'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            AppInput(controller: descCtrl, hint: 'Description'),
            const SizedBox(height: 12),
            AppInput(
              controller: amountCtrl,
              hint: 'Amount',
              keyboardType: const TextInputType.numberWithOptions(
                  decimal: true),
            ),
            const SizedBox(height: 12),
            AppInput(
              controller: typeCtrl,
              hint: 'Fee type (default: School Fee)',
            ),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Save')),
        ],
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
      title: 'Fees Setup',
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
                        child: _FeeTile(fee: f),
                      );
                    },
                  ),
                ),
    );
  }
}

class _FeeTile extends StatelessWidget {
  const _FeeTile({required this.fee});

  final FeeTemplate fee;

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
          ],
        ),
      ),
    );
  }
}
