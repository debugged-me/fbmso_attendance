import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';

/// Instructor/personnel scanner. Camera scans a student's QR, the token is
/// POSTed to `/attendance/consume`. When offline the scan queues to the
/// outbox and the UI confirms "Saved offline".
class ScanScreen extends StatefulWidget {
  const ScanScreen({
    super.key,
    required this.session,
    required this.activityId,
    required this.activityTitle,
  });

  final AppSession session;
  final int activityId;
  final String activityTitle;

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  late final AttendanceApi _api;
  late final MobileScannerController _controller;
  bool _processing = false;
  final List<_ScanRecord> _recent = [];

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _controller = MobileScannerController();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;
    final barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;
    final raw = barcodes.first.rawValue ?? '';
    if (raw.isEmpty) return;

    setState(() => _processing = true);

    final result = await _api.consume(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      activityId: widget.activityId,
      qrToken: raw,
    );

    if (!mounted) return;
    setState(() {
      _recent.insert(0, _ScanRecord(raw: raw, result: result, at: DateTime.now()));
      _processing = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Scan — ${widget.activityTitle}')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            flex: 3,
            child: Stack(
              children: [
                MobileScanner(
                  controller: _controller,
                  onDetect: _onDetect,
                ),
                if (_processing)
                  const Center(child: CircularProgressIndicator()),
                // Scan overlay frame
                Center(
                  child: Container(
                    width: 250,
                    height: 250,
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.white70, width: 2),
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            flex: 2,
            child: _recent.isEmpty
                ? const Center(
                    child: Text('Point the camera at a student QR code.',
                        style: TextStyle(color: AppTheme.textMuted)),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.all(8),
                    itemCount: _recent.length,
                    itemBuilder: (context, i) => _ScanRecordTile(
                        record: _recent[i], isFirst: i == 0),
                  ),
          ),
        ],
      ),
    );
  }
}

class _ScanRecord {
  _ScanRecord({required this.raw, required this.result, required this.at});
  final String raw;
  final CheckResult result;
  final DateTime at;
}

class _ScanRecordTile extends StatelessWidget {
  const _ScanRecordTile({required this.record, required this.isFirst});
  final _ScanRecord record;
  final bool isFirst;

  @override
  Widget build(BuildContext context) {
    final r = record.result;
    final (label, color, icon) = _style(r);
    return Card(
      color: isFirst ? color.withValues(alpha: 0.08) : null,
      child: ListTile(
        leading: Icon(icon, color: color),
        title: Text(label, style: TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Text([
          if (r.studentNumber != null) r.studentNumber!,
          if (r.student?['name'] != null) r.student!['name'].toString(),
          _timeLabel(record.at),
        ].join(' • ')),
        trailing: r.mode == 'checked_in'
            ? const Icon(Icons.login, color: AppTheme.success)
            : r.mode == 'checked_out'
                ? const Icon(Icons.logout, color: AppTheme.info)
                : r.mode == 'queued'
                    ? const Icon(Icons.cloud_upload, color: AppTheme.warning)
                    : null,
      ),
    );
  }

  String _timeLabel(DateTime t) {
    final h = t.hour.toString().padLeft(2, '0');
    final m = t.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }

  (String, Color, IconData) _style(CheckResult r) {
    switch (r.mode) {
      case 'checked_in':
        return ('Checked in', AppTheme.success, Icons.check_circle);
      case 'checked_out':
        return ('Checked out', AppTheme.info, Icons.check_circle);
      case 'already_in':
        return ('Already in', AppTheme.warning, Icons.info_outline);
      case 'duplicate':
        return ('Duplicate', AppTheme.warning, Icons.block);
      case 'queued':
        return ('Saved offline', AppTheme.warning, Icons.cloud_upload);
      default:
        return (r.message ?? 'Error', AppTheme.error, Icons.error_outline);
    }
  }
}
