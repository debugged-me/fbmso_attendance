import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
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
    return AppScaffold(
      title: 'Scan — ${widget.activityTitle}',
      showBackButton: true,
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
                      borderRadius: BorderRadius.circular(20),
                    ),
                  ),
                ),
                // Hint label
                Positioned(
                  bottom: 24,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.5),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Text(
                        'Point at a student QR code',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            flex: 2,
            child: _recent.isEmpty
                ? AppEmptyState(
                    icon: Icons.qr_code_scanner_rounded,
                    title: 'No scans yet',
                    subtitle:
                        'Recent check-ins will appear here as you scan.',
                    tone: AppInk.muted,
                  )
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                    itemCount: _recent.length,
                    itemBuilder: (context, i) => Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _ScanRecordTile(
                          record: _recent[i], isFirst: i == 0),
                    ),
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
    return AppCard(
      radius: 16,
      background: isFirst ? color.withValues(alpha: 0.06) : Colors.white,
      borderColor: isFirst ? color.withValues(alpha: 0.25) : AppInk.rule,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppInk.heading,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  [
                    if (r.studentNumber != null) r.studentNumber!,
                    if (r.student?['name'] != null)
                      r.student!['name'].toString(),
                    _timeLabel(record.at),
                  ].join(' • '),
                  style: const TextStyle(
                    fontSize: 12.5,
                    color: AppInk.muted,
                  ),
                ),
              ],
            ),
          ),
          if (r.mode == 'checked_in')
            const Icon(Icons.login_rounded, color: AppInk.positive, size: 20)
          else if (r.mode == 'checked_out')
            const Icon(Icons.logout_rounded, color: AppInk.accent, size: 20)
          else if (r.mode == 'queued')
            const Icon(Icons.cloud_upload_rounded,
                color: AppInk.caution, size: 20),
        ],
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
        return ('Checked in', AppInk.positive, Icons.check_circle_rounded);
      case 'checked_out':
        return ('Checked out', AppInk.accent, Icons.check_circle_rounded);
      case 'already_in':
        return ('Already in', AppInk.caution, Icons.info_outline_rounded);
      case 'duplicate':
        return ('Duplicate', AppInk.caution, Icons.block_rounded);
      case 'queued':
        return ('Saved offline', AppInk.caution, Icons.cloud_upload_rounded);
      default:
        return (r.message ?? 'Error', AppInk.critical, Icons.error_outline_rounded);
    }
  }
}
