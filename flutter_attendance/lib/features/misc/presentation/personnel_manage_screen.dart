import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Personnel management screen — admin can add, edit, delete.
/// Mirrors the web Page/employeelist (staff table).
class PersonnelManageScreen extends StatefulWidget {
  const PersonnelManageScreen({super.key, required this.session});
  final AppSession session;

  @override
  State<PersonnelManageScreen> createState() => _PersonnelManageScreenState();
}

class _PersonnelManageScreenState extends State<PersonnelManageScreen> {
  late final MiscApi _api;
  final List<Personnel> _rows = [];
  int _total = 0;
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  String _search = '';
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  static const _pageSize = 50;

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _scrollController.addListener(_onScroll);
    _load();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      _loadMore();
    }
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await _api.personnelAll(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        limit: _pageSize, offset: 0, search: _search,
      );
      if (!mounted) return;
      setState(() { _rows..clear()..addAll(r.rows); _total = r.total; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _rows.length >= _total) return;
    setState(() => _loadingMore = true);
    try {
      final r = await _api.personnelAll(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        limit: _pageSize, offset: _rows.length, search: _search,
      );
      if (!mounted) return;
      setState(() { _rows.addAll(r.rows); _total = r.total; _loadingMore = false; });
    } catch (_) {
      if (mounted) setState(() => _loadingMore = false);
    }
  }

  void _onSearchChanged(String v) { _search = v; _load(); }

  Future<void> _delete(Personnel p) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Personnel'),
        content: Text('Delete "${p.fullName}" (${p.id})?\nThis cannot be undone.'),
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
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  void _showForm({Personnel? existing}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _PersonnelForm(
        api: _api, session: widget.session, onSaved: _load, existing: existing,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Manage Personnel',
      showBackButton: true,
      actions: [
        IconButton(icon: const Icon(Icons.person_add_rounded), onPressed: () => _showForm()),
      ],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Search name, ID, position...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppInk.muted),
                suffixIcon: _search.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 20),
                        onPressed: () { _searchController.clear(); _onSearchChanged(''); },
                      )
                    : null,
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppInk.rule, width: 1.5),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppInk.accent, width: 2),
                ),
              ),
            ),
          ),
          if (!_loading && _error == null)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
              child: Row(
                children: [
                  Text('${_rows.length} of $_total personnel',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppInk.muted)),
                ],
              ),
            ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          AppEmptyState(
                            icon: Icons.cloud_off_rounded, title: 'Failed to load',
                            subtitle: _error, action: 'Retry', onAction: _load,
                          ),
                        ])
                      : _rows.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.people_outline_rounded,
                                title: 'No personnel found',
                                subtitle: 'Tap + to add a new personnel.',
                              ),
                            ])
                          : ListView.builder(
                              controller: _scrollController,
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount: _rows.length + (_loadingMore ? 1 : 0),
                              itemBuilder: (context, i) {
                                if (i >= _rows.length) {
                                  return const Padding(
                                    padding: EdgeInsets.all(16),
                                    child: Center(
                                      child: SizedBox(width: 24, height: 24,
                                        child: CircularProgressIndicator(strokeWidth: 2.5)),
                                    ),
                                  );
                                }
                                final p = _rows[i];
                                return _PersonnelCard(
                                  personnel: p,
                                  onEdit: () => _showForm(existing: p),
                                  onDelete: () => _delete(p),
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

class _PersonnelCard extends StatelessWidget {
  const _PersonnelCard({required this.personnel, required this.onEdit, required this.onDelete});
  final Personnel personnel;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final initials = _initials(personnel.fullName);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 42, height: 42,
              decoration: BoxDecoration(
                color: AppInk.accent.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Text(initials,
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppInk.accent)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(personnel.fullName,
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppInk.heading),
                      maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 3),
                  Wrap(
                    spacing: 8, runSpacing: 4,
                    children: [
                      Text(personnel.id, style: const TextStyle(fontSize: 12, color: AppInk.muted)),
                      if (personnel.title.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppInk.accent.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(personnel.title,
                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppInk.accent)),
                        ),
                      if (personnel.department.isNotEmpty)
                        Text(personnel.department,
                            style: const TextStyle(fontSize: 11, color: AppInk.muted)),
                    ],
                  ),
                  if (personnel.email.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(personnel.email,
                        style: const TextStyle(fontSize: 12, color: AppInk.muted),
                        maxLines: 1, overflow: TextOverflow.ellipsis),
                  ],
                ],
              ),
            ),
            IconButton(icon: const Icon(Icons.edit_outlined, size: 20, color: AppInk.muted), onPressed: onEdit),
            IconButton(icon: const Icon(Icons.delete_outline_rounded, color: AppInk.critical, size: 20), onPressed: onDelete),
          ],
        ),
      ),
    );
  }

  String _initials(String name) {
    final parts = name.split(RegExp(r'[\s,]+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts[0][0].toUpperCase();
    return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
  }
}

class _PersonnelForm extends StatefulWidget {
  const _PersonnelForm({required this.api, required this.session, required this.onSaved, this.existing});
  final MiscApi api;
  final AppSession session;
  final VoidCallback onSaved;
  final Personnel? existing;

  @override
  State<_PersonnelForm> createState() => _PersonnelFormState();
}

class _PersonnelFormState extends State<_PersonnelForm> {
  late final TextEditingController _idNumber;
  late final TextEditingController _firstName;
  late final TextEditingController _middleName;
  late final TextEditingController _lastName;
  late final TextEditingController _title;
  late final TextEditingController _department;
  late final TextEditingController _email;
  late final TextEditingController _mobile;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _idNumber = TextEditingController(text: widget.existing?.id ?? '');
    _firstName = TextEditingController(text: widget.existing?.firstName ?? '');
    _middleName = TextEditingController();
    _lastName = TextEditingController(text: widget.existing?.lastName ?? '');
    _title = TextEditingController(text: widget.existing?.title ?? '');
    _department = TextEditingController(text: widget.existing?.department ?? '');
    _email = TextEditingController(text: widget.existing?.email ?? '');
    _mobile = TextEditingController(text: widget.existing?.mobile ?? '');
  }

  @override
  void dispose() {
    _idNumber.dispose(); _firstName.dispose(); _middleName.dispose();
    _lastName.dispose(); _title.dispose(); _department.dispose();
    _email.dispose(); _mobile.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_idNumber.text.trim().isEmpty || _firstName.text.trim().isEmpty || _lastName.text.trim().isEmpty) {
      setState(() => _error = 'ID Number, First Name, and Last Name are required.');
      return;
    }
    setState(() { _saving = true; _error = null; });
    try {
      await widget.api.personnelSave(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: _idNumber.text.trim(),
        firstName: _firstName.text.trim(),
        middleName: _middleName.text.trim(),
        lastName: _lastName.text.trim(),
        title: _title.text.trim(),
        department: _department.text.trim(),
        email: _email.text.trim(),
        mobile: _mobile.text.trim(),
      );
      if (!mounted) return;
      widget.onSaved();
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      setState(() { _saving = false; _error = e.toString(); });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.existing != null;
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(24, 12, 24, 32 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(color: AppInk.rule, borderRadius: BorderRadius.circular(999)),
              ),
            ),
            const SizedBox(height: 20),
            Text(isEdit ? 'Edit Personnel' : 'Add Personnel',
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading)),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
              const SizedBox(height: 12),
            ],
            AppInput(controller: _idNumber, label: 'ID Number *', hint: 'e.g. AB-002', prefixIcon: Icons.badge_outlined),
            const SizedBox(height: 14),
            AppInput(controller: _firstName, label: 'First Name *', hint: 'Enter first name', prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _middleName, label: 'Middle Name', hint: 'Enter middle name', prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _lastName, label: 'Last Name *', hint: 'Enter last name', prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _title, label: 'Position', hint: 'e.g. Faculty', prefixIcon: Icons.work_outlined),
            const SizedBox(height: 14),
            AppInput(controller: _department, label: 'Department', hint: 'e.g. Admin', prefixIcon: Icons.business_outlined),
            const SizedBox(height: 14),
            AppInput(controller: _email, label: 'Email', hint: 'you@email.com', prefixIcon: Icons.email_outlined, keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 14),
            AppInput(controller: _mobile, label: 'Mobile', hint: '09XX XXX XXXX', prefixIcon: Icons.phone_outlined, keyboardType: TextInputType.phone),
            const SizedBox(height: 20),
            AppButton(
              label: isEdit ? 'Save Changes' : 'Add Personnel',
              fullWidth: true, size: AppButtonSize.lg,
              loading: _saving, disabled: _saving, onTap: _save,
            ),
          ],
        ),
      ),
    );
  }
}
