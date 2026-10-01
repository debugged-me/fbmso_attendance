import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_brand.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../attendance/presentation/my_logs_screen.dart';
import '../../attendance/presentation/poster_scan_screen.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';
import '../../../core/theme/app_icons.dart';

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
        title: const Text('Issue a new QR?'),
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
          const SnackBar(content: Text('New QR issued.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      showBackButton: false,
      leading: widget.menuButton,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? ListView(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                      children: const [
                        SizedBox(height: 12),
                        CardSkeleton(),
                      ],
                    )
                  : _error != null
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            AppEmptyState(
                              icon: AppIcons.cloud_off_rounded,
                              title: "Couldn't load your QR",
                              subtitle: _error,
                              action: 'Try again',
                              onAction: _load,
                            ),
                          ],
                        )
                      : _qr == null || !_qr!.isActive
                          ? ListView(
                              children: [
                                const SizedBox(height: 80),
                                AppEmptyState(
                                  icon: AppIcons.qr_code_2,
                                  title: 'No active QR yet',
                                  subtitle:
                                      'Issue your QR code to check in to activities.',
                                  action: 'Issue my QR',
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
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
      children: [
        const AppPageHeader(
          title: 'My QR',
          subtitle: 'Show this at the scanner to check in or out.',
          padding: EdgeInsets.fromLTRB(4, 4, 4, 16),
        ),
        _PassCard(qr: qr, session: session, displayName: displayName),
        const SizedBox(height: 20),
        AppButton(
          label: 'Scan an activity poster',
          icon: AppIcons.scan,
          size: AppButtonSize.lg,
          fullWidth: true,
          onTap: () {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => PosterScanScreen(session: session),
              ),
            );
          },
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: AppButton(
                label: 'My logs',
                icon: AppIcons.history_rounded,
                style: AppButtonStyle.outline,
                fullWidth: true,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => MyLogsScreen(session: session),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: AppButton(
                label: 'New QR',
                icon: AppIcons.refresh,
                style: AppButtonStyle.outline,
                fullWidth: true,
                onTap: onIssue,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Text(
          'Tip: turn up your screen brightness so the scanner reads it on '
          'the first try.',
          textAlign: TextAlign.center,
          style: AppType.caption,
        ),
      ],
    );
  }
}

/// The QR as a pass: school and status on top, a large high-contrast code in
/// the middle, and the holder's name and number under a perforation.
class _PassCard extends StatelessWidget {
  const _PassCard({
    required this.qr,
    required this.session,
    required this.displayName,
  });

  final StudentQr qr;
  final AppSession session;
  final String displayName;

  @override
  Widget build(BuildContext context) {
    final schoolName =
        session.schoolName.trim().isEmpty ? AppBrand.name : session.schoolName;
    final active = qr.status.toLowerCase() == 'active';
    final issued = qr.issuedAt.length >= 10 ? qr.issuedAt.substring(0, 10) : qr.issuedAt;
    return Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 420),
        child: AppCard.elevated(
          radius: AppRadius.xl,
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  const AppBrandMark(size: 30),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          schoolName,
                          style: AppType.row.copyWith(fontSize: 14),
                        ),
                        Text('Attendance pass', style: AppType.caption),
                      ],
                    ),
                  ),
                  AppChip(
                    label: active ? 'Active' : qr.status,
                    tone: active ? AppInk.positive : AppInk.caution,
                    dot: true,
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Center(
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(AppRadius.lg),
                    border: Border.all(color: AppInk.rule),
                  ),
                  child: QrImageView(
                    data: qr.token,
                    version: QrVersions.auto,
                    size: 228,
                    gapless: true,
                    padding: EdgeInsets.zero,
                    eyeStyle: const QrEyeStyle(
                      eyeShape: QrEyeShape.square,
                      color: AppInk.heading,
                    ),
                    dataModuleStyle: const QrDataModuleStyle(
                      dataModuleShape: QrDataModuleShape.square,
                      color: AppInk.heading,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              const _Perforation(),
              const SizedBox(height: 14),
              Text(
                displayName,
                textAlign: TextAlign.center,
                style: AppType.headline.copyWith(fontSize: 20),
              ),
              const SizedBox(height: 4),
              Text(
                qr.studentNumber,
                textAlign: TextAlign.center,
                style: AppType.value.copyWith(
                  fontSize: 15,
                  color: AppInk.secondary,
                  letterSpacing: 0.6,
                ),
              ),
              if (issued.isNotEmpty) ...[
                const SizedBox(height: 10),
                Text(
                  'Issued $issued',
                  textAlign: TextAlign.center,
                  style: AppType.caption,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

/// Ticket perforation: a dashed rule with a notch cut into each edge.
class _Perforation extends StatelessWidget {
  const _Perforation();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 20,
      child: Stack(
        clipBehavior: Clip.none,
        alignment: Alignment.center,
        children: [
          const Positioned(left: -32, child: _Notch()),
          const Positioned(right: -32, child: _Notch()),
          SizedBox(
            height: 1,
            width: double.infinity,
            child: CustomPaint(painter: _DashPainter()),
          ),
        ],
      ),
    );
  }
}

class _Notch extends StatelessWidget {
  const _Notch();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 24,
      height: 24,
      decoration: const BoxDecoration(
        color: AppInk.page,
        shape: BoxShape.circle,
      ),
    );
  }
}

class _DashPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = AppInk.ruleStrong
      ..strokeWidth = 1.2;
    const dash = 6.0, gap = 5.0;
    for (var x = 0.0; x < size.width; x += dash + gap) {
      canvas.drawLine(Offset(x, 0), Offset((x + dash).clamp(0, size.width), 0),
          paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
