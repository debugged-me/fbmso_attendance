import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_brand.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';
import '../domain/mobile_config.dart';
import 'auth_controller.dart';
import 'forgot_password_screen.dart';
import 'legal_dialogs.dart';
import 'register_screen.dart';

/// Credential entry. The base URL was already chosen on the welcome screen.
/// SY/semester come from the server config automatically — the user never
/// types them.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.controller});

  final AuthController controller;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  late final TextEditingController _usernameController;
  late final TextEditingController _passwordController;

  bool _obscurePassword = true;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _usernameController = TextEditingController();
    _passwordController = TextEditingController();
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _signIn() async {
    FocusScope.of(context).unfocus();
    HapticFeedback.mediumImpact();

    final username = _usernameController.text.trim();
    final password = _passwordController.text;

    if (username.isEmpty || password.isEmpty) {
      setState(() => _error = 'Username and password are required.');
      return;
    }

    setState(() {
      _busy = true;
      _error = null;
    });

    // SY/semester intentionally omitted — the server fills them from the
    // active settings.
    final ok = await widget.controller.login(
      username: username,
      password: password,
    );
    if (!mounted) return;

    if (ok) {
      // The root ListenableBuilder will rebuild and swap to the role shell.
      // Reset busy state so there's no stuck spinner if the rebuild is
      // delayed by a frame on web.
      if (mounted) setState(() => _busy = false);
      return;
    }

    setState(() {
      _busy = false;
      _error = widget.controller.error ?? 'Sign-in failed.';
    });
  }

  Future<void> _changeSchool() async {
    // Forgetting the school is a state change; the root flow then shows the
    // welcome screen on its own. See the note in welcome_screen.dart.
    await widget.controller.unpair();
  }

  void _forgotPassword() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ForgotPasswordScreen(controller: widget.controller),
      ),
    );
  }

  void _showDataPrivacy() =>
      LegalDialogs.showDataPrivacy(context, schoolName: _schoolName);
  void _showTermsOfUse() =>
      LegalDialogs.showTermsOfUse(context, schoolName: _schoolName);
  void _showAbout() =>
      LegalDialogs.showAbout(context, schoolName: _schoolName);

  /// Dynamic school name from the connected server's `/config` response.
  /// Falls back to [AppBrand.name] when the probe failed (offline cold
  /// start) so the login surface never shows a blank brand.
  String get _schoolName {
    final name = (widget.controller.config?.schoolName ?? '').trim();
    return name.isEmpty ? AppBrand.name : name;
  }

  @override
  Widget build(BuildContext context) {
    final config = widget.controller.config;

    return Scaffold(
      backgroundColor: AppInk.page,
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 32, 24, 32),
              child: ConstrainedBox(
                constraints: BoxConstraints(
                  minHeight: constraints.maxHeight - 64,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const SizedBox(height: 24),

                    // ── Logo ─────────────────────────────────────────
                    Center(child: _Logo(config: config)),
                    const SizedBox(height: 20),

                    // ── Brand name (dynamic from /config) ────────────
                    Center(
                      child: Text(
                        _schoolName,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: AppInk.heading,
                          height: 1.3,
                        ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Center(
                      child: Text(
                        AppBrand.tagline,
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppInk.muted,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                    const SizedBox(height: 32),

                    // ── Error banner ─────────────────────────────────
                    if ((_error ?? '').isNotEmpty) ...[
                      _ErrorBanner(message: _error!),
                      const SizedBox(height: 16),
                    ],

                    // ── Inputs ───────────────────────────────────────
                    AppInput(
                      controller: _usernameController,
                      label: 'Username',
                      hint: 'Student number or username',
                      textInputAction: TextInputAction.next,
                      prefixIcon: AppIcons.person_outline_rounded,
                      autofillHints: const ['username'],
                    ),
                    const SizedBox(height: 14),
                    AppInput(
                      controller: _passwordController,
                      label: 'Password',
                      hint: 'Enter your password',
                      obscureText: _obscurePassword,
                      textInputAction: TextInputAction.done,
                      prefixIcon: AppIcons.lock_outline_rounded,
                      autofillHints: const ['password'],
                      onSubmitted: (_) => _signIn(),
                      suffixIcon: GestureDetector(
                        onTap: () => setState(
                            () => _obscurePassword = !_obscurePassword),
                        child: Icon(
                          _obscurePassword
                              ? AppIcons.visibility_off
                              : AppIcons.visibility,
                          size: 20,
                          color: AppInk.muted,
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),

                    // ── Sign-in button ───────────────────────────────
                    AppButton(
                      label: 'Sign in',
                      fullWidth: true,
                      size: AppButtonSize.lg,
                      loading: _busy,
                      disabled: _busy,
                      onTap: _signIn,
                    ),
                    const SizedBox(height: 16),

                    // ── Register link (prominent, right after sign-in) ─
                    Center(
                      child: TextButton(
                        onPressed: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) =>
                                  RegisterScreen(controller: widget.controller),
                            ),
                          );
                        },
                        child: const Text.rich(
                          TextSpan(
                            text: 'No account? ',
                            style: TextStyle(
                              color: AppInk.muted,
                              fontSize: 14,
                            ),
                            children: [
                              TextSpan(
                                text: 'Create one',
                                style: TextStyle(
                                  color: AppInk.accent,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),

                    // ── Links ────────────────────────────────────────
                    Wrap(
                      alignment: WrapAlignment.center,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      spacing: 4,
                      children: [
                        TextButton(
                          onPressed: _forgotPassword,
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 6, vertical: 6),
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          ),
                          child: const Text(
                            'Forgot Password',
                            style: TextStyle(
                              color: AppInk.muted,
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                        ),
                        const Text('|',
                            style:
                                TextStyle(color: AppInk.rule, fontSize: 13)),
                        TextButton(
                          onPressed: _changeSchool,
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 6, vertical: 6),
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          ),
                          child: const Text(
                            'Switch School',
                            style: TextStyle(
                              color: AppInk.accent,
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // ── Legal footer (mirrors home_page.php legal-footer) ──
                    _LegalFooter(
                      onPrivacy: _showDataPrivacy,
                      onTerms: _showTermsOfUse,
                      onAbout: _showAbout,
                      copyrightName: _schoolName,
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

/// Three-link legal footer + copyright line, mirroring the
/// `legal-footer` block in `application/views/home_page.php`.
class _LegalFooter extends StatelessWidget {
  const _LegalFooter({
    required this.onPrivacy,
    required this.onTerms,
    required this.onAbout,
    required this.copyrightName,
  });

  final VoidCallback onPrivacy;
  final VoidCallback onTerms;
  final VoidCallback onAbout;
  final String copyrightName;

  @override
  Widget build(BuildContext context) {
    const linkStyle = TextStyle(
      fontSize: 12,
      fontWeight: FontWeight.w700,
      color: AppInk.muted,
    );
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Wrap(
          alignment: WrapAlignment.center,
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 4,
          children: [
            TextButton(
              onPressed: onPrivacy,
              style: TextButton.styleFrom(
                padding:
                    const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: const Text('Data Privacy', style: linkStyle),
            ),
            const Text('·', style: TextStyle(color: AppInk.rule, fontSize: 12)),
            TextButton(
              onPressed: onTerms,
              style: TextButton.styleFrom(
                padding:
                    const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: const Text('Terms of Use', style: linkStyle),
            ),
            const Text('·', style: TextStyle(color: AppInk.rule, fontSize: 12)),
            TextButton(
              onPressed: onAbout,
              style: TextButton.styleFrom(
                padding:
                    const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: const Text('About', style: linkStyle),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          '© ${DateTime.now().year} $copyrightName. All rights reserved.',
          style: const TextStyle(
            fontSize: 11,
            color: AppInk.muted,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}

/// School logo from the server config; falls back to the bundled app logo.
class _Logo extends StatelessWidget {
  const _Logo({this.config});
  final MobileConfig? config;

  @override
  Widget build(BuildContext context) {
    const size = 104.0;
    const outer = size + 28; // 132
    final url = (config?.loginLogoUrl ?? '').trim();

    final fallback = Image.asset(
      'assets/img/icon-logo.png',
      width: size,
      height: size,
      fit: BoxFit.contain,
    );

    return Container(
      width: outer,
      height: outer,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
        border: Border.all(color: AppInk.rule),
        boxShadow: [
          BoxShadow(
            color: AppTheme.midBlue.withValues(alpha: 0.06),
            blurRadius: 24,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: ClipOval(
        child: url.isEmpty
            ? fallback
            : Image.network(
                url,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => fallback,
              ),
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppInk.critical.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppInk.critical.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          const Icon(AppIcons.error_outline,
              size: 18, color: AppInk.critical),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: AppInk.critical,
                fontWeight: FontWeight.w600,
                height: 1.4,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
