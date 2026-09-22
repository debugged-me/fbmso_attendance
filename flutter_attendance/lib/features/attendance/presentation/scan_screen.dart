import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';
import '../domain/qr_payload_parser.dart';

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
  String? _lastPayload;
  DateTime? _lastDetectedAt;

  // Result flash overlay — brief green/red tint over the camera so the
  // operator gets confirmation without looking at the history list.
  Color _flashColor = Colors.transparent;
  double _flashOpacity = 0;

  void _flash(bool ok) {
    if (ok) {
      HapticFeedback.mediumImpact();
    } else {
      HapticFeedback.heavyImpact();
    }
    setState(() {
      _flashColor = ok ? AppInk.positive : AppInk.critical;
      _flashOpacity = 0.28;
    });
    Future.delayed(const Duration(milliseconds: 350), () {
      if (mounted) setState(() => _flashOpacity = 0);
    });
  }

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _controller = MobileScannerController(
      detectionSpeed: DetectionSpeed.noDuplicates,
      facing: CameraFacing.back,
    );
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

    final now = DateTime.now();
    if (_lastPayload == raw &&
        _lastDetectedAt != null &&
        now.difference(_lastDetectedAt!) < const Duration(seconds: 3)) {
      return;
    }
    _lastPayload = raw;
    _lastDetectedAt = now;

    final qrToken = QrPayloadParser.studentToken(raw);
    if (qrToken.isEmpty) {
      _flash(false);
      setState(() {
        _recent.insert(
          0,
          _ScanRecord(
            raw: raw,
            result: const CheckResult(
              ok: false,
              mode: 'invalid_qr',
              message: 'Not a student attendance QR. Open My QR and try again.',
            ),
            at: now,
          ),
        );
      });
      return;
    }

    setState(() => _processing = true);

    CheckResult result;
    try {
      result = await _api.consume(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: widget.activityId,
        qrToken: qrToken,
      );
    } catch (_) {
      result = const CheckResult(
        ok: false,
        mode: 'err',
        message:
            'Could not submit this scan. Check your connection and try again.',
      );
    }

    if (!mounted) return;
    _flash(result.ok || result.mode == 'queued' || result.mode == 'already_in');
    setState(() {
      _recent.insert(
          0, _ScanRecord(raw: raw, result: result, at: DateTime.now()));
      _processing = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Scan — ${widget.activityTitle}',
      showBackButton: true,
      actions: [
        IconButton(
          onPressed: _controller.toggleTorch,
          tooltip: 'Flashlight',
          icon: const Icon(Icons.flashlight_on_rounded),
        ),
        IconButton(
          onPressed: _controller.switchCamera,
          tooltip: 'Switch camera',
          icon: const Icon(Icons.cameraswitch_rounded),
        ),
      ],
      body: LayoutBuilder(
        builder: (context, constraints) {
          final wide = constraints.maxWidth >= 720;
          return Column(
            children: [
              const SyncStatusBanner(),
              Expanded(
                child: wide
                    ? Row(
                        children: [
                          Expanded(flex: 3, child: _cameraPanel()),
                          Expanded(flex: 2, child: _historyPanel()),
                        ],
                      )
                    : Column(
                        children: [
                          Expanded(flex: 6, child: _cameraPanel()),
                          Expanded(flex: 4, child: _historyPanel()),
                        ],
                      ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _cameraPanel() {
    return LayoutBuilder(
      builder: (context, constraints) {
        final frameSize =
            (constraints.biggest.shortestSide * 0.62).clamp(180.0, 280.0);
        return ClipRect(
          child: Stack(
            fit: StackFit.expand,
            children: [
              MobileScanner(controller: _controller, onDetect: _onDetect),
              Center(
                child: Container(
                  width: frameSize,
                  height: frameSize,
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.white, width: 3),
                    borderRadius: BorderRadius.circular(22),
                  ),
                ),
              ),
              // Result flash — covers the camera briefly on each scan.
              IgnorePointer(
                child: AnimatedOpacity(
                  opacity: _flashOpacity,
                  duration: const Duration(milliseconds: 220),
                  child: ColoredBox(color: _flashColor),
                ),
              ),
              if (_processing)
                ColoredBox(
                  color: Colors.black.withValues(alpha: 0.22),
                  child: const Center(
                    child: CircularProgressIndicator(color: Colors.white),
                  ),
                ),
              Positioned(
                left: 12,
                right: 12,
                bottom: 14,
                child: Center(
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.64),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      _processing
                          ? 'Recording attendance…'
                          : 'Point at a student QR code',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _historyPanel() {
    if (_recent.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(20),
          child: Text(
            'Recent check-ins will appear here.',
            textAlign: TextAlign.center,
            style: TextStyle(color: AppInk.muted, fontWeight: FontWeight.w600),
          ),
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
      itemCount: _recent.length,
      itemBuilder: (context, i) => Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: _ScanRecordTile(record: _recent[i], isFirst: i == 0),
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
                if (r.message != null && r.message!.trim().isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(
                    r.message!,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontSize: 12, color: color),
                  ),
                ],
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
      case 'invalid_qr':
        return ('Wrong QR code', AppInk.critical, Icons.qr_code_2_rounded);
      case 'expired_qr':
        return ('Expired QR', AppInk.critical, Icons.timer_off_rounded);
      case 'activity_ended':
      case 'activity_scheduled':
      case 'activity_closed':
        return (
          'Check-in unavailable',
          AppInk.caution,
          Icons.lock_clock_rounded
        );
      default:
        return (
          r.message ?? 'Error',
          AppInk.critical,
          Icons.error_outline_rounded
        );
    }
  }
}
