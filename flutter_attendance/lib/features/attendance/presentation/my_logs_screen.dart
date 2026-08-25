import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/utils/time_format.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';

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
      title: 'My Attendance',
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _logs.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 80),
                            AppEmptyState(
                              icon: Icons.history_rounded,
                              title: 'No attendance records yet',
                              subtitle:
                                  'Your check-ins will appear here once you attend an activity.',
                              tone: AppInk.muted,
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                          itemCount: _logs.length,
                          itemBuilder: (context, i) =>
                              _LogTile(log: _logs[i]),
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
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        radius: 20,
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        log.title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: AppInk.heading,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        log.activityDate,
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppInk.muted,
                        ),
                      ),
                    ],
                  ),
                ),
                if (log.sessionLabel.isNotEmpty)
                  AppChip(label: log.sessionLabel, tone: AppInk.accent),
              ],
            ),
            const SizedBox(height: 14),
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
              const SizedBox(height: 12),
              const Divider(height: 1, color: AppInk.rule),
              const SizedBox(height: 10),
              Text(
                log.remarks,
                style: const TextStyle(
                  fontSize: 13,
                  color: AppInk.body,
                  height: 1.4,
                ),
              ),
            ],
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
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '$label ',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}
