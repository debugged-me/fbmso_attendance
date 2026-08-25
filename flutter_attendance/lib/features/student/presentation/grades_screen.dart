import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
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

    return AppScaffold(
      title: 'My Grades',
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _grades.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            AppEmptyState(
                              icon: Icons.grade_outlined,
                              title: 'No grades recorded yet',
                              subtitle:
                                  'Grades will appear here once they are posted.',
                            ),
                          ],
                        )
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
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
        AppSectionHeader(title: title),
        const SizedBox(height: 10),
        AppCard(
          padding: const EdgeInsets.all(16),
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
                  border: Border(bottom: BorderSide(color: AppInk.rule)),
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
                      _cell(g.average?.toStringAsFixed(1) ?? '—', bold: true),
                    ],
                  )),
            ],
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _hdr(String t) => Padding(
        padding: const EdgeInsets.only(bottom: 8, right: 4),
        child: Text(
          t,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.8,
            color: AppInk.muted,
          ),
        ),
      );

  Widget _cell(String t, {bool small = false, bool bold = false}) => Padding(
        padding: const EdgeInsets.only(top: 6, bottom: 6, right: 4),
        child: Text(
          t,
          style: TextStyle(
            fontSize: small ? 11 : 13,
            fontWeight: bold ? FontWeight.w700 : FontWeight.w400,
            color: bold ? AppInk.heading : AppInk.body,
            height: 1.3,
          ),
        ),
      );
}
