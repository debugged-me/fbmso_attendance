import 'dart:async';

import 'package:flutter/foundation.dart';

import 'connectivity_service.dart';
import 'outbox_service.dart';

/// Coordinates the offline→online sync and exposes a single status stream
/// the UI banner subscribes to.
///
/// States:
///   - "offline"   : no connectivity; writes queue locally.
///   - "syncing"   : online and the outbox is being drained.
///   - "synced"    : online and the outbox is empty.
///   - "pending"   : online but queued writes remain (a flush is in flight or
///                   a transient error left rows queued).
enum SyncStatus { offline, syncing, synced, pending }

class SyncOrchestrator extends SyncStatusNotifier {
  SyncOrchestrator._();
  static final SyncOrchestrator instance = SyncOrchestrator._();

  StreamSubscription<bool>? _connectivitySub;
  Timer? _pollTimer;
  SyncStatus _status = SyncStatus.synced;
  int _queued = 0;
  int _conflicts = 0;

  SyncStatus get status => _status;
  int get queuedCount => _queued;
  int get conflictCount => _conflicts;

  /// Begin monitoring. Call once at startup (after OutboxService.initialize).
  Future<void> start() async {
    await refresh();
    _connectivitySub?.cancel();
    _connectivitySub = ConnectivityService.connectionStream.listen(_onConnectivity);
    // Poll queued count every few seconds so the banner stays accurate even
    // when flushes happen internally.
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 3), (_) => refresh());
  }

  void _onConnectivity(bool online) {
    if (online) {
      OutboxService.flush();
    }
    refresh();
  }

  /// Recompute status from connectivity + outbox counts.
  Future<void> refresh() async {
    final online = await ConnectivityService.isConnected();
    _queued = await OutboxService.queuedCount();
    _conflicts = await OutboxService.conflictCount();

    if (!online) {
      _status = SyncStatus.offline;
    } else if (_queued > 0) {
      _status = SyncStatus.pending;
    } else {
      _status = SyncStatus.synced;
    }
    notify();
  }

  /// Mark the start of an explicit flush (called by OutboxService.flush via
  /// [markSyncing]) so the banner can show the spinner.
  void markSyncing() {
    _status = SyncStatus.syncing;
    notify();
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
