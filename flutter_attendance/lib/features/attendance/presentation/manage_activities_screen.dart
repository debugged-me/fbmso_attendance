import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';
import 'activity_form_screen.dart';
import 'activity_state_style.dart';
import 'activity_poster_screen.dart';

/// Staff activity management — list all activities with create / edit /
/// delete actions.
class ManageActivitiesScreen extends StatefulWidget {
  const ManageActivitiesScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<ManageActivitiesScreen> createState() => _ManageActivitiesScreenState();
}

class _ManageActivitiesScreenState extends State<ManageActivitiesScreen> {
  late final AttendanceApi _api;
  List<Activity> _activities = [];
  bool _loading = true;
  String? _error;
  bool _posterMode = false;
  bool _togglingMode = false;

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
      final pm = await _api.posterMode(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _activities = list;
        _posterMode = pm;
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

  Future<void> _togglePosterMode(bool value) async {
    setState(() => _togglingMode = true);
    try {
      final result = await _api.setPosterMode(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        on: value,
      );
      if (!mounted) return;
      setState(() {
        _posterMode = result;
        _togglingMode = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result ? 'Poster Mode enabled' : 'Poster Mode disabled'),
          duration: const Duration(seconds: 2),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _togglingMode = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }

  Future<void> _openForm([Activity? activity]) async {
    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => ActivityFormScreen(
          session: widget.session,
          activity: activity,
        ),
      ),
    );
    if (result == true) _load();
  }

  /// Quick manual override from the list, mirroring the web list's lock button.
  Future<void> _toggleStatus(Activity a) async {
    final opening = !a.isOpen;

    // Reopening something the clock closed only sticks if auto-close is lifted.
    final liftAutoClose = opening && a.autoClosed;

    if (liftAutoClose) {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Reopen check-ins?'),
          content: Text(
            '“${a.title}” already ended. Reopening it also turns OFF auto-close, '
            'so it will stay open until you close it again.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: const Text('Cancel'),
            ),
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(true),
              child: const Text('Reopen'),
            ),
          ],
        ),
      );
      if (confirmed != true) return;
    }

    final res = await _api.setActivityStatus(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      activityId: a.activityId,
      status: opening ? ActivityStatus.open : ActivityStatus.closed,
      liftAutoClose: liftAutoClose,
    );

    if (!mounted) return;
    if (res.ok) {
      _load();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res.message.isEmpty
            ? 'Could not change the activity status.'
            : res.message)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Manage Activities',
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          // Poster Mode toggle — mirrors the web sidebar switch
          Container(
            margin: const EdgeInsets.fromLTRB(16, 8, 16, 4),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: _posterMode
                  ? AppInk.accent.withValues(alpha: 0.08)
                  : const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: _posterMode
                    ? AppInk.accent.withValues(alpha: 0.3)
                    : AppInk.rule,
              ),
            ),
            child: Row(
              children: [
                Icon(
                  _posterMode ? Icons.image_rounded : Icons.qr_code_scanner_rounded,
                  size: 22,
                  color: _posterMode ? AppInk.accent : AppInk.muted,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Poster Mode',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: AppInk.heading,
                        ),
                      ),
                      Text(
                        _posterMode
                            ? 'Students scan activity QR to self-check in'
                            : 'Admin scans student QR to check in',
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppInk.muted,
                        ),
                      ),
                    ],
                  ),
                ),
                Switch.adaptive(
                  value: _posterMode,
                  onChanged: _togglingMode ? null : _togglePosterMode,
                  activeTrackColor: AppInk.accent,
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? Center(
                          child: Padding(
                            padding: const EdgeInsets.all(32),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.cloud_off_rounded,
                                    size: 48, color: AppInk.muted),
                                const SizedBox(height: 14),
                                Text(_error!,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
                                        color: AppInk.muted)),
                                const SizedBox(height: 16),
                                AppButton(label: 'Retry', onTap: _load),
                              ],
                            ),
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 80),
                          itemCount: _activities.length,
                          itemBuilder: (context, i) {
                            final a = _activities[i];
                            return _ActivityManageCard(
                              activity: a,
                              posterMode: _posterMode,
                              session: widget.session,
                              onEdit: () => _openForm(a),
                              onToggleStatus: () => _toggleStatus(a),
                            );
                          },
                        ),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _openForm(),
        backgroundColor: AppInk.accent,
        foregroundColor: Colors.white,
        child: const Icon(Icons.add_rounded),
      ),
    );
  }
}

class _ActivityManageCard extends StatelessWidget {
  const _ActivityManageCard({
    required this.activity,
    required this.onEdit,
    required this.onToggleStatus,
    required this.posterMode,
    required this.session,
  });

  final Activity activity;
  final VoidCallback onEdit;
  final VoidCallback onToggleStatus;
  final bool posterMode;
  final AppSession session;

  void _openPoster(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ActivityPosterScreen(
          session: session,
          activityId: activity.activityId,
          activityTitle: activity.title,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isOpen = activity.isOpen;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        onTap: onEdit,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        activity.title,
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: AppInk.heading,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.event_rounded,
                              size: 14, color: AppInk.muted),
                          const SizedBox(width: 4),
                          Text(
                            activity.activityDate,
                            style: const TextStyle(
                                fontSize: 12, color: AppInk.muted),
                          ),
                          if (activity.location.isNotEmpty) ...[
                            const SizedBox(width: 10),
                            Icon(Icons.place_outlined,
                                size: 14, color: AppInk.muted),
                            const SizedBox(width: 4),
                            Flexible(
                              child: Text(
                                activity.location,
                                style: const TextStyle(
                                    fontSize: 12, color: AppInk.muted),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                ActivityStatePill(activity: activity),
                const SizedBox(width: 6),
                const Icon(Icons.edit_rounded, color: AppInk.muted, size: 20),
              ],
            ),

            // ── Why it is closed ────────────────────────────────────
            if (!isOpen) ...[
              const SizedBox(height: 10),
              ActivityClosedNotice(activity: activity),
            ],

            // ── Quick open/close override ───────────────────────────
            const SizedBox(height: 10),
            AppButton(
              label: isOpen ? 'Close check-ins' : 'Open check-ins',
              icon: isOpen ? Icons.lock_outline_rounded : Icons.lock_open_rounded,
              size: AppButtonSize.sm,
              style: AppButtonStyle.outline,
              fullWidth: true,
              onTap: onToggleStatus,
            ),
            // ── Poster mode action row ──────────────────────────────
            if (posterMode) ...[
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: AppButton(
                      label: 'View Poster QR',
                      icon: Icons.qr_code_2_rounded,
                      size: AppButtonSize.sm,
                      fullWidth: true,
                      onTap: () => _openPoster(context),
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
}
