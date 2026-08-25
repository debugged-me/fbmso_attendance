import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Student's permanent QR code. The token is the same 32-hex string the
/// scanner consumes — so this QR is what the instructor's camera reads.
///
/// Issue/revoke buttons queue through the outbox when offline.
class MyQrScreen extends StatefulWidget {
  const MyQrScreen({super.key, required this.session});

  final AppSession session;

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
                            ),
            ),
          ),
        ],
      ),
    );
  }
}

class _QrView extends StatelessWidget {
  const _QrView({required this.qr, required this.displayName, required this.onIssue});
  final StudentQr qr;
  final String displayName;
  final VoidCallback onIssue;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
      children: [
        const SizedBox(height: 16),
        Center(
          child: AppCard.elevated(
            padding: const EdgeInsets.all(24),
            child: QrImageView(
              data: qr.token,
              version: QrVersions.auto,
              size: 260,
              gapless: true,
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
        const SizedBox(height: 20),
        Center(
          child: Text(
            displayName,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: AppInk.heading,
            ),
          ),
        ),
        const SizedBox(height: 4),
        Center(
          child: Text(
            qr.studentNumber,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: AppInk.muted,
            ),
          ),
        ),
        const SizedBox(height: 8),
        Center(
          child: Text(
            'Issued ${qr.issuedAt}',
            style: const TextStyle(
              fontSize: 12.5,
              color: AppInk.muted,
            ),
          ),
        ),
        const SizedBox(height: 24),
        AppButton(
          label: 'Issue new QR',
          icon: Icons.refresh,
          fullWidth: true,
          size: AppButtonSize.lg,
          style: AppButtonStyle.outline,
          onTap: onIssue,
        ),
        const SizedBox(height: 16),
        const Text(
          'Show this QR to the scanner at any activity to check in or out.',
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
