import 'dart:async';

import 'package:flutter/foundation.dart';

import 'connectivity_service.dart';
import 'notification_service.dart';
import 'outbox_service.dart';

/// Coordinates the offline→online sync and exposes a single status stream
/// the UI banner subscribes to.
///
/// States:
///   - "offline"      : no connectivity; writes queue locally.
///   - "syncing"      : online and the outbox is being drained.
///   - "synced"       : online and nothing is stuck (a scan taken seconds
///                      ago may still be on its way up).
///   - "pending"      : online but queued writes are not going out (a flush
///                      is in flight or a transient error left rows backing off).
///   - "authRequired" : the session expired while writes were queued; they stay
///                      parked until the user signs in again.
enum SyncStatus { offline, syncing, synced, pending, authRequired }

class SyncOrchestrator extends SyncStatusNotifier {
  SyncOrchestrator._();
  static final SyncOrchestrator instance = SyncOrchestrator._();

  /// Every scan passes through the outbox and is normally uploaded within a
  /// second or two; only rows older than this (or already retried) are
  /// reported as pending, so the banner does not flash on every scan.
  static const _stuckAfter = Duration(seconds: 10);

  StreamSubscription<bool>? _connectivitySub;
  Timer? _pollTimer;
  SyncStatus _status = SyncStatus.synced;
  int _queued = 0;
  int _conflicts = 0;
  int _authBlocked = 0;
  bool _wasOffline = false;

  // One "batch": from the queue leaving zero until it is back at zero.
  int _batchPeak = 0;
  int _batchConflictsBefore = 0;
  bool _batchSawOffline = false;

  SyncStatus get status => _status;
  int get queuedCount => _queued;
  int get conflictCount => _conflicts;
  int get authBlockedCount => _authBlocked;

  /// Begin monitoring. Call once at startup (after OutboxService.initialize).
  Future<void> start() async {
    OutboxService.onFlushStart = markSyncing;
    await refresh();
    _connectivitySub?.cancel();
    _connectivitySub = ConnectivityService.connectionStream.listen(_onConnectivity);
    // Poll every few seconds: keeps the banner accurate even when flushes
    // happen internally, and is what retries the queue (see refresh).
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 3), (_) => refresh());
  }

  void _onConnectivity(bool online) {
    ConnectivityService.invalidateProbe();
    if (online) {
      if (_wasOffline && _queued > 0) {
        NotificationService.instance.add(
          title: 'Back online',
          body: 'Syncing $_queued pending item(s)…',
          type: 'sync',
        );
      }
      OutboxService.flush();
    } else {
      NotificationService.instance.add(
        title: 'You are offline',
        body: 'Changes will be saved locally and synced when you reconnect.',
        type: 'warning',
      );
    }
    _wasOffline = !online;
    refresh();
  }

  /// Recompute status from connectivity + outbox counts, and keep the queue
  /// moving while the server is reachable.
  Future<void> refresh() async {
    // Reachability, not interface type: a venue's dead one-bar signal would
    // otherwise show "synced" while nothing can actually leave the device.
    final online = await ConnectivityService.isReachable();
    final queue = await OutboxService.queuedSummary();
    _queued = queue.count;
    _conflicts = await OutboxService.conflictCount();
    _authBlocked = await OutboxService.authBlockedCount();

    if (_queued > 0 && _batchPeak == 0) {
      _batchConflictsBefore = _conflicts;
      _batchSawOffline = false;
    }
    if (_queued > _batchPeak) _batchPeak = _queued;

    if (!online) {
      _status = SyncStatus.offline;
      _wasOffline = true;
      if (_queued > 0) _batchSawOffline = true;
    } else if (_authBlocked > 0) {
      _status = SyncStatus.authRequired;
    } else if (_queued > 0) {
      final ageMs = DateTime.now().millisecondsSinceEpoch - queue.oldestQueuedAt;
      final stuck = queue.maxRetries > 0 || ageMs >= _stuckAfter.inMilliseconds;
      _status = stuck ? SyncStatus.pending : SyncStatus.synced;
      // Nothing else sends a row again once its backoff has passed, or drains
      // a queue that was already waiting when the app started. flush() does
      // nothing while one is running or the head of the queue is backing off.
      OutboxService.flush();
    } else {
      _status = SyncStatus.synced;
      if (_batchPeak > 0) _finishBatch();
    }
    notify();
  }

  /// The queue just emptied. After an offline stretch, say how it went —
  /// including anything the server refused, which "all synced" would hide.
  void _finishBatch() {
    if (_batchSawOffline) {
      final refused = (_conflicts - _batchConflictsBefore).clamp(0, _batchPeak);
      if (refused == 0) {
        NotificationService.instance.add(
          title: 'Sync complete',
          body: 'All $_batchPeak pending item(s) uploaded.',
          type: 'success',
        );
      } else {
        NotificationService.instance.add(
          title: 'Sync finished — $refused refused',
          body: '$refused of $_batchPeak item(s) were refused by the server. '
              'Open the Sync report to see why and retry them.',
          type: 'warning',
        );
      }
    }
    _batchPeak = 0;
    _batchSawOffline = false;
  }

  /// Mark the start of an explicit flush (called by OutboxService.flush via
  /// [markSyncing]) so the banner can show the spinner.
  void markSyncing() {
    // Only worth a spinner when something has been waiting.
    if (_status == SyncStatus.pending || _status == SyncStatus.offline) {
      _status = SyncStatus.syncing;
      notify();
    }
  }

  void notify() {
    notifyListeners();
  }

  @override
  Future<void> dispose() async {
    await _connectivitySub?.cancel();
    _pollTimer?.cancel();
    super.dispose();
  }
}

/// Thin change-notifier base so the UI can listen with AnimatedBuilder.
class SyncStatusNotifier extends ChangeNotifier {}
