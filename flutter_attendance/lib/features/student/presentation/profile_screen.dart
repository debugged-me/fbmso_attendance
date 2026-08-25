import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
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
    return Scaffold(
      appBar: AppBar(title: const Text('My Profile')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _profile == null
                      ? const Center(child: Text('No profile data.'))
                      : ListView(
                          padding: const EdgeInsets.all(16),
                          children: [
                            Card(
                              child: Padding(
                                padding: const EdgeInsets.all(20),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(_profile!.fullName,
                                        style: Theme.of(context)
                                            .textTheme
                                            .headlineSmall),
                                    const SizedBox(height: 4),
                                    Text(_profile!.studentNumber,
                                        style: Theme.of(context)
                                            .textTheme
                                            .bodySmall),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),
                            _Section(title: 'Academic', rows: [
                              ('Course', _profile!.course),
                              ('Major', _profile!.major),
                              ('Status', _profile!.status),
                              ('Enrollment Date', _profile!.enrollmentDate),
                            ]),
                            const SizedBox(height: 8),
                            _Section(title: 'Personal', rows: [
                              ('Sex', _profile!.sex),
                              ('Birth Date', _profile!.birthDate),
                              ('Civil Status', _profile!.civilStatus),
                              ('Ethnicity', _profile!.ethnicity),
                              ('Religion', _profile!.religion),
                            ]),
                            const SizedBox(height: 8),
                            _Section(title: 'Contact', rows: [
                              ('Email', _profile!.email),
                              ('Contact No', _profile!.contactNo),
                            ]),
                            const SizedBox(height: 8),
                            _Section(title: 'Address', rows: [
                              ('Sitio', _profile!.sitio),
                              ('Barangay', _profile!.barangay),
                              ('City', _profile!.city),
                              ('Province', _profile!.province),
                            ]),
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
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            ...rows.map((r) => _Row(label: r.$1, value: r.$2)),
          ],
        ),
      ),
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
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label,
                style: const TextStyle(
                    color: AppTheme.textMuted, fontWeight: FontWeight.w600)),
          ),
          Expanded(child: Text(value.isEmpty ? '—' : value)),
        ],
      ),
    );
  }
}
