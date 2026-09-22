import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:http/http.dart' as http;

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

  /// Whether a network interface is associated (true = online).
  ///
  /// This is the cheap check. It says "connected" for a venue's one-bar signal
  /// that passes no traffic at all, so anything reporting sync state to the
  /// user should prefer [isReachable].
  static Future<bool> isConnected() async {
    final results = await _connectivity.checkConnectivity();
    return results.isNotEmpty &&
        results.any((r) => r != ConnectivityResult.none);
  }

  /// Base URL of the paired school, set once the session is known. Empty
  /// disables probing, and [isReachable] falls back to the interface check.
  static String probeBaseUrl = '';

  static const _probeTimeout = Duration(seconds: 5);
  static const _probeCacheWindow = Duration(seconds: 15);
  static bool? _lastProbe;
  static DateTime? _lastProbeAt;

  /// Whether the server actually answers. Cached briefly because the sync
  /// banner polls this on a timer.
  static Future<bool> isReachable() async {
    if (!await isConnected()) {
      _lastProbe = false;
      _lastProbeAt = DateTime.now();
      return false;
    }
    if (probeBaseUrl.isEmpty) return true;

    final cachedAt = _lastProbeAt;
    if (cachedAt != null &&
        DateTime.now().difference(cachedAt) < _probeCacheWindow) {
      return _lastProbe ?? true;
    }

    var reachable = false;
    try {
      final base = probeBaseUrl.endsWith('/')
          ? probeBaseUrl.substring(0, probeBaseUrl.length - 1)
          : probeBaseUrl;
      final response = await http
          .get(Uri.parse('$base/api/mobile/config'))
          .timeout(_probeTimeout);
      reachable = response.statusCode < 500;
    } catch (_) {
      reachable = false;
    }

    _lastProbe = reachable;
    _lastProbeAt = DateTime.now();
    return reachable;
  }

  /// Drop the cached probe so the next check hits the network. Called when
  /// connectivity flips, where the cached answer is certainly stale.
  static void invalidateProbe() {
    _lastProbe = null;
    _lastProbeAt = null;
  }

  static void dispose() {
    _subscription?.cancel();
    _connectionController.close();
  }
}
