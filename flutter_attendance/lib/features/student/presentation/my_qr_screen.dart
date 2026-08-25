import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

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
        title: const Text('Issue new QR?'),
        content: const Text(
            'Your current QR will be revoked and a new one issued. '
            'The old QR will no longer work for check-ins.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Issue')),
        ],
      ),
    );
    if (ok != true) return;

    try {
      await _api.issueQr(baseUrl: widget.session.baseUrl, token: widget.session.token);
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('New QR issued.'), backgroundColor: AppTheme.success),
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
    return Scaffold(
      appBar: AppBar(title: const Text('My QR')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? Center(child: Text(_error!))
                      : _qr == null || !_qr!.isActive
                          ? _NoQrView(onIssue: _issue)
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
      padding: const EdgeInsets.all(24),
      children: [
        const SizedBox(height: 16),
        Center(
          child: Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: AppTheme.cardBorder),
            ),
            child: QrImageView(
              data: qr.token,
              version: QrVersions.auto,
              size: 260,
              gapless: true,
              eyeStyle: const QrEyeStyle(
                eyeShape: QrEyeShape.square,
                color: AppTheme.textDark,
              ),
              dataModuleStyle: const QrDataModuleStyle(
                dataModuleShape: QrDataModuleShape.square,
                color: AppTheme.textDark,
              ),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Center(
          child: Text(
            displayName,
            style: Theme.of(context).textTheme.titleMedium,
          ),
        ),
        Center(
          child: Text(
            qr.studentNumber,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ),
        const SizedBox(height: 8),
        Center(
          child: Text(
            'Issued ${qr.issuedAt}',
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ),
        const SizedBox(height: 24),
        FilledButton.icon(
          onPressed: onIssue,
          icon: const Icon(Icons.refresh),
          label: const Text('Issue new QR'),
        ),
        const SizedBox(height: 16),
        Text(
          'Show this QR to the scanner at any activity to check in or out.',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    );
  }
}

class _NoQrView extends StatelessWidget {
  const _NoQrView({required this.onIssue});
  final VoidCallback onIssue;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.qr_code_2, size: 64, color: AppTheme.textMuted),
            const SizedBox(height: 16),
            const Text('No active QR token.'),
            const SizedBox(height: 8),
            const Text(
              'Issue a QR to check in to activities.',
              style: TextStyle(color: AppTheme.textMuted),
            ),
            const SizedBox(height: 24),
            FilledButton.icon(
              onPressed: onIssue,
              icon: const Icon(Icons.qr_code_2),
              label: const Text('Issue QR'),
            ),
          ],
        ),
      ),
    );
  }
}
