import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/notification_bell.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';

/// Activities list with self check-in/out per row. Reads are cache-first
/// so the list renders even with no signal.
class ActivitiesScreen extends StatefulWidget {
  const ActivitiesScreen({super.key, required this.session});

  final AppSession session;

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

  Future<void> _checkin(Activity activity, String direction) async {
    final result = await _api.selfCheckin(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      activityId: activity.activityId,
      direction: direction,
    );
    if (!mounted) return;
    _showResult(result, activity.title);
    _load(); // refresh
  }

  void _showResult(CheckResult r, String title) {
    final (label, color) = _resultStyle(r);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('$title: $label'),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }

  (String, Color) _resultStyle(CheckResult r) {
    switch (r.mode) {
      case 'checked_in':
        return ('Checked in ✓', AppTheme.success);
      case 'checked_out':
        return ('Checked out ✓', AppTheme.success);
      case 'already_in':
        return ('Already checked in — check out first.', AppTheme.warning);
      case 'duplicate':
        return ('Duplicate scan ignored.', AppTheme.warning);
      case 'queued':
        return ('Saved offline — will sync on reconnect.', AppTheme.info);
      default:
        return (r.message ?? 'Something went wrong.', AppTheme.error);
    }
  }

  @override
  Widget build(BuildContext context) {
    final open = _activities.where((a) => a.isOpen).toList();
    final closed = _activities.where((a) => !a.isOpen).toList();

    return Scaffold(
      backgroundColor: AppInk.page,
      appBar: AppBar(
        title: const Text('Activities'),
        actions: const [NotificationBell()],
      ),
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
                      : _activities.isEmpty
                          ? const _EmptyState()
                          : ListView(
                              padding:
                                  const EdgeInsets.fromLTRB(16, 8, 16, 24),
                              children: [
                                if (open.isNotEmpty) ...[
                                  const _SectionLabel('Happening now'),
                                  ...open.map((a) => _ActivityCard(
                                        activity: a,
                                        onCheckIn: () => _checkin(a, 'in'),
                                        onCheckOut: () => _checkin(a, 'out'),
                                      )),
                                ],
                                if (closed.isNotEmpty) ...[
                                  const _SectionLabel('Closed'),
                                  ...closed.map((a) => _ActivityCard(
                                        activity: a,
                                        onCheckIn: null,
                                        onCheckOut: null,
                                      )),
                                ],
                              ],
                            ),
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
    required this.onCheckIn,
    required this.onCheckOut,
  });

  final Activity activity;
  final VoidCallback? onCheckIn;
  final VoidCallback? onCheckOut;

  @override
  Widget build(BuildContext context) {
    final isOpen = activity.isOpen;

    return Container(
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
                _StatusPill(isOpen: isOpen),
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
            if (isOpen) ...[
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: AppButton(
                      label: 'Check in',
                      icon: Icons.login_rounded,
                      onTap: onCheckIn,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: AppButton(
                      label: 'Check out',
                      icon: Icons.logout_rounded,
                      style: AppButtonStyle.outline,
                      onTap: onCheckOut,
                    ),
                  ),
                ],
              ),
            ],
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

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.isOpen});
  final bool isOpen;

  @override
  Widget build(BuildContext context) {
    final color = isOpen ? AppTheme.success : AppInk.muted;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 5),
          Text(
            isOpen ? 'Open' : 'Closed',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
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
