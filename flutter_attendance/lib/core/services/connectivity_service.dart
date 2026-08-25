import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';

/// Broadcasts online/offline state. The SyncOrchestrator listens to this and
/// drains the outbox when connectivity returns.
class ConnectivityService {
  static final Connectivity _connectivity = Connectivity();
  static StreamSubscription<List<ConnectivityResult>>? _subscription;
  static final StreamController<bool> _connectionController =
      StreamController<bool>.broadcast();

  /// Stream of connection status changes (true = online).
  static Stream<bool> get connectionStream => _connectionController.stream;

  /// Start monitoring. Call once at app startup.
  static void initialize() {
    _subscription?.cancel();
    _subscription = _connectivity.onConnectivityChanged.listen((results) {
      final isConnected = results.isNotEmpty &&
          results.any((r) => r != ConnectivityResult.none);
      _connectionController.add(isConnected);
    });
  }

  /// Current connectivity (true = online).
  static Future<bool> isConnected() async {
    final results = await _connectivity.checkConnectivity();
    return results.isNotEmpty &&
        results.any((r) => r != ConnectivityResult.none);
  }

  static void dispose() {
    _subscription?.cancel();
    _connectionController.close();
  }
}
