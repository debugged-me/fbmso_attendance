import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';
import '../domain/mobile_config.dart';
import 'auth_controller.dart';
import 'forgot_password_screen.dart';
import 'welcome_screen.dart';

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

    setState(() {
      _busy = false;
      _error = ok ? null : (widget.controller.error ?? 'Sign-in failed.');
    });
    // On success the root AuthFlow rebuilds and swaps to the role shell.
  }

  void _changeSchool() {
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(
        builder: (_) => WelcomeScreen(controller: widget.controller),
      ),
      (_) => false,
    );
  }

  void _forgotPassword() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ForgotPasswordScreen(controller: widget.controller),
      ),
    );
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

                    // ── School name ──────────────────────────────────
                    Center(
                      child: Text(
                        config?.schoolName.isNotEmpty == true
                            ? config!.schoolName
                            : 'FBMSO Attendance',
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
                        'Sign in with your portal account',
                        style: TextStyle(
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
                    const SizedBox(height: 20),

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

                    // ── Active term footnote ─────────────────────────
                    if (config != null &&
                        (config.activeSy.isNotEmpty ||
                            config.activeSem.isNotEmpty))
                      Center(
                        child: Text(
                          '${config.activeSem} • ${config.activeSy}',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppInk.muted,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
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

/// School logo from the server config; falls back to the bundled app logo.
class _Logo extends StatelessWidget {
  const _Logo({this.config});
  final MobileConfig? config;

  @override
  Widget build(BuildContext context) {
    const size = 104.0;
    final url = (config?.loginLogoUrl ?? '').trim();

    final fallback = Image.asset(
      'assets/img/icon-logo.png',
      width: size,
      height: size,
      fit: BoxFit.contain,
    );

    return Container(
      width: size + 28,
      height: size + 28,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: AppInk.rule),
        boxShadow: [
          BoxShadow(
            color: AppTheme.midBlue.withValues(alpha: 0.06),
            blurRadius: 24,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: url.isEmpty
          ? fallback
          : Image.network(
              url,
              fit: BoxFit.contain,
              errorBuilder: (_, __, ___) => fallback,
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
