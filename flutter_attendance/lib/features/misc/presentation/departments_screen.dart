import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Departments / Courses management screen.
class DepartmentsScreen extends StatefulWidget {
  const DepartmentsScreen({super.key, required this.session});
  final AppSession session;

  @override
  State<DepartmentsScreen> createState() => _DepartmentsScreenState();
}

class _DepartmentsScreenState extends State<DepartmentsScreen> {
  late final MiscApi _api;
  List<Department> _rows = [];
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
      final r = await _api.departments(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        limit: _pageSize, offset: 0, search: _search,
      );
      if (!mounted) return;
      setState(() { _rows = r.rows; _total = r.total; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _rows.length >= _total) return;
    setState(() => _loadingMore = true);
    try {
      final r = await _api.departments(
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

  Future<void> _delete(Department d) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Course'),
        content: Text('Delete "${d.courseDescription}"?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await _api.departmentDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        courseid: d.id,
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
      builder: (ctx) => _DeptForm(api: _api, session: widget.session, onSaved: _load),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Departments',
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
                hintText: 'Search course...',
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
                                icon: Icons.school_outlined,
                                title: 'No courses found',
                                subtitle: 'Tap + to add a course.',
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
                                      child: SizedBox(
                                        width: 24, height: 24,
                                        child: CircularProgressIndicator(strokeWidth: 2.5),
                                      ),
                                    ),
                                  );
                                }
                                final d = _rows[i];
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
                                          child: const Icon(Icons.school_rounded, color: AppInk.accent, size: 22),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(d.courseDescription,
                                                  style: const TextStyle(
                                                      fontSize: 14,
                                                      fontWeight: FontWeight.w700,
                                                      color: AppInk.heading),
                                                  maxLines: 2,
                                                  overflow: TextOverflow.ellipsis),
                                              const SizedBox(height: 4),
                                              Wrap(
                                                spacing: 8,
                                                runSpacing: 4,
                                                children: [
                                                  if (d.courseCode.isNotEmpty)
                                                    Text(d.courseCode,
                                                        style: const TextStyle(fontSize: 12, color: AppInk.muted)),
                                                  if (d.major.isNotEmpty)
                                                    Container(
                                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                      decoration: BoxDecoration(
                                                        color: AppInk.accent.withValues(alpha: 0.08),
                                                        borderRadius: BorderRadius.circular(6),
                                                      ),
                                                      child: Text(d.major,
                                                          style: const TextStyle(
                                                              fontSize: 11,
                                                              fontWeight: FontWeight.w600,
                                                              color: AppInk.accent)),
                                                    ),
                                                  if (d.duration.isNotEmpty)
                                                    Text(d.duration,
                                                        style: const TextStyle(fontSize: 11, color: AppInk.muted)),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                        IconButton(
                                          icon: const Icon(Icons.delete_outline_rounded, color: AppInk.critical, size: 20),
                                          onPressed: () => _delete(d),
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

class _DeptForm extends StatefulWidget {
  const _DeptForm({required this.api, required this.session, required this.onSaved});
  final MiscApi api;
  final AppSession session;
  final VoidCallback onSaved;

  @override
  State<_DeptForm> createState() => _DeptFormState();
}

class _DeptFormState extends State<_DeptForm> {
  late final TextEditingController _code;
  late final TextEditingController _desc;
  late final TextEditingController _major;
  late final TextEditingController _duration;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _code = TextEditingController();
    _desc = TextEditingController();
    _major = TextEditingController();
    _duration = TextEditingController();
  }

  @override
  void dispose() {
    _code.dispose(); _desc.dispose(); _major.dispose(); _duration.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_code.text.trim().isEmpty || _desc.text.trim().isEmpty) {
      setState(() => _error = 'Course Code and Description are required.');
      return;
    }
    setState(() { _saving = true; _error = null; });
    try {
      await widget.api.departmentCreate(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        courseCode: _code.text.trim(),
        courseDescription: _desc.text.trim(),
        major: _major.text.trim(),
        duration: _duration.text.trim(),
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
            const Text('Add Course',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading)),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
              const SizedBox(height: 12),
            ],
            AppInput(controller: _code, label: 'Course Code *', hint: 'e.g. BSBA', prefixIcon: Icons.code_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _desc, label: 'Course Description *', hint: 'e.g. Bachelor of Science...', prefixIcon: Icons.school_outlined),
            const SizedBox(height: 14),
            AppInput(controller: _major, label: 'Major', hint: 'Major (optional)', prefixIcon: Icons.book_outlined),
            const SizedBox(height: 14),
            AppInput(controller: _duration, label: 'Duration', hint: 'e.g. 4 years', prefixIcon: Icons.timer_outlined),
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
