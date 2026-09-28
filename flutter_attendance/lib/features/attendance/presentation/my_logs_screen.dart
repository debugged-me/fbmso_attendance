import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/utils/time_format.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';
import 'activity_state_style.dart';
import '../../../core/theme/app_icons.dart';

/// Student's own attendance history. Cache-first so it renders offline.
class MyLogsScreen extends StatefulWidget {
  const MyLogsScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<MyLogsScreen> createState() => _MyLogsScreenState();
}

class _MyLogsScreenState extends State<MyLogsScreen> {
  late final AttendanceApi _api;
  List<AttendanceLog> _logs = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final logs = await _api.myLogs(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
    );
    if (!mounted) return;
    setState(() {
      _logs = logs;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const ListSkeleton(itemCount: 5)
                  : _logs.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 80),
                            AppEmptyState(
                              icon: AppIcons.history_rounded,
                              title: 'No attendance records yet',
                              subtitle:
                                  'Your check-ins will appear here once you attend an activity.',
                              tone: AppInk.muted,
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                          itemCount: _logs.length + 1,
                          itemBuilder: (context, i) {
                            if (i == 0) {
                              return AppPageHeader(
                                title: 'My attendance',
                                subtitle:
                                    '${_logs.length} record${_logs.length == 1 ? '' : 's'}',
                              );
                            }
                            return _LogTile(log: _logs[i - 1]);
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LogTile extends StatelessWidget {
  const _LogTile({required this.log});
  final AttendanceLog log;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ActivityDateBadge(date: log.activityDate),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(child: Text(log.title, style: AppType.row)),
                      if (log.sessionLabel.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        AppChip(label: log.sessionLabel, tone: AppInk.accent),
                      ],
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      _TimeChip(
                        label: 'In',
                        value: _shortTime(log.checkedInAt),
                        color: AppInk.positive,
                      ),
                      const SizedBox(width: 8),
                      _TimeChip(
                        label: 'Out',
                        value: log.isCheckedOut
                            ? _shortTime(log.checkedOutAt)
                            : '—',
                        color: log.isCheckedOut ? AppInk.accent : AppInk.muted,
                      ),
                    ],
                  ),
                  if (log.remarks.isNotEmpty && log.remarks != '—') ...[
                    const SizedBox(height: 10),
                    Text(log.remarks, style: AppType.rowSub),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _shortTime(String dt) {
    if (dt.isEmpty) return '—';
    return to12HourFromDateTime(dt);
  }
}

class _TimeChip extends StatelessWidget {
  const _TimeChip({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppRadius.pill),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '$label ',
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w600,
              color: AppInk.onTint(color).withValues(alpha: 0.8),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: AppInk.onTint(color),
            ),
          ),
        ],
      ),
    );
  }
}
