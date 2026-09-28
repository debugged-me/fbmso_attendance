import 'package:flutter/material.dart';

import '../design/tokens/app_tokens.dart';
import '../theme/app_theme.dart';
import '../../features/misc/presentation/sync_report_screen.dart';
import '../services/sync_orchestrator.dart';
import '../theme/app_icons.dart';

/// Persistent offline/sync banner shown at the top of every authenticated
/// screen. Subscribes to [SyncOrchestrator] and rebuilds on status change.
class SyncStatusBanner extends StatelessWidget {
  const SyncStatusBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: SyncOrchestrator.instance,
      builder: (context, _) {
        final s = SyncOrchestrator.instance;
        if (s.status == SyncStatus.synced && s.conflictCount == 0) {
          return const SizedBox.shrink(); // nothing to show when all good
        }

        final (label, color, icon) = _style(s);

        final ink = AppInk.onTint(color);
        return Material(
          color: Color.alphaBlend(color.withValues(alpha: 0.10), Colors.white),
          child: InkWell(
            // The banner is the only thing on screen that knows work is
            // pending, so it is also the way in to see what that work is.
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const SyncReportScreen()),
            ),
            child: SafeArea(
              bottom: false,
              child: Container(
                decoration: BoxDecoration(
                  border: Border(
                    bottom: BorderSide(color: color.withValues(alpha: 0.18)),
                  ),
                ),
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
                child: Row(
                  children: [
                    Icon(icon, size: 16, color: color),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        label,
                        style: TextStyle(
                          color: ink,
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    if (s.status == SyncStatus.syncing)
                      SizedBox(
                        width: 14,
                        height: 14,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: color,
                        ),
                      )
                    else
                      Icon(AppIcons.chevron_right_rounded,
                          size: 16, color: ink),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  (String, Color, IconData) _style(SyncOrchestrator s) {
    switch (s.status) {
      case SyncStatus.offline:
        return (
          s.queuedCount > 0
              ? 'Offline · ${s.queuedCount} change(s) saved on this phone'
              : 'Offline · changes are saved on this phone',
          AppTheme.ink600,
          AppIcons.cloud_off
        );
      case SyncStatus.syncing:
        return ('Syncing…', AppTheme.info, AppIcons.sync);
      case SyncStatus.pending:
        return (
          '${s.queuedCount} change(s) waiting to upload',
          AppTheme.warning,
          AppIcons.sync_problem
        );
      case SyncStatus.authRequired:
        return (
          'Sign in again to sync ${s.authBlockedCount} change(s)',
          AppTheme.error,
          AppIcons.lock_outline
        );
      case SyncStatus.synced:
        return (
          '${s.conflictCount} item(s) refused by the server · tap to review',
          AppTheme.error,
          AppIcons.error_outline
        );
    }
  }
}
