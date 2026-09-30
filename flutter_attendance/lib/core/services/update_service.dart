import 'package:flutter/foundation.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../features/auth/domain/mobile_config.dart';

/// How the installed build compares to the one the server advertises.
enum UpdateVerdict { none, available, required }

class AppUpdate {
  const AppUpdate({
    required this.latestVersion,
    required this.latestCode,
    required this.url,
    required this.required,
  });

  /// Display string only ("1.0.1") — comparisons use [latestCode].
  final String latestVersion;
  final int latestCode;
  final String url;
  final bool required;
}

/// Turns the version fields in `/api/mobile/config` into an [AppUpdate] the
/// UI can show. Singleton like [SyncOrchestrator] so the banner can listen
/// from the top of the tree regardless of which screen is up.
///
/// Dismissal is per-build: closing the strip silences it until the server
/// advertises a NEWER versionCode than the dismissed one. A required update
/// cannot be dismissed.
class UpdateService extends ChangeNotifier {
  UpdateService._();
  static final UpdateService instance = UpdateService._();

  static const _dismissedKey = 'update_dismissed_code';

  AppUpdate? _update;
  AppUpdate? get update => _update;

  /// Pure decision — kept separate from PackageInfo so it is unit-testable.
  static UpdateVerdict verdictFor({
    required int installed,
    required int latest,
    required int minCode,
    required int dismissedCode,
  }) {
    if (latest <= 0 || installed <= 0 || installed >= latest) {
      return UpdateVerdict.none;
    }
    if (installed < minCode) return UpdateVerdict.required;
    return dismissedCode == latest ? UpdateVerdict.none : UpdateVerdict.available;
  }

  /// Called every time `/config` answers. A failed or absent check leaves
  /// whatever was last known — an update prompt must never depend on signal.
  Future<void> evaluate(MobileConfig config) async {
    if (kIsWeb) return; // the web build is always the deploy's own version
    final latest = config.appLatestVersionCode;
    if (latest <= 0 || config.appUpdateUrl.isEmpty) return;

    int installed = 0;
    try {
      installed =
          int.tryParse((await PackageInfo.fromPlatform()).buildNumber) ?? 0;
    } catch (_) {
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final verdict = verdictFor(
      installed: installed,
      latest: latest,
      minCode: config.appMinVersionCode,
      dismissedCode: prefs.getInt(_dismissedKey) ?? 0,
    );
    if (verdict == UpdateVerdict.none) {
      if (_update != null) {
        _update = null;
        notifyListeners();
      }
      return;
    }
    _update = AppUpdate(
      latestVersion: config.appLatestVersion,
      latestCode: latest,
      url: config.appUpdateUrl,
      required: verdict == UpdateVerdict.required,
    );
    notifyListeners();
  }

  /// Hide the strip until the server advertises a build newer than the one
  /// that was dismissed. Does nothing for a required update.
  Future<void> dismiss() async {
    final u = _update;
    if (u == null || u.required) return;
    _update = null;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_dismissedKey, u.latestCode);
  }
}
