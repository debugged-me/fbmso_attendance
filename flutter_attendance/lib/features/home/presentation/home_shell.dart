import 'package:flutter/material.dart';

import '../../../core/design/tokens/app_brand.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/legal_dialogs.dart';

/// Temporary post-login landing. Phase 4+ replaces this with the real
/// per-role bottom-nav shells (StudentShell, InstructorShell, AdminShell...).
class HomeShell extends StatelessWidget {
  const HomeShell({super.key, required this.session, required this.controller});

  final AppSession session;
  final AuthController controller;

  @override
  Widget build(BuildContext context) {
    final schoolName =
        session.schoolName.trim().isEmpty ? AppBrand.name : session.schoolName;
    return Scaffold(
      appBar: AppBar(
        title: Text(schoolName),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () => _confirmLogout(context),
          ),
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert),
            tooltip: 'More',
            onSelected: (value) {
              switch (value) {
                case 'privacy':
                  LegalDialogs.showDataPrivacy(context,
                      schoolName: session.schoolName);
                  break;
                case 'terms':
                  LegalDialogs.showTermsOfUse(context,
                      schoolName: session.schoolName);
                  break;
                case 'about':
                  LegalDialogs.showAbout(context,
                      schoolName: session.schoolName);
                  break;
              }
            },
            itemBuilder: (_) => const [
              PopupMenuItem(value: 'privacy', child: Text('Data Privacy')),
              PopupMenuItem(value: 'terms', child: Text('Terms of Use')),
              PopupMenuItem(value: 'about', child: Text('About')),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Welcome, ${session.displayName}',
                        style: Theme.of(context).textTheme.headlineSmall),
                    const SizedBox(height: 8),
                    _row('School', session.schoolName),
                    _row('Role', session.position),
                    _row('Username', session.username),
                    _row('ID Number', session.idNumber),
                    _row('Email', session.email),
                    _row('SY', session.activeSy),
                    _row('Semester', session.activeSem),
                    _row('Base URL', session.baseUrl),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'Phase 2 scaffold — role shells and feature modules arrive in Phase 4+.',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
          ),
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 96,
            child: Text(label,
                style: const TextStyle(
                    color: AppTheme.textMuted, fontWeight: FontWeight.w600)),
          ),
          Expanded(child: Text(value.isEmpty ? '—' : value)),
        ],
      ),
    );
  }

  void _confirmLogout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Sign out?'),
        content: const Text('You will need to sign in again to continue.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Sign out'),
          ),
        ],
      ),
    );
    if (ok == true) {
      await controller.logout();
    }
  }
}
