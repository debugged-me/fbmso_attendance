import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../misc/data/misc_api.dart';
import '../../misc/domain/misc_models.dart';

/// Personnel management screen — admin can add, edit, delete, toggle.
/// Mirrors the web FbmsoPersonnels/manage page.
class PersonnelManageScreen extends StatefulWidget {
  const PersonnelManageScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<PersonnelManageScreen> createState() => _PersonnelManageScreenState();
}

class _PersonnelManageScreenState extends State<PersonnelManageScreen> {
  late final MiscApi _api;
  List<Personnel> _people = [];
  bool _loading = true;
  String? _error;

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
      final list = await _api.personnelAll(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _people = list;
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

  Future<void> _delete(Personnel p) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Personnel'),
        content: Text('Remove "${p.fullName}"? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await _api.personnelDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: p.id,
      );
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    }
  }

  Future<void> _toggle(Personnel p) async {
    try {
      await _api.personnelToggle(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: p.id,
        isActive: p.isActive == 1 ? 0 : 1,
      );
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    }
  }

  void _showForm([Personnel? existing]) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _PersonnelForm(
        api: _api,
        session: widget.session,
        existing: existing,
        onSaved: _load,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Manage Personnel',
      showBackButton: true,
      actions: [
        IconButton(
          icon: const Icon(Icons.person_add_rounded),
          onPressed: () => _showForm(),
        ),
      ],
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
                      : _people.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.people_outline_rounded,
                                title: 'No personnel yet',
                                subtitle: 'Tap + to add personnel.',
                              ),
                            ])
                          : ListView.builder(
                              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                              itemCount: _people.length,
                              itemBuilder: (context, i) {
                                final p = _people[i];
                                return _PersonnelManageCard(
                                  person: p,
                                  onEdit: () => _showForm(p),
                                  onDelete: () => _delete(p),
                                  onToggle: () => _toggle(p),
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

class _PersonnelManageCard extends StatelessWidget {
  const _PersonnelManageCard({
    required this.person,
    required this.onEdit,
    required this.onDelete,
    required this.onToggle,
  });
  final Personnel person;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onToggle;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipOval(
              child: SizedBox(
                width: 48,
                height: 48,
                child: person.photoUrl.isNotEmpty
                    ? Image.network(person.photoUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                            color: AppInk.accent.withValues(alpha: 0.12),
                            child: const Icon(Icons.person_rounded,
                                color: AppInk.accent, size: 24)))
                    : Container(
                        color: AppInk.accent.withValues(alpha: 0.12),
                        child: const Icon(Icons.person_rounded,
                            color: AppInk.accent, size: 24)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(person.fullName,
                      style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: AppInk.heading)),
                  if (person.title.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(person.title,
                        style: const TextStyle(
                            fontSize: 13, color: AppInk.accent, fontWeight: FontWeight.w600)),
                  ],
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: (person.isActive == 1 ? AppInk.positive : AppInk.muted)
                          .withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      person.isActive == 1 ? 'Active' : 'Inactive',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: person.isActive == 1 ? AppInk.positive : AppInk.muted,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            PopupMenuButton<String>(
              onSelected: (v) {
                if (v == 'edit') onEdit();
                if (v == 'delete') onDelete();
                if (v == 'toggle') onToggle();
              },
              itemBuilder: (_) => [
                const PopupMenuItem(value: 'edit', child: Text('Edit')),
                PopupMenuItem(
                    value: 'toggle',
                    child: Text(person.isActive == 1 ? 'Deactivate' : 'Activate')),
                const PopupMenuItem(value: 'delete', child: Text('Delete')),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _PersonnelForm extends StatefulWidget {
  const _PersonnelForm({
    required this.api,
    required this.session,
    this.existing,
    required this.onSaved,
  });

  final MiscApi api;
  final AppSession session;
  final Personnel? existing;
  final VoidCallback onSaved;

  @override
  State<_PersonnelForm> createState() => _PersonnelFormState();
}

class _PersonnelFormState extends State<_PersonnelForm> {
  late final TextEditingController _name;
  late final TextEditingController _title;
  late final TextEditingController _bio;
  late final TextEditingController _sortOrder;
  bool _isActive = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.existing?.fullName ?? '');
    _title = TextEditingController(text: widget.existing?.title ?? '');
    _bio = TextEditingController(text: widget.existing?.bio ?? '');
    _sortOrder = TextEditingController(
        text: (widget.existing?.sortOrder ?? 100).toString());
    _isActive = widget.existing?.isActive != 0;
  }

  @override
  void dispose() {
    _name.dispose();
    _title.dispose();
    _bio.dispose();
    _sortOrder.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_name.text.trim().isEmpty) {
      setState(() => _error = 'Full name is required.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      await widget.api.personnelSave(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: widget.existing?.id ?? 0,
        fullName: _name.text.trim(),
        title: _title.text.trim(),
        bio: _bio.text.trim(),
        sortOrder: int.tryParse(_sortOrder.text) ?? 100,
        isActive: _isActive ? 1 : 0,
      );
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
            widget.existing != null ? 'Edit Personnel' : 'Add Personnel',
            style: const TextStyle(
              fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading),
          ),
          const SizedBox(height: 20),
          if (_error != null) ...[
            Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
            const SizedBox(height: 12),
          ],
          AppInput(
            controller: _name,
            label: 'Full Name *',
            hint: 'Enter full name',
            prefixIcon: Icons.person_outline_rounded,
          ),
          const SizedBox(height: 14),
          AppInput(
            controller: _title,
            label: 'Title / Position',
            hint: 'e.g. President, Adviser',
            prefixIcon: Icons.work_outline_rounded,
          ),
          const SizedBox(height: 14),
          AppInput(
            controller: _bio,
            label: 'Bio',
            hint: 'Short description (optional)',
            prefixIcon: Icons.info_outline_rounded,
            maxLines: 3,
          ),
          const SizedBox(height: 14),
          AppInput(
            controller: _sortOrder,
            label: 'Sort Order',
            hint: '100',
            prefixIcon: Icons.sort_rounded,
            keyboardType: TextInputType.number,
          ),
          const SizedBox(height: 14),
          SwitchListTile(
            title: const Text('Active'),
            value: _isActive,
            onChanged: (v) => setState(() => _isActive = v),
            activeThumbColor: AppInk.accent,
          ),
          const SizedBox(height: 20),
          AppButton(
            label: 'Save',
            fullWidth: true,
            size: AppButtonSize.lg,
            loading: _saving,
            disabled: _saving,
            onTap: _save,
          ),
        ],
      ),
    );
  }
}
