import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../misc/data/misc_api.dart';
import '../../misc/domain/misc_models.dart';

/// Registered Students screen — mirrors the web Page/profileList.
/// Shows students from studentsignup with search, pagination, and delete.
class RegisteredStudentsScreen extends StatefulWidget {
  const RegisteredStudentsScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<RegisteredStudentsScreen> createState() =>
      _RegisteredStudentsScreenState();
}

class _RegisteredStudentsScreenState extends State<RegisteredStudentsScreen> {
  late final MiscApi _api;
  final List<RegisteredStudent> _rows = [];
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
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await _api.registeredStudents(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        limit: _pageSize,
        offset: 0,
        search: _search,
      );
      if (!mounted) return;
      setState(() {
        _rows
          ..clear()
          ..addAll(result.rows);
        _total = result.total;
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

  Future<void> _loadMore() async {
    if (_loadingMore || _rows.length >= _total) return;
    setState(() => _loadingMore = true);
    try {
      final result = await _api.registeredStudents(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        limit: _pageSize,
        offset: _rows.length,
        search: _search,
      );
      if (!mounted) return;
      setState(() {
        _rows.addAll(result.rows);
        _total = result.total;
        _loadingMore = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
    }
  }

  void _onSearchChanged(String v) {
    _search = v;
    _load();
  }

  Future<void> _delete(RegisteredStudent s) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Student'),
        content: Text(
            'Delete "${s.fullName}" (${s.studentNumber})?\nThis will remove the student account and signup record.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await _api.registeredStudentDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        studentNumber: s.studentNumber,
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

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Registered Students',
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Search name, ID, email...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppInk.muted),
                suffixIcon: _search.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 20),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
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
                  Text(
                    '${_rows.length} of $_total students',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppInk.muted,
                    ),
                  ),
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
                                title: 'No students found',
                                subtitle: 'Try a different search term.',
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
                                final r = _rows[i];
                                return _StudentCard(
                                  student: r,
                                  onDelete: () => _delete(r),
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

class _StudentCard extends StatelessWidget {
  const _StudentCard({required this.student, required this.onDelete});
  final RegisteredStudent student;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final initials = _initials(student.fullName);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: AppInk.accent.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Text(initials,
                    style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppInk.accent)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(student.fullName,
                      style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: AppInk.heading),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      Text(student.studentNumber,
                          style: const TextStyle(fontSize: 12, color: AppInk.muted)),
                      if (student.course.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppInk.accent.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(student.course,
                              style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: AppInk.accent)),
                        ),
                      ],
                      if (student.yearLevel.isNotEmpty) ...[
                        const SizedBox(width: 4),
                        Text('${student.yearLevel} yr',
                            style: const TextStyle(fontSize: 11, color: AppInk.muted)),
                      ],
                    ],
                  ),
                  if (student.email.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(student.email,
                        style: const TextStyle(fontSize: 12, color: AppInk.muted)),
                  ],
                ],
              ),
            ),
            if (student.status.isNotEmpty)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: (student.status.toLowerCase() == 'enrolled'
                          ? AppInk.positive
                          : AppInk.muted)
                      .withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(student.status,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: student.status.toLowerCase() == 'enrolled'
                          ? AppInk.positive
                          : AppInk.muted,
                    )),
              ),
            IconButton(
              icon: const Icon(Icons.delete_outline_rounded, color: AppInk.critical, size: 20),
              onPressed: onDelete,
            ),
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
