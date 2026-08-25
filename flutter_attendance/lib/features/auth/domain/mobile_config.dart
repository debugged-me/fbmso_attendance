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
  );
}
