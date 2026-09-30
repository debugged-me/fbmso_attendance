/// Public configuration returned by `GET /api/mobile/config`.
///
/// Fetched before login so the welcome/login screen can show the school name,
/// active SY/sem, and logo for whatever base URL the user typed in.
class MobileConfig {
  const MobileConfig({
    required this.ok,
    required this.schoolName,
    required this.activeSy,
    required this.activeSem,
    required this.allowSignup,
    required this.loginLogoUrl,
    required this.loginBackgroundUrl,
    required this.baseUrl,
    required this.apiBaseUrl,
    required this.appLatestVersion,
    required this.appLatestVersionCode,
    required this.appMinVersionCode,
    required this.appUpdateUrl,
  });

  final bool ok;
  final String schoolName;
  final String activeSy;
  final String activeSem;
  final String allowSignup;
  final String loginLogoUrl;
  final String loginBackgroundUrl;
  final String baseUrl;
  final String apiBaseUrl;

  /// Newest released build, per the server's config.php. versionCode is the
  /// comparable number; the version string is only for display. Absent or 0
  /// on servers that predate the field — the app then skips the check.
  final String appLatestVersion;
  final int appLatestVersionCode;

  /// Builds below this are told to update before they can be used.
  final int appMinVersionCode;

  /// Where the update strip points — the server's APK download.
  final String appUpdateUrl;

  factory MobileConfig.fromJson(Map<String, dynamic> json) {
    return MobileConfig(
      ok: json['ok'] == true,
      schoolName: (json['school_name'] ?? '').toString(),
      activeSy: (json['active_sy'] ?? '').toString(),
      activeSem: (json['active_sem'] ?? '').toString(),
      allowSignup: (json['allow_signup'] ?? 'No').toString(),
      loginLogoUrl: (json['login_logo_url'] ?? '').toString(),
      loginBackgroundUrl: (json['login_background_url'] ?? '').toString(),
      baseUrl: (json['base_url'] ?? '').toString(),
      apiBaseUrl: (json['api_base_url'] ?? '').toString(),
      appLatestVersion: (json['app_latest_version'] ?? '').toString(),
      appLatestVersionCode:
          (json['app_latest_version_code'] as num?)?.toInt() ?? 0,
      appMinVersionCode:
          (json['app_min_version_code'] as num?)?.toInt() ?? 0,
      appUpdateUrl: (json['app_update_url'] ?? '').toString(),
    );
  }

  static const empty = MobileConfig(
    ok: false,
    schoolName: '',
    activeSy: '',
    activeSem: '',
    allowSignup: 'No',
    loginLogoUrl: '',
    loginBackgroundUrl: '',
    baseUrl: '',
    apiBaseUrl: '',
    appLatestVersion: '',
    appLatestVersionCode: 0,
    appMinVersionCode: 0,
    appUpdateUrl: '',
  );
}
