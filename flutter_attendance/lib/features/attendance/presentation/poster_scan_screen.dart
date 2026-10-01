import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';
import 'scan_viewfinder.dart';
import '../domain/qr_payload_parser.dart';
import '../../../core/theme/app_icons.dart';

/// Student poster QR scanner. The student scans an activity poster QR
/// (which contains a URL like `.../attendance/checkin/{id}`), the activity
/// ID is extracted, and a self check-in/out is POSTed to the backend.
class PosterScanScreen extends StatefulWidget {
  const PosterScanScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<PosterScanScreen> createState() => _PosterScanScreenState();
}

class _PosterScanScreenState extends State<PosterScanScreen> {
  late final AttendanceApi _api;
  late final MobileScannerController _controller;
  bool _processing = false;
  String? _statusMessage;
  Color? _statusColor;
  CheckResult? _lastResult;
  String? _lastPayload;
  DateTime? _lastDetectedAt;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _controller = MobileScannerController(
      detectionSpeed: DetectionSpeed.noDuplicates,
      facing: CameraFacing.back,
      // Higher resolution + QR-only decoding lets the scanner lock on from
      // farther away (a small QR has more pixels to decode at 1080p).
      cameraResolution: const Size(1920, 1080),
      formats: const [BarcodeFormat.qrCode],
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  /// Extract the activity ID from a scanned QR string.
  /// The poster QR contains a URL like:
  ///   http://localhost/fbmso_attendance/attendance/checkin/16
  int? _extractActivityId(String text) {
    return QrPayloadParser.activityId(text);
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;
    final barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;

    final raw = barcodes.first.rawValue;
    if (raw == null || raw.isEmpty) return;

    final now = DateTime.now();
    if (_lastPayload == raw &&
        _lastDetectedAt != null &&
        now.difference(_lastDetectedAt!) < const Duration(seconds: 3)) {
      return;
    }
    _lastPayload = raw;
    _lastDetectedAt = now;

    final activityId = _extractActivityId(raw);
    if (activityId == null) {
      setState(() {
        _statusMessage = 'Not a valid activity poster QR code.';
        _statusColor = AppInk.critical;
      });
      HapticFeedback.heavyImpact();
      return;
    }

    setState(() {
      _processing = true;
      _statusMessage = 'Checking in…';
      _statusColor = AppInk.accent;
    });
    HapticFeedback.mediumImpact();

    CheckResult result;
    try {
      result = await _api.selfCheckin(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: activityId,
        direction: 'auto',
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
    setState(() {
      _processing = false;
      _lastResult = result;
      if (result.ok) {
        _statusMessage = result.message ?? 'Success';
        _statusColor = AppInk.positive;
      } else {
        _statusMessage = result.message ?? 'Check-in failed';
        _statusColor = AppInk.critical;
      }
    });
    HapticFeedback.lightImpact();
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    final scanSize = (size.shortestSide * 0.66).clamp(210.0, 300.0);

    final (Color tone, IconData icon) = _statusColor == AppInk.positive
        ? (AppInk.positive, AppIcons.check_circle_rounded)
        : _statusColor == AppInk.critical
            ? (AppInk.critical, AppIcons.error_rounded)
            : (AppInk.info, AppIcons.info_rounded);

    return AppScaffold(
      title: 'Scan poster QR',
      backgroundColor: Colors.white,
      showBackButton: true,
      actions: [
        AppCircleButton(
          onTap: _controller.toggleTorch,
          tooltip: 'Flashlight',
          icon: AppIcons.flashlight_on_rounded,
        ),
        const SizedBox(width: 8),
        AppCircleButton(
          onTap: _controller.switchCamera,
          tooltip: 'Switch camera',
          icon: AppIcons.cameraswitch_rounded,
        ),
      ],
      body: Stack(
        children: [
          const Positioned.fill(child: ColoredBox(color: Colors.black)),
          // ── Camera scanner ──────────────────────────────────────────
          MobileScanner(
            controller: _controller,
            onDetect: _onDetect,
          ),

          // ── Viewfinder ──────────────────────────────────────────────
          Align(
            alignment: const Alignment(0, -0.35),
            child: SizedBox(
              width: scanSize,
              height: scanSize,
              child: ScanViewfinder(scanning: !_processing),
            ),
          ),

          // ── Bottom status sheet ─────────────────────────────────────
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Container(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius:
                    BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
              ),
              child: SafeArea(
                top: false,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (_processing)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 10),
                        child: SizedBox(
                          width: 26,
                          height: 26,
                          child: CircularProgressIndicator(strokeWidth: 2.5),
                        ),
                      )
                    else if (_statusMessage != null)
                      Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: tone.withValues(alpha: 0.12),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(icon, color: tone, size: 24),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              _statusMessage!,
                              style: AppType.row.copyWith(
                                fontSize: 15,
                                color: AppInk.onTint(tone),
                              ),
                            ),
                          ),
                        ],
                      )
                    else
                      Column(
                        children: [
                          Text('Scan the activity poster',
                              style: AppType.headline),
                          const SizedBox(height: 4),
                          Text(
                            'Point your camera at the QR code on the poster to '
                            'check in or out.',
                            style: AppType.body
                                .copyWith(fontSize: 14, color: AppInk.muted),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    if (_lastResult != null && _lastResult!.ok) ...[
                      const SizedBox(height: 16),
                      AppButton(
                        label: 'Done',
                        icon: AppIcons.check_rounded,
                        fullWidth: true,
                        size: AppButtonSize.lg,
                        onTap: () => Navigator.of(context).pop(),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
