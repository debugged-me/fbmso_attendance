import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
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
    return AppScaffold(
      title: 'My COR',
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _subjects.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            AppEmptyState(
                              icon: Icons.receipt_long_outlined,
                              title: 'No enrolled subjects',
                              subtitle:
                                  'Your enrolled subjects for this semester will appear here.',
                            ),
                          ],
                        )
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                          children: [
                            AppCard.elevated(
                              padding: const EdgeInsets.all(16),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          '$_sy • $_sem',
                                          style: const TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.w700,
                                            color: AppInk.heading,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          '${_subjects.length} subject(s) • $_totalUnits units',
                                          style: const TextStyle(
                                            fontSize: 13,
                                            color: AppInk.muted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Container(
                                    width: 40,
                                    height: 40,
                                    decoration: BoxDecoration(
                                      color: AppInk.accent.withValues(alpha: 0.1),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Icon(Icons.receipt_long,
                                        size: 20, color: AppInk.accent),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 20),
                            AppSectionHeader(title: 'Enrolled Subjects'),
                            const SizedBox(height: 10),
                            ..._subjects.map((s) => Padding(
                                  padding: const EdgeInsets.only(bottom: 12),
                                  child: _SubjectTile(subject: s),
                                )),
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
    return AppCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  subject.description,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppInk.heading,
                    height: 1.3,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              AppChip(
                label: '${subject.units.toStringAsFixed(1)} units',
                tone: AppInk.accent,
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            subject.subjectCode,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: AppInk.muted,
            ),
          ),
          const SizedBox(height: 10),
          if (subject.schedule.isNotEmpty)
            _line(Icons.schedule_outlined, subject.schedule),
          if (subject.room.isNotEmpty)
            _line(Icons.meeting_room_outlined, subject.room),
          if (subject.instructor.isNotEmpty)
            _line(Icons.person_outline, subject.instructor),
          if (subject.section.isNotEmpty)
            _line(Icons.class_outlined, 'Section ${subject.section}'),
        ],
      ),
    );
  }

  Widget _line(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        children: [
          Icon(icon, size: 15, color: AppInk.muted),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(
                fontSize: 12.5,
                color: AppInk.muted,
                height: 1.3,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
