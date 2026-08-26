/// App-wide brand strings.
///
/// Single source of truth for the product name shown on the splash, login,
/// AppBar, biometric prompt, etc. Keeps the brand neutral and multi-tenant —
/// the connected school's name still appears in the post-login profile, but
/// the login surface itself never hardcodes a specific institution.
class AppBrand {
  const AppBrand._();

  /// Short product name used in titles, AppBar, splash, biometric prompts.
  static const String name = 'Attendance Portal';

  /// Subtitle shown under the brand name on the login screen.
  static const String tagline = 'Sign in with your portal account';
}
