import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';

/// Student-facing list of activities with a self check-in button per row.
/// Reads are cache-first so the list renders even with no signal.
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
    return Scaffold(
      appBar: AppBar(title: const Text('Activities')),
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
                          : ListView.builder(
                              itemCount: _activities.length,
                              itemBuilder: (context, i) {
                                final a = _activities[i];
                                return _ActivityTile(
                                  activity: a,
                                  onCheckIn: () => _checkin(a, 'in'),
                                  onCheckOut: () => _checkin(a, 'out'),
                                );
                              },
                            ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ActivityTile extends StatelessWidget {
  const _ActivityTile({
    required this.activity,
    required this.onCheckIn,
    required this.onCheckOut,
  });

  final Activity activity;
  final VoidCallback onCheckIn;
  final VoidCallback onCheckOut;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(activity.title,
                      style: Theme.of(context).textTheme.titleMedium),
                ),
                if (activity.isOpen)
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppTheme.success.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text('OPEN',
                        style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.success)),
                  )
                else
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppTheme.textMuted.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text('CLOSED',
                        style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.textMuted)),
                  ),
              ],
            ),
            const SizedBox(height: 4),
            if (activity.activityDate.isNotEmpty)
              Text(activity.activityDate,
                  style: Theme.of(context).textTheme.bodySmall),
            if (activity.location.isNotEmpty)
              Text(activity.location,
                  style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: FilledButton.icon(
                    onPressed: activity.isOpen ? onCheckIn : null,
                    icon: const Icon(Icons.login, size: 18),
                    label: const Text('Check in'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: activity.isOpen ? onCheckOut : null,
                    icon: const Icon(Icons.logout, size: 18),
                    label: const Text('Check out'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(32),
        child: Text('No activities yet.',
            style: TextStyle(color: AppTheme.textMuted)),
      ),
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
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 16),
          FilledButton(onPressed: onRetry, child: const Text('Retry')),
        ],
      ),
    );
  }
}
