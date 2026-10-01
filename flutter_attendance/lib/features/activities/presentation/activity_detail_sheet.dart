import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/utils/time_format.dart';
import '../../auth/domain/app_session.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/activity_form_screen.dart';
import '../../attendance/presentation/activity_poster_screen.dart';
import '../../attendance/presentation/activity_state_style.dart';
import '../../attendance/presentation/poster_scan_screen.dart';
import '../../attendance/presentation/scan_screen.dart';
import '../../auth/domain/staff_permissions.dart';
import '../../student/presentation/my_qr_screen.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../../core/theme/app_icons.dart';

/// Shows the activity detail bottom sheet with role-based actions.
/// - Students: "Scan Poster QR" + "Show My QR"
/// - Staff: the web activities list's row menu — Scan or Poster depending
///   on poster mode, Attendance logs, and Edit for managers.
void showActivityDetailSheet(
  BuildContext context,
  Activity activity,
  AppSession session,
) {
  final isStudent = session.role.isStudentLike;

  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) => ActivityDetailSheet(
      activity: activity,
      isStudent: isStudent,
      permissions: StaffPermissions.of(session),
      // Scan vs Poster follows the school-wide poster mode, as on the web.
      // Offline this resolves to off, which keeps scanning available.
      posterMode: isStudent
          ? Future.value(false)
          : AttendanceApi().posterMode(
              baseUrl: session.baseUrl,
              token: session.token,
            ),
      session: session,
    ),
  );
}

class ActivityDetailSheet extends StatelessWidget {
  const ActivityDetailSheet({
    super.key,
    required this.activity,
    required this.isStudent,
    required this.permissions,
    required this.posterMode,
    required this.session,
  });

  final Activity activity;
  final bool isStudent;
  final StaffPermissions permissions;
  final Future<bool> posterMode;
  final AppSession session;

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

  @override
  Widget build(BuildContext context) {
    final isOpen = activity.isOpen;

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 12, 24, 32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ── Drag handle ──────────────────────────────────────
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppInk.ruleStrong,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // ── Title + status ───────────────────────────────────
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      activity.title,
                      style: AppType.title.copyWith(fontSize: 22),
                    ),
                  ),
                  const SizedBox(width: 10),
                  ActivityStatePill(activity: activity),
                ],
              ),
              const SizedBox(height: 16),

              // ── Why it is closed ─────────────────────────────────
              if (!isOpen) ...[
                ActivityClosedNotice(activity: activity),
                const SizedBox(height: 16),
              ],

              // ── Description ──────────────────────────────────────
              if (activity.description.isNotEmpty) ...[
                Text(
                  activity.description,
                  style: AppType.body.copyWith(fontSize: 14.5),
                ),
                const SizedBox(height: 16),
              ],

              // ── Details ──────────────────────────────────────────
              Container(
                decoration: BoxDecoration(
                  color: AppInk.page,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Column(
                  children: appRuled(
                    [
                      if (activity.activityDate.isNotEmpty)
                        _DetailChip(
                          icon: AppIcons.calendar_blank,
                          label: 'Date',
                          value: activity.activityDate,
                        ),
                      if (activity.startTime.isNotEmpty)
                        _DetailChip(
                          icon: AppIcons.clock,
                          label: 'Time',
                          value: _timeRange(activity),
                        ),
                      if (activity.location.isNotEmpty)
                        _DetailChip(
                          icon: AppIcons.map_pin,
                          label: 'Location',
                          value: activity.location,
                        ),
                      if (activity.program.isNotEmpty)
                        _DetailChip(
                          icon: AppIcons.school_outlined,
                          label: 'For',
                          value: activity.program,
                        ),
                      if (activity.code.isNotEmpty)
                        _DetailChip(
                          icon: AppIcons.tag_rounded,
                          label: 'Code',
                          value: activity.code,
                        ),
                    ],
                    indent: 44,
                  ),
                ),
              ),
              const SizedBox(height: 28),

              // ── Actions ──────────────────────────────────────────
              if (isStudent) ...[
                AppButton(
                  label: isOpen ? 'Scan poster QR' : 'Check-in closed',
                  icon: isOpen
                      ? AppIcons.qr_code_scanner_rounded
                      : AppIcons.lock_outline_rounded,
                  fullWidth: true,
                  size: AppButtonSize.lg,
                  disabled: !isOpen,
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => PosterScanScreen(session: session),
                      ),
                    );
                  },
                ),
                const SizedBox(height: 10),
                AppButton(
                  label: 'Show my QR',
                  icon: AppIcons.qr_code_2_rounded,
                  fullWidth: true,
                  size: AppButtonSize.lg,
                  style: AppButtonStyle.outline,
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => MyQrScreen(session: session),
                      ),
                    );
                  },
                ),
              ] else
                FutureBuilder<bool>(
                  future: posterMode,
                  builder: (context, snap) {
                    if (snap.connectionState != ConnectionState.done) {
                      return const Padding(
                        padding: EdgeInsets.symmetric(vertical: 16),
                        child: Center(
                          child: SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(strokeWidth: 2.5),
                          ),
                        ),
                      );
                    }
                    final pm = snap.data ?? false;
                    final showScan = permissions.canScanActivity(posterMode: pm);
                    final showPoster =
                        permissions.canShowActivityPoster(posterMode: pm);
                    final canViewLogs = permissions.canViewAttendanceLogs;
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (showScan) AppButton(
                          label: isOpen ? 'Scan students' : 'Check-in closed',
                          icon: isOpen
                              ? AppIcons.qr_code_scanner_rounded
                              : AppIcons.lock_outline_rounded,
                          fullWidth: true,
                          size: AppButtonSize.lg,
                          disabled: !isOpen,
                          onTap: () {
                            Navigator.of(context).pop();
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => ScanScreen(
                                  session: session,
                                  activityId: activity.activityId,
                                  activityTitle: activity.title,
                                ),
                              ),
                            );
                          },
                        ),
                        if (showScan && (showPoster || canViewLogs))
                          const SizedBox(height: 10),
                        if (showPoster) AppButton(
                          label: 'Show QR poster',
                          icon: AppIcons.qr_code_2_rounded,
                          fullWidth: true,
                          size: AppButtonSize.lg,
                          onTap: () {
                            Navigator.of(context).pop();
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => ActivityPosterScreen(
                                  session: session,
                                  activityId: activity.activityId,
                                  activityTitle: activity.title,
                                ),
                              ),
                            );
                          },
                        ),
                        if (showPoster && canViewLogs) const SizedBox(height: 10),
                        if (canViewLogs) AppButton(
                          label: 'Attendance logs',
                          icon: AppIcons.history_rounded,
                          fullWidth: true,
                          size: AppButtonSize.lg,
                          style: AppButtonStyle.outline,
                          onTap: () {
                            Navigator.of(context).pop();
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => ActivityLogView(
                                  session: session,
                                  activity: activity,
                                ),
                              ),
                            );
                          },
                        ),
                        // Edit — same action the web activities list exposes to
                        // managers (activities/edit/<id>).
                        if (permissions.canManageActivities) ...[
                          const SizedBox(height: 10),
                          AppButton(
                            label: 'Edit activity',
                            icon: AppIcons.edit_outlined,
                            fullWidth: true,
                            size: AppButtonSize.lg,
                            style: AppButtonStyle.outline,
                            onTap: () {
                              Navigator.of(context).pop();
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => ActivityFormScreen(
                                    session: session,
                                    activity: activity,
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      ],
                    );
                  },
                ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }
}

class _DetailChip extends StatelessWidget {
  const _DetailChip({
    required this.icon,
    required this.label,
    required this.value,
  });
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppInk.muted),
          const SizedBox(width: 12),
          SizedBox(
            width: 72,
            child: Text(label, style: AppType.caption.copyWith(fontSize: 13.5)),
          ),
          Expanded(
            child: Text(
              value,
              style: AppType.row.copyWith(fontSize: 14, fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}

/// Standalone activity log view (used from the activity detail sheet).
class ActivityLogView extends StatefulWidget {
  const ActivityLogView(
      {super.key, required this.session, required this.activity});
  final AppSession session;
  final Activity activity;

  @override
  State<ActivityLogView> createState() => _ActivityLogViewState();
}

class _ActivityLogViewState extends State<ActivityLogView> {
  late final AttendanceApi _api;
  List<Map<String, dynamic>> _logs = [];
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
      final result = await _api.activityLogs(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: widget.activity.activityId,
      );
      if (!mounted) return;
      setState(() {
        _logs = result.rows;
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
      titleWidget: const SizedBox.shrink(),
      showBackButton: true,
      body: Column(
        children: [
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const ListSkeleton(itemCount: 6)
                  : _error != null
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          AppEmptyState(
                            icon: AppIcons.cloud_off_rounded,
                            title: 'Failed to load',
                            subtitle: _error,
                            action: 'Retry',
                            onAction: _load,
                          ),
                        ])
                      : _logs.isEmpty
                          ? ListView(
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              children: [
                                AppPageHeader(title: widget.activity.title),
                                const SizedBox(height: 48),
                                const AppEmptyState(
                                  icon: AppIcons.history_rounded,
                                  title: 'No attendance records',
                                  subtitle: 'No one has checked in yet.',
                                ),
                              ],
                            )
                          : ListView.builder(
                              padding:
                                  const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount: _logs.length + 1,
                              itemBuilder: (context, i) {
                                if (i == 0) {
                                  return AppPageHeader(
                                    title: widget.activity.title,
                                    subtitle: '${_logs.length} checked in',
                                  );
                                }
                                final log = _logs[i - 1];
                                final name = (log['student_name'] ?? '')
                                    .toString()
                                    .trim();
                                final studentNo =
                                    (log['student_number'] ?? '').toString();
                                final checkedIn = to12HourFromDateTime(
                                    (log['checked_in_at'] ?? '').toString());
                                final checkedOut = to12HourFromDateTime(
                                    (log['checked_out_at'] ?? '').toString());
                                final source = (log['source'] ?? '').toString();

                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: AppCard(
                                    padding: const EdgeInsets.fromLTRB(
                                        14, 12, 14, 12),
                                    child: Row(
                                      children: [
                                        AppAvatar(
                                          name: name.isEmpty ? studentNo : name,
                                          size: 40,
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                name.isEmpty ? studentNo : name,
                                                style: AppType.row,
                                              ),
                                              const SizedBox(height: 2),
                                              Text(
                                                [
                                                  if (name.isNotEmpty) studentNo,
                                                  if (source.isNotEmpty)
                                                    source.toUpperCase(),
                                                ].join(' · '),
                                                style: AppType.rowSub,
                                              ),
                                            ],
                                          ),
                                        ),
                                        Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.end,
                                          children: [
                                            Text('In $checkedIn',
                                                style: AppType.caption.copyWith(
                                                    color: AppInk.body,
                                                    fontWeight:
                                                        FontWeight.w600)),
                                            if (checkedOut.isNotEmpty)
                                              Text('Out $checkedOut',
                                                  style: AppType.caption),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
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
