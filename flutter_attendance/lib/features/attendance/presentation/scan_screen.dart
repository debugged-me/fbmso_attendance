import 'dart:async';

import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/roster_service.dart';
import '../../../core/services/scan_ledger_service.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';
import '../domain/qr_payload_parser.dart';
import 'scan_viewfinder.dart';
import '../../../core/theme/app_icons.dart';

/// Instructor/personnel scanner. Camera scans a student's QR; the scan is
/// decided and saved on the phone at once and uploaded to
/// `/attendance/consume` in the background, so the line never waits on the
/// network. Each tile updates when the server's verdict for it arrives.
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
  StreamSubscription<ScanVerdict>? _verdictSub;

  /// The code last acted on and when the camera last saw it. It is ignored
  /// while it stays in view (a student holding their phone up must not check
  /// out ten seconds later) and counts again once it has been out of view
  /// for [_sameCodeGap] — e.g. the same student coming back to check out.
  String? _lastPayload;
  DateTime? _lastSeenAt;
  static const _sameCodeGap = Duration(seconds: 3);

  // Result flash overlay — brief green/red tint over the camera so the
  // operator gets confirmation without looking at the history list.
  Color _flashColor = Colors.transparent;
  double _flashOpacity = 0;

  // Identity verification popup — photo + name of whoever just scanned, so a
  // borrowed/shared QR shows the real owner's face. Auto-dismisses.
  CheckResult? _verifyResult;
  int _verifyToken = 0; // bumps per scan so stale timers can't hide a new card

  // Offline roster state, surfaced so the operator knows whether the scanner
  // can name students — shown from what is on the phone, signal or not.
  bool _rosterLoading = false;
  bool _rosterKnown = false;
  bool _rosterComplete = false;
  int _rosterCount = 0;
  (int, int)? _rosterProgress;

  static const _verifyModes = {
    'checked_in',
    'checked_out',
    'already_in',
    'duplicate',
    'queued',
    'unverified',
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
      'already_in' ||
      'duplicate' ||
      'queued' ||
      'unverified' =>
        'sounds/scan_duplicate.wav',
      _ => 'sounds/scan_error.wav',
    };
    _player.stop();
    _player.play(AssetSource(src));
  }

  void _flash(Color color) {
    if (color == AppInk.critical) {
      HapticFeedback.heavyImpact();
    } else {
      HapticFeedback.mediumImpact();
    }
    setState(() {
      _flashColor = color;
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
      // Every sighting is reported; _onDetect decides what counts. With
      // noDuplicates a code could not be read again until a different one
      // was — a student checking out on the phone that checked them in, with
      // nobody scanned in between, was silently ignored.
      detectionSpeed: DetectionSpeed.normal,
      facing: CameraFacing.back,
      // QR only — skipping other symbologies reduces false work per frame.
      formats: const [BarcodeFormat.qrCode],
      // Higher capture resolution keeps small/distant codes decodable —
      // default resolution requires holding the QR close to the lens.
      cameraResolution: const Size(1920, 1080),
    );
    _verdictSub = ScanLedgerService.verdicts.listen(_onVerdict);
    _prepareOfflineRoster();
  }

  /// Show what is on the phone, then refresh it while there is signal, so the
  /// scanner can name students and catch repeats once the connection drops.
  /// Best effort — scanning still works without it, just without local
  /// identity. A download that fails part-way leaves the saved roster as it
  /// was, and an unchanged roster is not downloaded again.
  Future<void> _prepareOfflineRoster() async {
    await _loadRosterState();
    if (!await ConnectivityService.isReachable()) return;
    if (!mounted) return;

    setState(() => _rosterLoading = true);
    try {
      await RosterService.download(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: widget.activityId,
        onProgress: (done, total) {
          if (mounted) setState(() => _rosterProgress = (done, total));
        },
      );
    } catch (_) {
      // Leave whatever snapshot is already on the device in place.
    } finally {
      if (mounted) setState(() => _rosterLoading = false);
      await _loadRosterState();
    }
  }

  Future<void> _loadRosterState() async {
    final meta = await RosterService.metaFor(widget.activityId);
    if (!mounted) return;
    setState(() {
      _rosterKnown = true;
      _rosterCount = meta?.total ?? 0;
      _rosterComplete = meta?.complete ?? false;
    });
  }

  /// Tells the operator whether this phone can still name students once the
  /// signal goes — the difference between a useful offline scan and a blind one.
  Widget _rosterBanner() {
    if (!_rosterLoading && !_rosterKnown) return const SizedBox.shrink();

    final (done, total) = _rosterProgress ?? (0, 0);
    final String label;
    final Color tint;
    if (_rosterLoading) {
      label = total > 0
          ? 'Preparing offline roster… $done of $total'
          : 'Preparing offline roster…';
      tint = AppInk.accent;
    } else if (_rosterCount > 0 && _rosterComplete) {
      label = 'Offline roster ready — $_rosterCount student(s)';
      tint = AppInk.positive;
    } else if (_rosterCount > 0) {
      label = 'Offline roster incomplete — connect once to finish it';
      tint = AppInk.caution;
    } else {
      label = 'No offline roster on this phone — names will not show until '
          'it downloads (scans are still saved)';
      tint = AppInk.caution;
    }

    return Container(
      width: double.infinity,
      color: tint.withValues(alpha: 0.08),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          if (_rosterLoading)
            SizedBox(
              width: 14,
              height: 14,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                value: total > 0 ? done / total : null,
                color: tint,
              ),
            )
          else
            Icon(
              tint == AppInk.positive
                  ? AppIcons.offline_pin_rounded
                  : AppIcons.warning_amber_rounded,
              size: 16,
              color: tint,
            ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                color: AppInk.onTint(tint),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _verdictSub?.cancel();
    _controller.dispose();
    _player.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    final barcodes = capture.barcodes;
    if (barcodes.isEmpty) return;
    final raw = barcodes.first.rawValue ?? '';
    if (raw.isEmpty) return;

    final now = DateTime.now();
    if (raw == _lastPayload &&
        _lastSeenAt != null &&
        now.difference(_lastSeenAt!) < _sameCodeGap) {
      _lastSeenAt = now; // still in view
      return;
    }
    // Busy with the previous code (a few milliseconds of local work): this
    // one is still in front of the camera and is read on the next frame.
    if (_processing) return;
    _lastPayload = raw;
    _lastSeenAt = now;

    final qrToken = QrPayloadParser.studentToken(raw);
    if (qrToken.isEmpty) {
      const badQr = CheckResult(
        ok: false,
        mode: 'invalid_qr',
        message: 'Not a student attendance QR. Open My QR and try again.',
      );
      _flash(AppInk.critical);
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
        message: 'Could not save this scan on the phone. Scan it again.',
      );
    }

    if (!mounted) return;
    _flash(switch (result.mode) {
      'checked_in' || 'checked_out' => AppInk.positive,
      _ when result.ok => AppInk.caution,
      _ => AppInk.critical,
    });
    _playResult(result);
    _showVerify(result);
    setState(() {
      _recent.insert(
          0, _ScanRecord(raw: raw, result: result, at: DateTime.now()));
      _processing = false;
    });
  }

  /// The server answered for a scan made on this screen: replace the phone's
  /// provisional answer on its tile. A refusal that arrives while the student
  /// is likely still at the table also buzzes, so they can be called back.
  void _onVerdict(ScanVerdict v) {
    if (!mounted) return;
    final i = _recent.indexWhere((r) => r.result.clientScanId == v.clientScanId);
    if (i < 0) return;
    final before = _recent[i];
    final after = CheckResult(
      ok: v.ok,
      mode: v.mode,
      clientScanId: v.clientScanId,
      studentNumber: v.studentNumber ?? before.result.studentNumber,
      session: v.session ?? before.result.session,
      student: v.student ?? before.result.student,
      message: v.ok ? null : v.message,
    );
    setState(() {
      _recent[i] = _ScanRecord(raw: before.raw, result: after, at: before.at);
      // The identity card for this scan may still be up: show the verdict.
      if (_verifyResult?.clientScanId == v.clientScanId) _verifyResult = after;
    });
    if (!v.ok && DateTime.now().difference(before.at) < const Duration(seconds: 8)) {
      _flash(AppInk.critical);
      _playResult(after);
    }
  }

  static const _caption = 'Scanner · works offline';

  /// Staff confirm which event they are scanning by its name, so the app
  /// bar grows to fit all of it rather than cutting it off.
  double _toolbarHeight(BuildContext context, TextStyle titleStyle) {
    // Title room: screen minus the 60px back button, the two 40px buttons
    // with their 8px gaps, the app bar's title spacing, and 4px of slack.
    final width = MediaQuery.sizeOf(context).width - 60 - 96 - 8 - 4;
    final scaler = MediaQuery.textScalerOf(context);
    final title = TextPainter(
      text: TextSpan(text: widget.activityTitle, style: titleStyle),
      textDirection: TextDirection.ltr,
      textScaler: scaler,
    )..layout(maxWidth: width);
    final caption = TextPainter(
      text: const TextSpan(text: _caption, style: AppType.caption),
      textDirection: TextDirection.ltr,
      textScaler: scaler,
    )..layout();
    final height = title.height + caption.height + 16;
    title.dispose();
    caption.dispose();
    return height < kToolbarHeight ? kToolbarHeight : height;
  }

  @override
  Widget build(BuildContext context) {
    final titleStyle = AppType.row.copyWith(fontSize: 16, height: 1.2);
    return AppScaffold(
      backgroundColor: Colors.white,
      toolbarHeight: _toolbarHeight(context, titleStyle),
      titleWidget: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          // The app bar's default style is one line with an ellipsis.
          Text(
            widget.activityTitle,
            softWrap: true,
            overflow: TextOverflow.visible,
            style: titleStyle,
          ),
          const Text(_caption, style: AppType.caption),
        ],
      ),
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
      body: LayoutBuilder(
        builder: (context, constraints) {
          final wide = constraints.maxWidth >= 720;
          return Column(
            children: [
              const SyncStatusBanner(),
              _rosterBanner(),
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
                          Expanded(
                            flex: 4,
                            child: Transform.translate(
                              offset: const Offset(0, -18),
                              child: _historyPanel(),
                            ),
                          ),
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
              const ColoredBox(color: Colors.black),
              MobileScanner(controller: _controller, onDetect: _onDetect),
              Center(
                child: SizedBox(
                  width: frameSize,
                  height: frameSize,
                  child: ScanViewfinder(scanning: !_processing),
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
                  left: 12,
                  right: 12,
                  top: 12,
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
                bottom: 32,
                child: Center(
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.55),
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(
                          color: Colors.white.withValues(alpha: 0.12)),
                    ),
                    child: Text(
                      _processing
                          ? 'Recording attendance…'
                          : 'Point at a student QR code',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
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
        );
      },
    );
  }

  Widget _historyPanel() {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 16, 6),
            child: Row(
              children: [
                Text('Recent scans',
                    style: AppType.headline.copyWith(fontSize: 16)),
                const SizedBox(width: 8),
                if (_recent.isNotEmpty)
                  AppChip(label: '${_recent.length}', tone: AppInk.muted),
              ],
            ),
          ),
          Expanded(
            child: _recent.isEmpty
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(AppIcons.scan,
                              size: 28, color: AppInk.faint),
                          const SizedBox(height: 8),
                          Text(
                            'Check-ins will appear here as you scan.',
                            textAlign: TextAlign.center,
                            style: AppType.rowSub.copyWith(fontSize: 14),
                          ),
                        ],
                      ),
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(12, 0, 12, 24),
                    itemCount: _recent.length,
                    itemBuilder: (context, i) =>
                        _ScanRecordTile(record: _recent[i], isFirst: i == 0),
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
    final who = [
      if (r.student?['name'] != null) r.student!['name'].toString(),
      if (r.studentNumber != null) r.studentNumber!,
    ].join(' · ');
    return AnimatedContainer(
      duration: AppMotion.slow,
      margin: const EdgeInsets.only(bottom: 4),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: isFirst ? color.withValues(alpha: 0.07) : Colors.transparent,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  who.isEmpty ? label : who,
                  style: AppType.row.copyWith(fontSize: 14.5),
                ),
                const SizedBox(height: 2),
                Text(
                  [
                    label,
                    if (r.message != null && r.message!.trim().isNotEmpty)
                      r.message!.trim(),
                  ].join(' · '),
                  style: AppType.caption.copyWith(
                    color: AppInk.onTint(color),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(_timeLabel(record.at), style: AppType.caption),
              const SizedBox(height: 4),
              // Not confirmed by the server yet: it can still refuse the scan.
              if (r.provisional || r.mode == 'queued')
                const Tooltip(
                  message: 'Saved on this phone, waiting for the server',
                  child: Icon(AppIcons.cloud_upload_rounded,
                      color: AppInk.caution, size: 16),
                )
              else if (r.mode == 'checked_in')
                const Icon(AppIcons.login_rounded,
                    color: AppInk.positive, size: 16)
              else if (r.mode == 'checked_out')
                const Icon(AppIcons.logout_rounded,
                    color: AppInk.accent, size: 16),
            ],
          ),
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
        return ('Checked in', AppInk.positive, AppIcons.check_circle_rounded);
      case 'checked_out':
        return ('Checked out', AppInk.accent, AppIcons.check_circle_rounded);
      case 'already_in':
        return ('Already in', AppInk.caution, AppIcons.info_outline_rounded);
      case 'duplicate':
        return ('Duplicate', AppInk.caution, AppIcons.block_rounded);
      case 'queued':
        return ('Saved offline', AppInk.caution, AppIcons.cloud_upload_rounded);
      case 'unverified':
        return (
          'Saved — server will check',
          AppInk.caution,
          AppIcons.help_outline_rounded
        );
      case 'too_soon_after_in':
        return ('Scanned moments ago', AppInk.caution, AppIcons.block_rounded);
      case 'inactive_student':
        return ('Inactive account', AppInk.critical, AppIcons.person_off_rounded);
      case 'unknown_qr':
        return ('Not on this roster', AppInk.critical, AppIcons.person_off_rounded);
      case 'invalid_qr':
        return ('Wrong QR code', AppInk.critical, AppIcons.qr_code_2_rounded);
      case 'stale_scan':
        return ('Outside activity hours', AppInk.critical, AppIcons.history_rounded);
      case 'expired_qr':
        return ('Expired QR', AppInk.critical, AppIcons.timer_off_rounded);
      case 'activity_ended':
      case 'activity_scheduled':
      case 'activity_closed':
        return (
          'Check-in unavailable',
          AppInk.caution,
          AppIcons.lock_clock_rounded
        );
      default:
        return (
          r.message ?? 'Error',
          AppInk.critical,
          AppIcons.error_outline_rounded
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
    final (label, color, icon) = switch (result.mode) {
      'checked_in' => ('Checked in', AppInk.positive, AppIcons.login_rounded),
      'checked_out' => ('Checked out', AppInk.accent, AppIcons.logout_rounded),
      'already_in' =>
        ('Already in', AppInk.caution, AppIcons.info_outline_rounded),
      'duplicate' => ('Duplicate scan', AppInk.caution, AppIcons.block_rounded),
      'queued' =>
        ('Saved offline', AppInk.caution, AppIcons.cloud_upload_rounded),
      'inactive_student' =>
        ('Inactive account', AppInk.critical, AppIcons.person_off_rounded),
      _ => ('Scanned', AppInk.accent, AppIcons.qr_code_rounded),
    };

    // Saved on the phone; the server has not answered yet and can still
    // reject it, so never let the card imply the attendance is final.
    if (result.provisional) {
      return ('$label · saved', color, AppIcons.cloud_upload_rounded);
    }
    return (label, color, icon);
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
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          boxShadow: AppShadow.lg,
        ),
        child: Padding(
          padding: const EdgeInsets.all(12),
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
                      style: AppType.row.copyWith(fontSize: 16.5),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      [
                        if (number.isNotEmpty) number,
                        if (course.isNotEmpty) course,
                        if (section.isNotEmpty) section,
                      ].join(' · '),
                      style: AppType.rowSub,
                    ),
                    const SizedBox(height: 8),
                    AppChip(label: label, tone: color, icon: icon),
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
          fontSize: 24,
          fontWeight: FontWeight.w700,
          color: AppInk.onTint(color),
        ),
      ),
    );
  }
}
