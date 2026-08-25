import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';

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

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _controller = MobileScannerController(
      detectionSpeed: DetectionSpeed.noDuplicates,
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
    final match = RegExp(r'attendance/checkin/(\d+)', caseSensitive: false)
        .firstMatch(text);
    if (match != null) {
      return int.tryParse(match.group(1)!);
    }
    // Also accept a bare numeric ID.
    final n = int.tryParse(text.trim());
    if (n != null && n > 0) return n;
    return null;
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;
    final barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;

    final raw = barcodes.first.rawValue;
    if (raw == null || raw.isEmpty) return;

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

    final result = await _api.selfCheckin(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      activityId: activityId,
      direction: 'auto',
    );

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
    return AppScaffold(
      title: 'Scan Poster QR',
      showBackButton: true,
      body: Stack(
        children: [
          // ── Camera scanner ──────────────────────────────────────────
          MobileScanner(
            controller: _controller,
            onDetect: _onDetect,
          ),

          // ── Overlay frame ───────────────────────────────────────────
          Center(
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.7),
                  width: 3,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
            ),
          ),

          // ── Bottom status panel ─────────────────────────────────────
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Container(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.85),
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: SafeArea(
                top: false,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (_processing)
                      const Padding(
                        padding: EdgeInsets.only(bottom: 12),
                        child: SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: Colors.white,
                          ),
                        ),
                      )
                    else if (_statusMessage != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              _statusColor == AppInk.positive
                                  ? Icons.check_circle_rounded
                                  : _statusColor == AppInk.critical
                                      ? Icons.error_rounded
                                      : Icons.info_rounded,
                              color: _statusColor ?? Colors.white,
                              size: 20,
                            ),
                            const SizedBox(width: 8),
                            Flexible(
                              child: Text(
                                _statusMessage!,
                                style: TextStyle(
                                  color: _statusColor ?? Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      const Padding(
                        padding: EdgeInsets.only(bottom: 12),
                        child: Text(
                          'Point your camera at the activity poster QR code.',
                          style: TextStyle(
                            color: Colors.white70,
                            fontSize: 13,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    if (_lastResult != null && _lastResult!.ok) ...[
                      const SizedBox(height: 4),
                      AppButton(
                        label: 'Done',
                        icon: Icons.check_rounded,
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
