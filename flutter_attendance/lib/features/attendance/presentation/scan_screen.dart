import 'package:audioplayers/audioplayers.dart';
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
  final AudioPlayer _player = AudioPlayer();
  bool _processing = false;
  final List<_ScanRecord> _recent = [];
  String? _lastPayload;
  DateTime? _lastDetectedAt;

  // Result flash overlay — brief green/red tint over the camera so the
  // operator gets confirmation without looking at the history list.
  Color _flashColor = Colors.transparent;
  double _flashOpacity = 0;

  // Identity verification popup — photo + name of whoever just scanned, so a
  // borrowed/shared QR shows the real owner's face. Auto-dismisses.
  CheckResult? _verifyResult;
  int _verifyToken = 0; // bumps per scan so stale timers can't hide a new card

  static const _verifyModes = {
    'checked_in',
    'checked_out',
    'already_in',
    'duplicate',
    'queued',
    'inactive_student',
  };

  void _showVerify(CheckResult r) {
    final s = r.student;
    final hasIdentity = s != null &&
        ((s['name'] ?? '').toString().isNotEmpty ||
            (s['photo_url'] ?? '').toString().isNotEmpty);
    if (!_verifyModes.contains(r.mode) || !hasIdentity) return;
    final token = ++_verifyToken;
    setState(() => _verifyResult = r);
    Future.delayed(const Duration(milliseconds: 2600), () {
      if (mounted && _verifyToken == token) {
        setState(() => _verifyResult = null);
      }
    });
  }

  /// Audible result — distinct tones so the operator doesn't need to look at
  /// the screen: bright chime = recorded, double beep = already counted /
  /// saved offline, low buzz = rejected or failed.
  void _playResult(CheckResult r) {
    final src = switch (r.mode) {
      'checked_in' || 'checked_out' => 'sounds/scan_success.wav',
      'already_in' || 'duplicate' || 'queued' => 'sounds/scan_duplicate.wav',
      _ => 'sounds/scan_error.wav',
    };
    _player.stop();
    _player.play(AssetSource(src));
  }

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
      // QR only — skipping other symbologies reduces false work per frame.
      formats: const [BarcodeFormat.qrCode],
      // Higher capture resolution keeps small/distant codes decodable —
      // default resolution requires holding the QR close to the lens.
      cameraResolution: const Size(1920, 1080),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    _player.dispose();
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
      const badQr = CheckResult(
        ok: false,
        mode: 'invalid_qr',
        message: 'Not a student attendance QR. Open My QR and try again.',
      );
      _flash(false);
      _playResult(badQr);
      setState(() {
        _recent.insert(
          0,
          _ScanRecord(
            raw: raw,
            result: badQr,
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
    _playResult(result);
    _showVerify(result);
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
              // Identity card — photo + name of the student who scanned, for
              // visual verification against QR sharing. Tap to dismiss early.
              if (_verifyResult != null)
                Positioned(
                  left: 14,
                  right: 14,
                  top: 14,
                  child: _VerifyCard(
                    result: _verifyResult!,
                    onDismiss: () => setState(() => _verifyResult = null),
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

/// Identity verification card that pops over the camera after each scan.
/// Shows the QR owner's photo, name, number and course/section so the
/// operator can match the face to the person in front of them — a shared
/// QR still resolves to its real owner, which is the whole point.
class _VerifyCard extends StatelessWidget {
  const _VerifyCard({required this.result, required this.onDismiss});
  final CheckResult result;
  final VoidCallback onDismiss;

  (String, Color, IconData) _status() {
    switch (result.mode) {
      case 'checked_in':
        return ('Checked in', AppInk.positive, Icons.login_rounded);
      case 'checked_out':
        return ('Checked out', AppInk.accent, Icons.logout_rounded);
      case 'already_in':
        return ('Already in', AppInk.caution, Icons.info_outline_rounded);
      case 'duplicate':
        return ('Duplicate scan', AppInk.caution, Icons.block_rounded);
      case 'queued':
        return ('Saved offline', AppInk.caution, Icons.cloud_upload_rounded);
      case 'inactive_student':
        return ('Inactive account', AppInk.critical, Icons.person_off_rounded);
      default:
        return ('Scanned', AppInk.accent, Icons.qr_code_rounded);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = result.student ?? const {};
    final name = (s['name'] ?? '').toString();
    final number = (s['number'] ?? result.studentNumber ?? '').toString();
    final photo = (s['photo_url'] ?? '').toString();
    final course = (s['course'] ?? '').toString();
    final section = (s['section'] ?? '').toString();
    final (label, color, icon) = _status();

    final initials = name
        .split(RegExp(r'[,\s]+'))
        .where((p) => p.isNotEmpty)
        .take(2)
        .map((p) => p[0].toUpperCase())
        .join();

    return GestureDetector(
      onTap: onDismiss,
      child: Material(
        elevation: 12,
        borderRadius: BorderRadius.circular(18),
        color: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              // Photo — falls back to initials when the account has no avatar.
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: SizedBox(
                  width: 72,
                  height: 72,
                  child: photo.isNotEmpty
                      ? Image.network(
                          photo,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) =>
                              _initialAvatar(initials, color),
                        )
                      : _initialAvatar(initials, color),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      name.isEmpty ? 'Unknown student' : name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: AppInk.heading,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      [
                        if (number.isNotEmpty) number,
                        if (course.isNotEmpty) course,
                        if (section.isNotEmpty) section,
                      ].join(' · '),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: AppInk.muted,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.10),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(icon, size: 14, color: color),
                          const SizedBox(width: 5),
                          Text(
                            label,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: color,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _initialAvatar(String initials, Color color) {
    return Container(
      color: color.withValues(alpha: 0.10),
      alignment: Alignment.center,
      child: Text(
        initials.isEmpty ? '?' : initials,
        style: TextStyle(
          fontSize: 26,
          fontWeight: FontWeight.w800,
          color: color,
        ),
      ),
    );
  }
}
