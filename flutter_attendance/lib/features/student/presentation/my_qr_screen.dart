import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_brand.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../attendance/presentation/poster_scan_screen.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Student's permanent QR code. The token is the same 32-hex string the
/// scanner consumes — so this QR is what the instructor's camera reads.
///
/// Issue/revoke buttons queue through the outbox when offline.
class MyQrScreen extends StatefulWidget {
  const MyQrScreen({super.key, required this.session, this.menuButton});

  final AppSession session;
  final Widget? menuButton;

  @override
  State<MyQrScreen> createState() => _MyQrScreenState();
}

class _MyQrScreenState extends State<MyQrScreen> {
  late final StudentApi _api;
  StudentQr? _qr;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = StudentApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final qr = await _api.myQr(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _qr = qr;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _issue() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        title: const Text('Issue new QR?'),
        content: const Text(
            'Your current QR will be revoked and a new one issued. '
            'The old QR will no longer work for check-ins.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Issue'),
          ),
        ],
      ),
    );
    if (ok != true) return;

    try {
      await _api.issueQr(baseUrl: widget.session.baseUrl, token: widget.session.token);
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('New QR issued.'),
            backgroundColor: AppTheme.success,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString()),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'My QR',
      showBackButton: false,
      leading: widget.menuButton,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            AppEmptyState(
                              icon: Icons.error_outline,
                              title: 'Could not load QR',
                              subtitle: _error,
                              action: 'Retry',
                              onAction: _load,
                            ),
                          ],
                        )
                      : _qr == null || !_qr!.isActive
                          ? ListView(
                              children: [
                                const SizedBox(height: 80),
                                AppEmptyState(
                                  icon: Icons.qr_code_2,
                                  title: 'No active QR token',
                                  subtitle:
                                      'Issue a QR to check in to activities.',
                                  action: 'Issue QR',
                                  onAction: _issue,
                                ),
                              ],
                            )
                          : _QrView(
                              qr: _qr!,
                              displayName: widget.session.displayName,
                              onIssue: _issue,
                              session: widget.session,
                            ),
            ),
          ),
        ],
      ),
    );
  }
}

class _QrView extends StatelessWidget {
  const _QrView({
    required this.qr,
    required this.displayName,
    required this.onIssue,
    required this.session,
  });

  final StudentQr qr;
  final String displayName;
  final VoidCallback onIssue;
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    final schoolName =
        session.schoolName.trim().isEmpty ? AppBrand.name : session.schoolName;

    // Split the display name into LAST, FIRST lines like the web card.
    final last = session.lastName.trim();
    final first = session.firstName.trim();
    final String line1;
    final String line2;
    if (last.isNotEmpty || first.isNotEmpty) {
      line1 = last.toUpperCase();
      line2 = first.toUpperCase();
    } else {
      line1 = displayName.toUpperCase();
      line2 = '';
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
      children: [
        const SizedBox(height: 12),
        _QrBankCard(
          qr: qr,
          line1: line1,
          line2: line2,
          status: qr.status,
          schoolName: schoolName,
        ),
        const SizedBox(height: 22),
        Row(
          children: [
            Expanded(
              child: AppButton(
                label: 'Issue new QR',
                icon: Icons.refresh,
                size: AppButtonSize.lg,
                style: AppButtonStyle.outline,
                onTap: onIssue,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: AppButton(
                label: 'Scan Poster',
                icon: Icons.qr_code_scanner_rounded,
                size: AppButtonSize.lg,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => PosterScanScreen(session: session),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        const Text(
          'Show this QR to the scanner at any activity to check in or out.\n'
          'Or tap "Scan Poster" to scan an activity poster for self check-in.',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 12.5,
            color: AppInk.muted,
            height: 1.45,
          ),
        ),
      ],
    );
  }
}

/// Bank-card style QR card mirroring the web's `.qr-card` in
/// `application/views/student_my_qr.php`.
///
/// Layout (top → bottom):
///  • Top row: gold chip + school name + "Attendance ID" sub, status pill
///  • Mid row: white QR tile (left) + Cardholder name (right)
///  • Bottom row: Student No. (left) + "Attendance Portal" wordmark (right)
///
/// Background uses the same gradient as the web card
/// (`#1a2a6c → #2a4090 → #3b5fd4`) with two radial highlights.
class _QrBankCard extends StatelessWidget {
  const _QrBankCard({
    required this.qr,
    required this.line1,
    required this.line2,
    required this.status,
    required this.schoolName,
  });

  final StudentQr qr;
  final String line1;
  final String line2;
  final String status;
  final String schoolName;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 460),
        child: AspectRatio(
          aspectRatio: 1.586,
          child: DecoratedBox(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(22),
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  Color(0xFF1A2A6C),
                  Color(0xFF2A4090),
                  Color(0xFF3B5FD4),
                ],
              ),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF0D1B4B).withValues(alpha: 0.32),
                  blurRadius: 50,
                  offset: const Offset(0, 24),
                ),
                BoxShadow(
                  color: const Color(0xFF0D1B4B).withValues(alpha: 0.18),
                  blurRadius: 14,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Stack(
              children: [
                // ── Radial highlights (mirrors the web's radial-gradient
                //    overlays) ────────────────────────────────────────────
                Positioned(
                  top: -60,
                  right: -40,
                  child: Container(
                    width: 200,
                    height: 200,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      backgroundBlendMode: BlendMode.srcOver,
                      gradient: RadialGradient(
                        colors: [
                          const Color(0xFF7CFFB2).withValues(alpha: 0.35),
                          Colors.transparent,
                        ],
                      ),
                    ),
                  ),
                ),
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(22),
                      gradient: RadialGradient(
                        radius: 1.2,
                        center: const Alignment(-1, -1),
                        colors: [
                          Colors.white.withValues(alpha: 0.18),
                          Colors.transparent,
                        ],
                        stops: const [0, 0.45],
                      ),
                    ),
                  ),
                ),
                // ── Sheen sweep (subtle diagonal highlight) ─────────────
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(22),
                      gradient: LinearGradient(
                        begin: const Alignment(-0.6, 0),
                        end: const Alignment(0.6, 0),
                        colors: [
                          Colors.transparent,
                          Colors.white.withValues(alpha: 0.06),
                          Colors.transparent,
                        ],
                        stops: const [0.3, 0.5, 0.7],
                      ),
                    ),
                  ),
                ),

                // ── Card content ────────────────────────────────────────
                Padding(
                  padding: const EdgeInsets.fromLTRB(22, 22, 22, 22),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Top row
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Gold chip
                          _GoldChip(),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  AppBrand.name.toUpperCase(),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 0.6,
                                    height: 1.1,
                                  ),
                                ),
                                const Text(
                                  'ATTENDANCE ID',
                                  style: TextStyle(
                                    color: Colors.white70,
                                    fontSize: 9,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 1.2,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          _StatusPill(status: status),
                        ],
                      ),

                      // Mid row
                      Row(
                        children: [
                          // QR tile
                          Container(
                            width: 116,
                            height: 116,
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.22),
                                  blurRadius: 14,
                                  offset: const Offset(0, 6),
                                ),
                              ],
                            ),
                            child: QrImageView(
                              data: qr.token,
                              version: QrVersions.auto,
                              gapless: true,
                              eyeStyle: const QrEyeStyle(
                                eyeShape: QrEyeShape.square,
                                color: Color(0xFF0F172A),
                              ),
                              dataModuleStyle: const QrDataModuleStyle(
                                dataModuleShape: QrDataModuleShape.square,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                          ),
                          const SizedBox(width: 18),
                          // Cardholder
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'CARDHOLDER',
                                  style: TextStyle(
                                    color: Colors.white70,
                                    fontSize: 8.5,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 1.6,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  line1,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 15,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: 0.4,
                                    height: 1.2,
                                  ),
                                ),
                                if (line2.isNotEmpty) ...[
                                  const SizedBox(height: 2),
                                  Text(
                                    line2,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 12.5,
                                      fontWeight: FontWeight.w600,
                                      letterSpacing: 0.3,
                                      height: 1.2,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ],
                      ),

                      // Bottom row
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          // Student No.
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Text(
                                  'STUDENT NO.',
                                  style: TextStyle(
                                    color: Colors.white70,
                                    fontSize: 8.5,
                                    fontWeight: FontWeight.w400,
                                    letterSpacing: 1.1,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  qr.studentNumber,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 0.6,
                                    fontFamily: 'InstrumentSans',
                                  ),
                                ),
                              ],
                            ),
                          ),
                          // Wordmark
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                AppBrand.name,
                                style: const TextStyle(
                                  color: Colors.white70,
                                  fontSize: 9,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 1.8,
                                ),
                              ),
                              Text(
                                _shortSchoolName(schoolName),
                                textAlign: TextAlign.right,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.4,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// Shorten the school name for the bottom-right wordmark so it fits the
  /// card. Mirrors the web's `<b>FBMSO</b>` line — but dynamic.
  String _shortSchoolName(String name) {
    final trimmed = name.trim();
    if (trimmed.isEmpty) return AppBrand.name;
    // Take the first letters of each significant word, capped at 6 chars.
    final words = trimmed
        .split(RegExp(r'\s+'))
        .where((w) => w.isNotEmpty && w.toLowerCase() != 'of' && w.toLowerCase() != 'and' && w.toLowerCase() != 'the')
        .toList();
    if (words.isEmpty) return trimmed.length > 12 ? trimmed.substring(0, 12) : trimmed;
    final acronym = words.map((w) => w[0].toUpperCase()).join();
    return acronym.length > 8 ? acronym.substring(0, 8) : acronym;
  }
}

/// Gold EMV-style chip mirroring the web's `.qc-chip`.
class _GoldChip extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      width: 34,
      height: 26,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(7),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFFF6D365), Color(0xFFFDA085)],
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 0,
            spreadRadius: 1,
          ),
        ],
      ),
      child: Center(
        child: Container(
          width: 22,
          height: 16,
          decoration: BoxDecoration(
            border: Border.symmetric(
              vertical: BorderSide(
                color: Colors.black.withValues(alpha: 0.18),
                width: 1,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Active/inactive status pill mirroring the web's `.qr-card-status`.
class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final isActive = status.toLowerCase() == 'active';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.28)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isActive ? const Color(0xFF7CFFB2) : Colors.amber,
              boxShadow: [
                BoxShadow(
                  color: (isActive ? const Color(0xFF7CFFB2) : Colors.amber)
                      .withValues(alpha: 0.8),
                  blurRadius: 6,
                ),
              ],
            ),
          ),
          const SizedBox(width: 5),
          Text(
            status.toUpperCase(),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 9,
              fontWeight: FontWeight.w800,
              letterSpacing: 1,
            ),
          ),
        ],
      ),
    );
  }
}
