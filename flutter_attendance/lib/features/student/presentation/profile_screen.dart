import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Student profile detail. Cache-first so it renders offline.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late final StudentApi _api;
  StudentProfile? _profile;
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
      final p = await _api.profile(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _profile = p;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'My Profile',
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _profile == null
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            AppEmptyState(
                              icon: Icons.person_outline,
                              title: 'No profile data',
                              subtitle:
                                  'Your profile information will appear here.',
                            ),
                          ],
                        )
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                          children: [
                            AppCard.elevated(
                              padding: const EdgeInsets.all(20),
                              child: Row(
                                children: [
                                  Container(
                                    width: 52,
                                    height: 52,
                                    decoration: BoxDecoration(
                                      color:
                                          AppInk.accent.withValues(alpha: 0.1),
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: ClipRRect(
                                      borderRadius: BorderRadius.circular(16),
                                      child: widget.session.avatar.isNotEmpty
                                          ? Image.network(
                                              widget.session.avatar,
                                              fit: BoxFit.cover,
                                              width: 52,
                                              height: 52,
                                              errorBuilder: (c, e, s) =>
                                                  const Icon(Icons.person,
                                                      size: 26,
                                                      color: AppInk.accent),
                                            )
                                          : const Icon(Icons.person,
                                              size: 26, color: AppInk.accent),
                                    ),
                                  ),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          _profile!.fullName,
                                          style: const TextStyle(
                                            fontSize: 18,
                                            fontWeight: FontWeight.w700,
                                            color: AppInk.heading,
                                            height: 1.25,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          _profile!.studentNumber,
                                          style: const TextStyle(
                                            fontSize: 13.5,
                                            fontWeight: FontWeight.w500,
                                            color: AppInk.muted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Academic',
                              rows: [
                                ('Course', _profile!.course),
                                ('Major', _profile!.major),
                                ('Status', _profile!.status),
                                ('Enrollment Date', _profile!.enrollmentDate),
                              ],
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Personal',
                              rows: [
                                ('Sex', _profile!.sex),
                                ('Birth Date', _profile!.birthDate),
                                ('Civil Status', _profile!.civilStatus),
                                ('Ethnicity', _profile!.ethnicity),
                                ('Religion', _profile!.religion),
                              ],
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Contact',
                              rows: [
                                ('Email', _profile!.email),
                                ('Contact No', _profile!.contactNo),
                              ],
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Address',
                              rows: [
                                ('Sitio', _profile!.sitio),
                                ('Barangay', _profile!.barangay),
                                ('City', _profile!.city),
                                ('Province', _profile!.province),
                              ],
                            ),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.rows});
  final String title;
  final List<(String, String)> rows;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppSectionHeader(title: title),
        const SizedBox(height: 10),
        AppCard(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          child: Column(
            children: [
              for (var i = 0; i < rows.length; i++) ...[
                _Row(label: rows[i].$1, value: rows[i].$2),
                if (i != rows.length - 1) const AppRule(),
              ]
            ],
          ),
        ),
      ],
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 13),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppInk.muted,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '—' : value,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: AppInk.heading,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
