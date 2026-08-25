import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Sections management screen — mirrors Page/manageSections.
class SectionsScreen extends StatefulWidget {
  const SectionsScreen({super.key, required this.session});
  final AppSession session;

  @override
  State<SectionsScreen> createState() => _SectionsScreenState();
}

class _SectionsScreenState extends State<SectionsScreen> {
  late final MiscApi _api;
  List<Section> _rows = [];
  bool _loading = true;
  String? _error;
  String _search = '';
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _load();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await _api.sections(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        search: _search,
      );
      if (!mounted) return;
      setState(() { _rows = r.rows; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  void _onSearchChanged(String v) { _search = v; _load(); }

  Future<void> _delete(Section s) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Section'),
        content: Text('Delete section "${s.section}"?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await _api.sectionDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: s.id,
      );
      _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  void _showForm() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _SectionForm(api: _api, session: widget.session, onSaved: _load),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Sections',
      showBackButton: true,
      actions: [
        IconButton(icon: const Icon(Icons.add_rounded), onPressed: _showForm),
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
                hintText: 'Search section...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppInk.muted),
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
                      : _rows.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.group_outlined,
                                title: 'No sections found',
                                subtitle: 'Tap + to add a section.',
                              ),
                            ])
                          : ListView.builder(
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount: _rows.length,
                              itemBuilder: (context, i) {
                                final s = _rows[i];
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
                                          child: const Icon(Icons.group_rounded, color: AppInk.accent, size: 22),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(s.section,
                                                  style: const TextStyle(
                                                      fontSize: 14,
                                                      fontWeight: FontWeight.w700,
                                                      color: AppInk.heading)),
                                              const SizedBox(height: 4),
                                              Wrap(
                                                spacing: 8,
                                                runSpacing: 4,
                                                children: [
                                                  if (s.courseName.isNotEmpty)
                                                    Text(s.courseName,
                                                        style: const TextStyle(fontSize: 12, color: AppInk.muted),
                                                        maxLines: 1,
                                                        overflow: TextOverflow.ellipsis),
                                                  if (s.yearLevel.isNotEmpty)
                                                    Container(
                                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                      decoration: BoxDecoration(
                                                        color: AppInk.accent.withValues(alpha: 0.08),
                                                        borderRadius: BorderRadius.circular(6),
                                                      ),
                                                      child: Text('${s.yearLevel} Year',
                                                          style: const TextStyle(
                                                              fontSize: 11,
                                                              fontWeight: FontWeight.w600,
                                                              color: AppInk.accent)),
                                                    ),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                        IconButton(
                                          icon: const Icon(Icons.delete_outline_rounded, color: AppInk.critical, size: 20),
                                          onPressed: () => _delete(s),
                                        ),
                                      ],
                                    ),
                                  ),
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

class _SectionForm extends StatefulWidget {
  const _SectionForm({required this.api, required this.session, required this.onSaved});
  final MiscApi api;
  final AppSession session;
  final VoidCallback onSaved;

  @override
  State<_SectionForm> createState() => _SectionFormState();
}

class _SectionFormState extends State<_SectionForm> {
  late final TextEditingController _section;
  String _yearLevel = '';
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _section = TextEditingController();
  }

  @override
  void dispose() {
    _section.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_section.text.trim().isEmpty) {
      setState(() => _error = 'Section name is required.');
      return;
    }
    setState(() { _saving = true; _error = null; });
    try {
      await widget.api.sectionCreate(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        section: _section.text.trim(),
        yearLevel: _yearLevel,
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
            const Text('Add Section',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading)),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
              const SizedBox(height: 12),
            ],
            AppInput(controller: _section, label: 'Section Name *', hint: 'e.g. BA1A', prefixIcon: Icons.group_add_outlined),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _yearLevel.isEmpty ? null : _yearLevel,
              decoration: InputDecoration(
                labelText: 'Year Level',
                prefixIcon: const Icon(Icons.stairs_outlined, size: 20, color: AppInk.muted),
                filled: true, fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
              ),
              items: ['1st', '2nd', '3rd', '4th']
                  .map((s) => DropdownMenuItem(value: s, child: Text('$s Year')))
                  .toList(),
              onChanged: (v) => setState(() => _yearLevel = v ?? ''),
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
      ),
    );
  }
}
