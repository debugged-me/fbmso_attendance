import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../services/sync_orchestrator.dart';

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

        return Material(
          color: color,
          child: SafeArea(
            bottom: false,
            child: Padding(
              padding:
                  const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  Icon(icon, size: 16, color: Colors.white),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      label,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  if (s.status == SyncStatus.syncing)
                    const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    ),
                ],
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
              ? 'Offline — ${s.queuedCount} change(s) queued'
              : 'Offline — changes will be queued',
          AppTheme.textMuted,
          Icons.cloud_off
        );
      case SyncStatus.syncing:
        return ('Syncing…', AppTheme.info, Icons.sync);
      case SyncStatus.pending:
        return (
          'Pending — ${s.queuedCount} change(s) uploading',
          AppTheme.warning,
          Icons.sync_problem
        );
      case SyncStatus.synced:
        return (
          '${s.conflictCount} conflict(s) need attention',
          AppTheme.error,
          Icons.error_outline
        );
    }
  }
}
