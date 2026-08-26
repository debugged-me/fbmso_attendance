import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/utils/time_format.dart';
import '../../auth/domain/app_session.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/activity_state_style.dart';
import '../../attendance/presentation/poster_scan_screen.dart';
import '../../attendance/presentation/scan_screen.dart';

/// Shows the activity detail bottom sheet with role-based actions.
/// - Students: "Scan Poster QR" + "Show My QR"
/// - Admins: "Scan Students" + "View Attendance Logs"
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
      session: session,
    ),
  );
}

class ActivityDetailSheet extends StatelessWidget {
  const ActivityDetailSheet({
    super.key,
    required this.activity,
    required this.isStudent,
    required this.session,
  });

  final Activity activity;
  final bool isStudent;
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
                    color: AppInk.rule,
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
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                        color: AppInk.heading,
                        height: 1.25,
                      ),
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
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppInk.muted,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 16),
              ],

              // ── Detail chips ─────────────────────────────────────
              Wrap(
                spacing: 12,
                runSpacing: 10,
                children: [
                  if (activity.activityDate.isNotEmpty)
                    _DetailChip(
                      icon: Icons.event_rounded,
                      label: 'Date',
                      value: activity.activityDate,
                    ),
                  if (activity.startTime.isNotEmpty)
                    _DetailChip(
                      icon: Icons.schedule_rounded,
                      label: 'Time',
                      value: _timeRange(activity),
                    ),
                  if (activity.location.isNotEmpty)
                    _DetailChip(
                      icon: Icons.place_rounded,
                      label: 'Location',
                      value: activity.location,
                    ),
                  if (activity.code.isNotEmpty)
                    _DetailChip(
                      icon: Icons.tag_rounded,
                      label: 'Code',
                      value: activity.code,
                    ),
                ],
              ),
              const SizedBox(height: 28),

              // ── Actions ──────────────────────────────────────────
              if (isStudent) ...[
                AppButton(
                  label: isOpen ? 'Scan Poster QR' : 'Check-in Closed',
                  icon: isOpen
                      ? Icons.qr_code_scanner_rounded
                      : Icons.lock_outline_rounded,
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
                  label: 'Show My QR',
                  icon: Icons.qr_code_2_rounded,
                  fullWidth: true,
                  size: AppButtonSize.lg,
                  style: AppButtonStyle.outline,
                  onTap: () {
                    Navigator.of(context).pop();
                    Navigator.of(context).popUntil((route) => route.isFirst);
                  },
                ),
              ] else ...[
                AppButton(
                  label: isOpen ? 'Scan Students' : 'Check-in Closed',
                  icon: isOpen
                      ? Icons.qr_code_scanner_rounded
                      : Icons.lock_outline_rounded,
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
                const SizedBox(height: 10),
                AppButton(
                  label: 'View Attendance Logs',
                  icon: Icons.history_rounded,
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
              ],
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
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: AppInk.page,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppInk.rule),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: AppInk.muted),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  color: AppInk.muted,
                  letterSpacing: 0.5,
                ),
              ),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppInk.heading,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Standalone activity log view (used from the activity detail sheet).
class ActivityLogView extends StatefulWidget {
  const ActivityLogView({super.key, required this.session, required this.activity});
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
      title: widget.activity.title,
      showBackButton: true,
      body: Column(
        children: [
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
                      : _logs.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.history_rounded,
                                title: 'No attendance records',
                                subtitle: 'No one has checked in yet.',
                              ),
                            ])
                          : ListView.builder(
                              padding:
                                  const EdgeInsets.fromLTRB(16, 12, 16, 24),
                              itemCount: _logs.length,
                              itemBuilder: (context, i) {
                                final log = _logs[i];
                                final name = (log['student_name'] ?? '')
                                    .toString()
                                    .trim();
                                final studentNo =
                                    (log['student_number'] ?? '').toString();
                                final checkedIn =
                                    to12HourFromDateTime((log['checked_in_at'] ?? '').toString());
                                final checkedOut =
                                    to12HourFromDateTime((log['checked_out_at'] ?? '').toString());
                                final source =
                                    (log['source'] ?? '').toString();

                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: AppCard(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 14, vertical: 12),
                                    child: Row(
                                      children: [
                                        Container(
                                          width: 40,
                                          height: 40,
                                          decoration: BoxDecoration(
                                            color: AppInk.accent
                                                .withValues(alpha: 0.12),
                                            borderRadius:
                                                BorderRadius.circular(12),
                                          ),
                                          child: const Icon(
                                              Icons.person_rounded,
                                              color: AppInk.accent,
                                              size: 20),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                name.isEmpty
                                                    ? studentNo
                                                    : name,
                                                style: const TextStyle(
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.w700,
                                                  color: AppInk.heading,
                                                ),
                                                maxLines: 1,
                                                overflow:
                                                    TextOverflow.ellipsis,
                                              ),
                                              const SizedBox(height: 4),
                                              Wrap(
                                                spacing: 12,
                                                runSpacing: 4,
                                                children: [
                                                  Row(
                                                    mainAxisSize:
                                                        MainAxisSize.min,
                                                    children: [
                                                      const Icon(
                                                          Icons.login_rounded,
                                                          size: 14,
                                                          color: AppInk
                                                              .positive),
                                                      const SizedBox(width: 4),
                                                      Text(checkedIn,
                                                          style: const TextStyle(
                                                              fontSize: 12,
                                                              color: AppInk
                                                                  .muted)),
                                                    ],
                                                  ),
                                                  if (checkedOut.isNotEmpty)
                                                    Row(
                                                      mainAxisSize:
                                                          MainAxisSize.min,
                                                      children: [
                                                        const Icon(
                                                            Icons
                                                                .logout_rounded,
                                                            size: 14,
                                                            color: AppInk
                                                                .critical),
                                                        const SizedBox(
                                                            width: 4),
                                                        Text(checkedOut,
                                                            style: const TextStyle(
                                                                fontSize: 12,
                                                                color: AppInk
                                                                    .muted)),
                                                      ],
                                                    ),
                                                  if (source.isNotEmpty)
                                                    Container(
                                                      padding:
                                                          const EdgeInsets
                                                              .symmetric(
                                                          horizontal: 6,
                                                          vertical: 2),
                                                      decoration:
                                                          BoxDecoration(
                                                        color: AppInk.accent
                                                            .withValues(
                                                                alpha: 0.08),
                                                        borderRadius:
                                                            BorderRadius
                                                                .circular(6),
                                                      ),
                                                      child: Text(
                                                        source.toUpperCase(),
                                                        style: const TextStyle(
                                                          fontSize: 10,
                                                          fontWeight:
                                                              FontWeight.w700,
                                                          color:
                                                              AppInk.accent,
                                                        ),
                                                      ),
                                                    ),
                                                ],
                                              ),
                                            ],
                                          ),
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
