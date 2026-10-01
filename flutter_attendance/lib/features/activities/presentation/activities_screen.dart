import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/domain/staff_permissions.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/activity_form_screen.dart';
import '../../attendance/presentation/activity_state_style.dart';
import 'activity_detail_sheet.dart';
import '../../../core/theme/app_icons.dart';

/// Activities list. Tapping an activity opens a detail sheet with actions:
/// - Students: "Show my QR" or "Scan Poster QR" (for self check-in).
/// - Admins: "Scan Students" or "View Attendance Logs".
class ActivitiesScreen extends StatefulWidget {
  const ActivitiesScreen({
    super.key,
    required this.session,
    this.showWelcomeHeader = false,
    this.menuButton,
  });

  final AppSession session;
  final bool showWelcomeHeader;
  final Widget? menuButton;

  @override
  State<ActivitiesScreen> createState() => _ActivitiesScreenState();
}

class _ActivitiesScreenState extends State<ActivitiesScreen> {
  late final AttendanceApi _api;
  List<Activity> _activities = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await _api.activities(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _activities = list;
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

  void _openActivitySheet(Activity activity) {
    showActivityDetailSheet(context, activity, widget.session);
  }

  /// Web parity: the activities page has a "Create Activity" button for
  /// roles allowed to manage activities (see StaffPermissions).
  Future<void> _createActivity() async {
    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => ActivityFormScreen(session: widget.session),
      ),
    );
    if (result == true) _load();
  }

  int _filter = 0;
  static const _filters = ['All', 'Open', 'Upcoming', 'Past'];

  bool _matches(Activity a) {
    switch (_filter) {
      case 1:
        return a.isOpen;
      case 2:
        return !a.isOpen && a.state == 'scheduled';
      case 3:
        return !a.isOpen && a.state != 'scheduled';
    }
    return true;
  }

  @override
  Widget build(BuildContext context) {
    final shown = _activities.where(_matches).toList();
    final open = shown.where((a) => a.isOpen).toList();
    final upcoming =
        shown.where((a) => !a.isOpen && a.state == 'scheduled').toList();
    final past =
        shown.where((a) => !a.isOpen && a.state != 'scheduled').toList();
    final openCount = _activities.where((a) => a.isOpen).length;
    final canManage =
        StaffPermissions.of(widget.session).canManageActivities;

    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      showBackButton: false,
      leading: widget.menuButton,
      floatingActionButton: canManage
          ? FloatingActionButton.extended(
              onPressed: _createActivity,
              icon: const Icon(AppIcons.add_rounded, size: 20),
              label: const Text('New activity'),
            )
          : null,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const ListSkeleton(itemCount: 5)
                  : _error != null
                      ? ListView(
                          children: [
                            const SizedBox(height: 80),
                            AppEmptyState(
                              icon: AppIcons.cloud_off_rounded,
                              title: "Couldn't load activities",
                              subtitle: _error,
                              action: 'Try again',
                              onAction: _load,
                            ),
                          ],
                        )
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(0, 4, 0, 96),
                          children: [
                            Padding(
                              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                              child: AppPageHeader(
                                title: widget.showWelcomeHeader
                                    ? 'Hi, ${widget.session.firstName.isNotEmpty ? widget.session.firstName : widget.session.displayName}'
                                    : 'Activities',
                                subtitle: _activities.isEmpty
                                    ? null
                                    : '$openCount open · ${_activities.length} total',
                                padding: const EdgeInsets.fromLTRB(4, 4, 4, 8),
                              ),
                            ),
                            if (_activities.isNotEmpty)
                              AppFilterChips(
                                labels: _filters,
                                selected: _filter,
                                onSelected: (i) => setState(() => _filter = i),
                              ),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  if (open.isNotEmpty) ...[
                                    const _SectionLabel('Open now'),
                                    ...open.map(_card),
                                  ],
                                  if (upcoming.isNotEmpty) ...[
                                    const _SectionLabel('Upcoming'),
                                    ...upcoming.map(_card),
                                  ],
                                  if (past.isNotEmpty) ...[
                                    const _SectionLabel('Past and closed'),
                                    ...past.map(_card),
                                  ],
                                  if (shown.isEmpty)
                                    AppEmptyState(
                                      icon: AppIcons.event_busy_rounded,
                                      title: _activities.isEmpty
                                          ? 'No activities yet'
                                          : 'Nothing here',
                                      subtitle: _activities.isEmpty
                                          ? 'Activities will appear here once created.'
                                          : 'No activities match this filter.',
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _card(Activity a) => _ActivityCard(
        activity: a,
        session: widget.session,
        onTap: () => _openActivitySheet(a),
      );
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(2, 16, 2, 10),
      child: Text(text, style: AppType.headline.copyWith(fontSize: 16.5)),
    );
  }
}

class _ActivityCard extends StatelessWidget {
  const _ActivityCard({
    required this.activity,
    required this.session,
    required this.onTap,
  });

  final Activity activity;
  final AppSession session;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final time = _timeRange(activity);
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        onTap: onTap,
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ActivityDateBadge(
              date: activity.activityDate,
              highlight: activity.isOpen,
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(activity.title, style: AppType.row),
                      ),
                      const SizedBox(width: 8),
                      ActivityStatePill(activity: activity, dense: true),
                    ],
                  ),
                  const SizedBox(height: 8),
                  if (time.isNotEmpty)
                    _Meta(icon: AppIcons.clock, text: time),
                  if (activity.location.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    _Meta(icon: AppIcons.map_pin, text: activity.location),
                  ],
                  if (activity.program.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    _Meta(
                        icon: AppIcons.school_outlined,
                        text: activity.program),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _timeRange(Activity a) {
    final start = _short(a.startTime);
    final end = _short(a.endTime);
    if (start.isEmpty) return '';
    return end.isEmpty ? start : '$start – $end';
  }

  String _short(String t) {
    if (t.isEmpty) return '';
    final parts = t.split(':');
    if (parts.length < 2) return t;
    var h = int.tryParse(parts[0]) ?? 0;
    final m = parts[1];
    final suffix = h >= 12 ? 'PM' : 'AM';
    h = h % 12 == 0 ? 12 : h % 12;
    return '$h:$m $suffix';
  }
}

class _Meta extends StatelessWidget {
  const _Meta({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 15, color: AppInk.muted),
        const SizedBox(width: 6),
        Expanded(
          child: Text(
            text,
            style: AppType.rowSub,
          ),
        ),
      ],
    );
  }
}
