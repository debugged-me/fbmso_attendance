import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

import '../design/tokens/app_brand.dart';
import 'secure_storage_service.dart';

/// Biometric unlock service. Wraps local_auth so the rest of the app
/// doesn't deal with platform exceptions.
///
/// The flow is:
/// 1. On login, [setEnabled] is called if the user opts in.
/// 2. On cold start with a saved session, [gate] is called — if biometric
///    is enabled and the device supports it, the user must authenticate
///    before seeing any data.
/// 3. On logout, [disable] clears the flag.
class BiometricService {
  static final LocalAuthentication _auth = LocalAuthentication();

  static const _flagKey = 'biometric_enabled';

  /// Whether the device has biometric hardware enrolled.
  static Future<bool> get isAvailable async {
    try {
      final canCheck = await _auth.canCheckBiometrics;
      final isSupported = await _auth.isDeviceSupported();
      return canCheck || isSupported;
    } on PlatformException {
      return false;
    }
  }

  /// Prompt the user for biometric authentication. Returns true on success.
  static Future<bool> authenticate({
    String reason = 'Please authenticate to open the app.',
  }) async {
    try {
      return await _auth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(
          biometricOnly: false,
          stickyAuth: true,
          useErrorDialogs: true,
        ),
      );
    } on PlatformException {
      return false;
    }
  }

  /// Gate the app behind biometrics. Returns true if the user is allowed
  /// to proceed (either biometric is disabled, not available, or the user
  /// authenticated successfully). Returns false if the user cancelled.
  ///
  /// [schoolName] is the connected school's dynamic name (from
  /// `/api/mobile/config`); it falls back to [AppBrand.name] when empty.
  static Future<bool> gate({String schoolName = ''}) async {
    final enabled = await isEnabled;
    if (!enabled) return true;

    final available = await isAvailable;
    if (!available) return true; // device has no biometrics — don't block

    final name = schoolName.trim().isEmpty ? AppBrand.name : schoolName;
    return await authenticate(
      reason: 'Authenticate to open $name.',
    );
  }

  // ─── Enabled flag (stored in secure storage) ────────────────────────────

  static Future<bool> get isEnabled async {
    final value = await SecureStorageService.readRaw(_flagKey);
    return value == '1';
  }

  static Future<void> setEnabled(bool enabled) =>
      SecureStorageService.writeRaw(_flagKey, enabled ? '1' : '0');

  static Future<void> disable() => setEnabled(false);
}
