import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Certificate of Registration — enrolled subjects for the active SY/Sem.
/// Cache-first so the student can show their COR even with no signal.
class CorScreen extends StatefulWidget {
  const CorScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<CorScreen> createState() => _CorScreenState();
}

class _CorScreenState extends State<CorScreen> {
  late final StudentApi _api;
  List<EnrolledSubject> _subjects = [];
  double _totalUnits = 0;
  String _sy = '';
  String _sem = '';
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
      final result = await _api.enrolledSubjects(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _subjects = result.subjects;
        _totalUnits = result.totalUnits;
        _sy = result.sy;
        _sem = result.sem;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My COR')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _subjects.isEmpty
                      ? const Center(
                          child: Text('No enrolled subjects for this semester.',
                              style: TextStyle(color: AppTheme.textMuted)),
                        )
                      : ListView(
                          padding: const EdgeInsets.all(16),
                          children: [
                            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('$_sy • $_sem',
                              style: Theme.of(context).textTheme.titleMedium),
                          const SizedBox(height: 4),
                          Text('${_subjects.length} subject(s) • $_totalUnits units',
                              style: Theme.of(context).textTheme.bodySmall),
                        ],
                      ),
                    ),
                    const Icon(Icons.receipt_long, color: AppTheme.midBlue),
                  ],
                ),
              ),
            ),
                            const SizedBox(height: 8),
                            ..._subjects.map((s) => _SubjectTile(subject: s)),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SubjectTile extends StatelessWidget {
  const _SubjectTile({required this.subject});
  final EnrolledSubject subject;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(subject.description,
                      style: Theme.of(context).textTheme.titleMedium),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppTheme.midBlue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    '${subject.units.toStringAsFixed(1)} units',
                    style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.midBlue),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(subject.subjectCode,
                style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 8),
            if (subject.schedule.isNotEmpty)
              _line(Icons.schedule, subject.schedule),
            if (subject.room.isNotEmpty) _line(Icons.room, subject.room),
            if (subject.instructor.isNotEmpty)
              _line(Icons.person, subject.instructor),
            if (subject.section.isNotEmpty)
              _line(Icons.class_, 'Section ${subject.section}'),
          ],
        ),
      ),
    );
  }

  Widget _line(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1),
      child: Row(
        children: [
          Icon(icon, size: 14, color: AppTheme.textMuted),
          const SizedBox(width: 6),
          Expanded(
              child: Text(text,
                  style: const TextStyle(
                      fontSize: 12, color: AppTheme.textMuted))),
        ],
      ),
    );
  }
}
