import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Grades report, grouped by SY/Semester. Cache-first.
class GradesScreen extends StatefulWidget {
  const GradesScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<GradesScreen> createState() => _GradesScreenState();
}

class _GradesScreenState extends State<GradesScreen> {
  late final StudentApi _api;
  List<Grade> _grades = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _api = StudentApi();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final list = await _api.grades(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _grades = list;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Group by SY + Semester
    final groups = <String, List<Grade>>{};
    for (final g in _grades) {
      final key = '${g.sy} • ${g.semester}';
      groups.putIfAbsent(key, () => []).add(g);
    }

    return Scaffold(
      appBar: AppBar(title: const Text('My Grades')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _grades.isEmpty
                      ? const Center(
                          child: Text('No grades recorded yet.',
                              style: TextStyle(color: AppTheme.textMuted)),
                        )
                      : ListView(
                          padding: const EdgeInsets.all(16),
                          children: groups.entries.map((e) {
                            return _GradeGroup(title: e.key, grades: e.value);
                          }).toList(),
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _GradeGroup extends StatelessWidget {
  const _GradeGroup({required this.title, required this.grades});
  final String title;
  final List<Grade> grades;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 12, bottom: 8),
          child: Text(title, style: Theme.of(context).textTheme.titleMedium),
        ),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Table(
              columnWidths: const {
                0: FlexColumnWidth(3),
                1: FlexColumnWidth(1),
                2: FlexColumnWidth(1),
                3: FlexColumnWidth(1),
                4: FlexColumnWidth(1),
                5: FlexColumnWidth(1),
              },
              children: [
                TableRow(
                  decoration: const BoxDecoration(
                    border: Border(
                        bottom: BorderSide(color: AppTheme.cardBorder)),
                  ),
                  children: [
                    _hdr('Subject'),
                    _hdr('Pre'),
                    _hdr('Mid'),
                    _hdr('PF'),
                    _hdr('Fin'),
                    _hdr('Avg'),
                  ],
                ),
                ...grades.map((g) => TableRow(
                      children: [
                        _cell('${g.subjectCode}\n${g.description}', small: true),
                        _cell(g.prelim?.toStringAsFixed(1) ?? '—'),
                        _cell(g.midterm?.toStringAsFixed(1) ?? '—'),
                        _cell(g.preFinal?.toStringAsFixed(1) ?? '—'),
                        _cell(g.finalGrade?.toStringAsFixed(1) ?? '—'),
                        _cell(g.average?.toStringAsFixed(1) ?? '—',
                            bold: true),
                      ],
                    )),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _hdr(String t) => Padding(
        padding: const EdgeInsets.only(bottom: 6, right: 4),
        child: Text(t,
            style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: AppTheme.textMuted)),
      );

  Widget _cell(String t, {bool small = false, bool bold = false}) => Padding(
        padding: const EdgeInsets.only(top: 4, bottom: 4, right: 4),
        child: Text(
          t,
          style: TextStyle(
            fontSize: small ? 11 : 13,
            fontWeight: bold ? FontWeight.w700 : FontWeight.w400,
          ),
        ),
      );
}
