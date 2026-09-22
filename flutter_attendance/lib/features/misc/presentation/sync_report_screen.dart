import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/outbox_service.dart';
import '../../../core/services/sync_orchestrator.dart';
import '../../../core/widgets/sync_status_banner.dart';

/// Everything the device is still holding, and everything the server refused.
///
/// Offline work is invisible by nature: a queued payment or a scan the server
/// rejected at sync would otherwise only ever appear in a toast nobody was
/// watching. This is the one place to see what has not landed and why.
class SyncReportScreen extends StatefulWidget {
  const SyncReportScreen({super.key});

  @override
  State<SyncReportScreen> createState() => _SyncReportScreenState();
}

class _SyncReportScreenState extends State<SyncReportScreen> {
  List<Map<String, dynamic>> _rows = [];
  bool _loading = true;
  bool _syncing = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final rows = await OutboxService.allRows();
    if (!mounted) return;
    setState(() {
      _rows = rows;
      _loading = false;
    });
  }

  Future<void> _syncNow() async {
    setState(() => _syncing = true);
    ConnectivityService.invalidateProbe();
    await OutboxService.flush();
    await SyncOrchestrator.instance.refresh();
    if (!mounted) return;
    setState(() => _syncing = false);
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final queued = _rows.where((r) => r['status'] == 'queued').toList();
    final conflicts = _rows.where((r) => r['status'] == 'conflict').toList();
    final authBlocked =
        _rows.where((r) => r['status'] == 'auth_blocked').toList();

    return AppScaffold(
      title: 'Sync report',
      actions: [
        IconButton(
          tooltip: 'Sync now',
          onPressed: _syncing ? null : _syncNow,
          icon: _syncing
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(Icons.sync_rounded),
        ),
      ],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _load,
                    child: ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        if (_rows.isEmpty) _allClear(),
                        if (authBlocked.isNotEmpty)
                          _section(
                            'Needs sign-in',
                            'Your session expired while these were waiting. '
                                'Sign in again and they will go out.',
                            AppInk.critical,
                            authBlocked,
                          ),
                        if (conflicts.isNotEmpty)
                          _section(
                            'Rejected by the server',
                            'These were sent but refused. They will not retry '
                                'on their own.',
                            AppInk.critical,
                            conflicts,
                          ),
                        if (queued.isNotEmpty)
                          _section(
                            'Waiting to upload',
                            'Saved on this device. They upload automatically '
                                'once there is a working connection.',
                            AppInk.caution,
                            queued,
                          ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _allClear() => Padding(
        padding: const EdgeInsets.only(top: 48),
        child: Column(
          children: [
            const Icon(Icons.cloud_done_rounded,
                size: 48, color: AppInk.positive),
            const SizedBox(height: 12),
            const Text(
              'Everything has synced',
              style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: AppInk.heading),
            ),
            const SizedBox(height: 4),
            Text(
              'Nothing is waiting on this device.',
              style: TextStyle(fontSize: 13, color: AppInk.muted),
            ),
          ],
        ),
      );

  Widget _section(String title, String blurb, Color tint,
      List<Map<String, dynamic>> rows) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              title,
              style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: AppInk.heading),
            ),
            const SizedBox(width: 8),
            Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: tint.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                '${rows.length}',
                style: TextStyle(
                    fontSize: 12, fontWeight: FontWeight.w700, color: tint),
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(blurb, style: TextStyle(fontSize: 12, color: AppInk.muted)),
        const SizedBox(height: 10),
        ...rows.map((r) => _tile(r, tint)),
        const SizedBox(height: 22),
      ],
    );
  }

  Widget _tile(Map<String, dynamic> row, Color tint) {
    final id = row['id'] as int;
    final status = (row['status'] ?? '').toString();
    final retries = (row['retry_count'] as int?) ?? 0;
    final error = (row['last_error'] ?? '').toString();
    final queuedAt = row['queued_at'] as int?;

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(_iconFor(row['operation']), size: 18, color: tint),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _labelFor(row['operation']),
                    style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: AppInk.heading),
                  ),
                ),
                if (queuedAt != null)
                  Text(_age(queuedAt),
                      style: TextStyle(fontSize: 11, color: AppInk.muted)),
              ],
            ),
            if (retries > 0 || error.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                error.isNotEmpty
                    ? _shorten(error)
                    : 'Retried $retries time(s)',
                style: TextStyle(fontSize: 11, color: AppInk.muted),
              ),
            ],
            if (status == 'conflict' || status == 'auth_blocked') ...[
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () async {
                      await OutboxService.dismissConflict(id);
                      await _load();
                    },
                    child: const Text('Discard'),
                  ),
                  const SizedBox(width: 4),
                  FilledButton(
                    onPressed: () async {
                      await OutboxService.retryConflict(id);
                      await _load();
                    },
                    child: const Text('Retry'),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  static String _labelFor(Object? operation) => switch (operation) {
        'scanner_consume' => 'Attendance scan',
        'self_checkin' => 'Self check-in',
        'payment_create' => 'Payment',
        'activity_create' => 'New activity',
        'activity_update' => 'Activity change',
        'activity_delete' => 'Activity deletion',
        _ => (operation ?? 'Change').toString().replaceAll('_', ' '),
      };

  static IconData _iconFor(Object? operation) => switch (operation) {
        'scanner_consume' || 'self_checkin' => Icons.qr_code_scanner_rounded,
        'payment_create' => Icons.receipt_long_rounded,
        'activity_create' ||
        'activity_update' ||
        'activity_delete' =>
          Icons.event_note_rounded,
        _ => Icons.cloud_upload_rounded,
      };

  static String _age(int millis) {
    final diff = DateTime.now()
        .difference(DateTime.fromMillisecondsSinceEpoch(millis));
    if (diff.inMinutes < 1) return 'just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    return '${diff.inDays}d ago';
  }

  static String _shorten(String value) {
    final flat = value.replaceAll(RegExp(r'\s+'), ' ').trim();
    return flat.length <= 140 ? flat : '${flat.substring(0, 140)}…';
  }
}
