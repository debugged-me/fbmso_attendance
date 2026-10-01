import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_brand.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/widgets/anim_helpers.dart';
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
      body: Stack(
        children: [
          const AppDotGrid(height: 320),
          SafeArea(
            child: LayoutBuilder(
              builder: (context, constraints) {
                return SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(24, 8, 24, 20),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(
                      minHeight: constraints.maxHeight - 28,
                    ),
                    child: IntrinsicHeight(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Align(
                            alignment: Alignment.centerLeft,
                            child: _SchoolSwitch(
                              name: _schoolName,
                              onTap: _changeSchool,
                            ),
                          ),
                          const Spacer(),
                          const SizedBox(height: 24),
                          FadeSlideIn(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                AppBrandMark(
                                  size: 64,
                                  logoUrl: config?.loginLogoUrl,
                                ),
                                const SizedBox(height: 28),
                                Text('Welcome back', style: AppType.title
                                    .copyWith(fontSize: 28)),
                                const SizedBox(height: 8),
                                Text(
                                  'Sign in with your portal account.',
                                  style: AppType.body
                                      .copyWith(color: AppInk.muted),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 28),
                          FadeSlideIn(
                            delay: const Duration(milliseconds: 80),
                            child: AutofillGroup(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  AppInput(
                                    controller: _usernameController,
                                    label: 'Username',
                                    hint: 'Student number or username',
                                    textInputAction: TextInputAction.next,
                                    prefixIcon: AppIcons.person_outline_rounded,
                                    autocorrect: false,
                                    autofillHints: const [
                                      AutofillHints.username
                                    ],
                                  ),
                                  const SizedBox(height: 16),
                                  AppInput(
                                    controller: _passwordController,
                                    label: 'Password',
                                    hint: 'Your password',
                                    obscureText: _obscurePassword,
                                    textInputAction: TextInputAction.done,
                                    prefixIcon: AppIcons.lock_outline_rounded,
                                    autocorrect: false,
                                    autofillHints: const [
                                      AutofillHints.password
                                    ],
                                    onSubmitted: (_) => _signIn(),
                                    suffixIcon: IconButton(
                                      tooltip: _obscurePassword
                                          ? 'Show password'
                                          : 'Hide password',
                                      onPressed: () => setState(() =>
                                          _obscurePassword = !_obscurePassword),
                                      icon: Icon(
                                        _obscurePassword
                                            ? AppIcons.visibility_outlined
                                            : AppIcons.visibility_off_outlined,
                                        size: 20,
                                        color: AppInk.muted,
                                      ),
                                    ),
                                  ),
                                  Align(
                                    alignment: Alignment.centerRight,
                                    child: TextButton(
                                      onPressed: _forgotPassword,
                                      style: TextButton.styleFrom(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 4, vertical: 12),
                                        textStyle: AppType.caption.copyWith(
                                          fontSize: 13.5,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                      child: const Text('Forgot password?'),
                                    ),
                                  ),
                                  AnimatedSize(
                                    duration: AppMotion.base,
                                    curve: AppMotion.ease,
                                    child: (_error ?? '').isEmpty
                                        ? const SizedBox(
                                            width: double.infinity)
                                        : Padding(
                                            padding: const EdgeInsets.only(
                                                bottom: 16),
                                            child:
                                                AppNotice(message: _error!),
                                          ),
                                  ),
                                  AppButton(
                                    label: 'Sign in',
                                    fullWidth: true,
                                    size: AppButtonSize.lg,
                                    loading: _busy,
                                    onTap: _signIn,
                                  ),
                                  const SizedBox(height: 12),
                                  AppButton(
                                    label: 'Create an account',
                                    style: AppButtonStyle.outline,
                                    fullWidth: true,
                                    size: AppButtonSize.lg,
                                    onTap: _busy ? null : _register,
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const Spacer(),
                          const SizedBox(height: 28),
                          _LegalFooter(
                            onPrivacy: _showDataPrivacy,
                            onTerms: _showTermsOfUse,
                            onAbout: _showAbout,
                            copyrightName: _schoolName,
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  void _register() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => RegisterScreen(controller: widget.controller),
      ),
    );
  }
}

/// The connected school as a tappable chip: switching schools is rare, so
/// it lives up here instead of competing with the sign-in button.
class _SchoolSwitch extends StatelessWidget {
  const _SchoolSwitch({required this.name, required this.onTap});

  final String name;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      shape: const StadiumBorder(side: BorderSide(color: AppInk.rule)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(AppIcons.school_outlined,
                  size: 16, color: AppInk.accent),
              const SizedBox(width: 8),
              // Wraps to a second line: real school names run past 220px.
              Flexible(
                child: Text(
                  name,
                  style: AppType.caption.copyWith(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppInk.heading,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Text(
                'Switch',
                style: AppType.caption.copyWith(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppInk.accent,
                ),
              ),
            ],
          ),
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
    Widget link(String label, VoidCallback onTap) => TextButton(
          onPressed: onTap,
          style: TextButton.styleFrom(
            foregroundColor: AppInk.muted,
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            minimumSize: const Size(0, 32),
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            textStyle: AppType.caption.copyWith(fontWeight: FontWeight.w600),
          ),
          child: Text(label),
        );
    const dot = Text('·', style: TextStyle(color: AppInk.faint));
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Wrap(
          alignment: WrapAlignment.center,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            link('Privacy', onPrivacy),
            dot,
            link('Terms', onTerms),
            dot,
            link('About', onAbout),
          ],
        ),
        const SizedBox(height: 2),
        Text(
          '© ${DateTime.now().year} $copyrightName',
          textAlign: TextAlign.center,
          style: AppType.caption.copyWith(fontSize: 12, color: AppInk.faint),
        ),
      ],
    );
  }
}
