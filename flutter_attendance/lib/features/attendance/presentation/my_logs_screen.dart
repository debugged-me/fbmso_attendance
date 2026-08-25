import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
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
    return Scaffold(
      appBar: AppBar(title: const Text('My Attendance')),
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
                          children: const [
                            SizedBox(height: 120),
                            Center(
                              child: Text('No attendance records yet.',
                                  style:
                                      TextStyle(color: AppTheme.textMuted)),
                            ),
                          ],
                        )
                      : ListView.builder(
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
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(log.title, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 4),
            Text(log.activityDate,
                style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 12),
            Row(
              children: [
                _TimeChip(
                  label: 'In',
                  value: _shortTime(log.checkedInAt),
                  color: AppTheme.success,
                ),
                const SizedBox(width: 8),
                _TimeChip(
                  label: 'Out',
                  value: log.isCheckedOut ? _shortTime(log.checkedOutAt) : '—',
                  color: log.isCheckedOut ? AppTheme.info : AppTheme.textMuted,
                ),
                const SizedBox(width: 8),
                if (log.sessionLabel.isNotEmpty)
                  Chip(
                    label: Text(log.sessionLabel,
                        style: const TextStyle(fontSize: 11)),
                    padding: EdgeInsets.zero,
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
              ],
            ),
            if (log.remarks.isNotEmpty && log.remarks != '—') ...[
              const SizedBox(height: 8),
              Text(log.remarks,
                  style: Theme.of(context).textTheme.bodySmall),
            ],
          ],
        ),
      ),
    );
  }

  String _shortTime(String dt) {
    if (dt.isEmpty) return '—';
    // "2026-08-25 18:24:10" → "18:24"
    final parts = dt.split(' ');
    if (parts.length >= 2) return parts[1].substring(0, 5);
    return dt;
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
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text('$label ',
              style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: color)),
          Text(value,
              style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: color)),
        ],
      ),
    );
  }
}
