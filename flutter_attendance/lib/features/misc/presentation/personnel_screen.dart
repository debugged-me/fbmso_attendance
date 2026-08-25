import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../misc/data/misc_api.dart';
import '../../misc/domain/misc_models.dart';

/// Personnel directory — shows officials and staff.
class PersonnelScreen extends StatefulWidget {
  const PersonnelScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<PersonnelScreen> createState() => _PersonnelScreenState();
}

class _PersonnelScreenState extends State<PersonnelScreen> {
  late final MiscApi _api;
  List<Personnel> _personnel = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await _api.personnel(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _personnel = list;
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

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Personnel',
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
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
                      : _personnel.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.people_outline_rounded,
                                title: 'No personnel found',
                                subtitle:
                                    'Personnel and officials will appear here.',
                              ),
                            ])
                          : ListView.builder(
                              padding:
                                  const EdgeInsets.fromLTRB(16, 12, 16, 24),
                              itemCount: _personnel.length,
                              itemBuilder: (context, i) {
                                final p = _personnel[i];
                                return _PersonnelCard(person: p);
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
  const _PersonnelCard({required this.person});
  final Personnel person;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipOval(
              child: SizedBox(
                width: 56,
                height: 56,
                child: person.photoUrl.isNotEmpty
                    ? Image.network(
                        person.photoUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          color: AppInk.accent.withValues(alpha: 0.12),
                          child: const Icon(Icons.person_rounded,
                              color: AppInk.accent, size: 28),
                        ),
                      )
                    : Container(
                        color: AppInk.accent.withValues(alpha: 0.12),
                        child: const Icon(Icons.person_rounded,
                            color: AppInk.accent, size: 28),
                      ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    person.fullName,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                    ),
                  ),
                  if (person.title.isNotEmpty) ...[
                    const SizedBox(height: 3),
                    Text(
                      person.title,
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppInk.accent,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                  if (person.bio.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Text(
                      person.bio,
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppInk.muted,
                        height: 1.4,
                      ),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
