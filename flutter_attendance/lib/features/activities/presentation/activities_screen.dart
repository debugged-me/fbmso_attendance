import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/activity_state_style.dart';
import 'activity_detail_sheet.dart';

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

  @override
  Widget build(BuildContext context) {
    final open = _activities.where((a) => a.isOpen).toList();
    final closed = _activities.where((a) => !a.isOpen).toList();

    return AppScaffold(
      title: 'Activities',
      showBackButton: false,
      leading: widget.menuButton,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? _ErrorView(message: _error!, onRetry: _load)
                      : ListView(
                          padding:
                              const EdgeInsets.fromLTRB(16, 8, 16, 24),
                          children: [
                            if (widget.showWelcomeHeader) ...[
                              _WelcomeHeader(
                                name: widget.session.displayName,
                                openCount: open.length,
                                totalCount: _activities.length,
                              ),
                              const SizedBox(height: 8),
                            ],
                            if (open.isNotEmpty) ...[
                              const _SectionLabel('Happening now'),
                              ...open.map((a) => _ActivityCard(
                                    activity: a,
                                    session: widget.session,
                                    onTap: () => _openActivitySheet(a),
                                  )),
                            ],
                            if (closed.isNotEmpty) ...[
                              const _SectionLabel('Closed'),
                              ...closed.map((a) => _ActivityCard(
                                    activity: a,
                                    session: widget.session,
                                    onTap: () => _openActivitySheet(a),
                                  )),
                            ],
                            if (_activities.isEmpty &&
                                !widget.showWelcomeHeader)
                              const _EmptyState(),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Welcome header shown at the top of the student Dashboard tab.
class _WelcomeHeader extends StatelessWidget {
  const _WelcomeHeader({
    required this.name,
    required this.openCount,
    required this.totalCount,
  });

  final String name;
  final int openCount;
  final int totalCount;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 12, 4, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Welcome,',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppInk.muted,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            name,
            style: const TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w800,
              color: AppInk.heading,
              height: 1.15,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _StatChip(
                  icon: Icons.event_available_rounded,
                  label: 'Open now',
                  value: '$openCount',
                  tone: AppInk.positive,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatChip(
                  icon: Icons.event_note_rounded,
                  label: 'Total',
                  value: '$totalCount',
                  tone: AppInk.accent,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
        ],
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  const _StatChip({
    required this.icon,
    required this.label,
    required this.value,
    required this.tone,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color tone;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: tone.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 20, color: tone),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: AppInk.muted,
                  ),
                ),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: AppInk.heading,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 16, 4, 10),
      child: Text(
        text.toUpperCase(),
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w800,
          letterSpacing: 0.8,
          color: AppInk.muted,
        ),
      ),
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
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: AppInk.rule),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.03),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      activity.title,
                      style: const TextStyle(
                        fontSize: 15.5,
                        fontWeight: FontWeight.w700,
                        color: AppInk.heading,
                        height: 1.3,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  ActivityStatePill(activity: activity),
                ],
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 14,
                runSpacing: 4,
                children: [
                  if (activity.activityDate.isNotEmpty)
                    _Meta(icon: Icons.event_rounded, text: activity.activityDate),
                  if (activity.startTime.isNotEmpty)
                    _Meta(
                        icon: Icons.schedule_rounded,
                        text: _timeRange(activity)),
                  if (activity.location.isNotEmpty)
                    _Meta(
                        icon: Icons.place_rounded, text: activity.location),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Text(
                    'Tap for actions',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppInk.accent.withValues(alpha: 0.8),
                    ),
                  ),
                  const SizedBox(width: 4),
                  Icon(Icons.chevron_right_rounded,
                      size: 16, color: AppInk.accent.withValues(alpha: 0.8)),
                ],
              ),
            ],
          ),
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
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: AppInk.muted),
        const SizedBox(width: 5),
        Text(
          text,
          style: const TextStyle(
            fontSize: 12.5,
            color: AppInk.muted,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return ListView(
      children: const [
        SizedBox(height: 140),
        Center(
          child: Column(
            children: [
              Icon(Icons.event_busy_rounded,
                  size: 52, color: AppInk.muted),
              SizedBox(height: 14),
              Text('No activities yet.',
                  style: TextStyle(
                      color: AppInk.muted, fontWeight: FontWeight.w600)),
            ],
          ),
        ),
      ],
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            AppButton(label: 'Retry', onTap: onRetry),
          ],
        ),
      ),
    );
  }
}

