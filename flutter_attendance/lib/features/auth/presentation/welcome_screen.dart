import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';
import 'auth_controller.dart';
import 'login_screen.dart';

/// First-run / unpaired screen: the user types their school's URL.
///
/// "One app, many clients" — there is no hardcoded host. The typed URL is
/// saved and a `/config` probe confirms the server before login.
class WelcomeScreen extends StatefulWidget {
  const WelcomeScreen({super.key, required this.controller});

  final AuthController controller;

  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen> {
  late final TextEditingController _urlController;
  bool _probing = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _urlController = TextEditingController(text: widget.controller.baseUrl);
  }

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _continue() async {
    FocusScope.of(context).unfocus();
    HapticFeedback.mediumImpact();

    final raw = _urlController.text.trim();
    if (raw.isEmpty) {
      setState(() => _error = 'Please enter your school portal URL.');
      return;
    }

    setState(() {
      _probing = true;
      _error = null;
    });
    await widget.controller.loadConfig(raw);
    if (!mounted) return;
    setState(() => _probing = false);

    if (widget.controller.error != null) {
      setState(() => _error = widget.controller.error);
      return;
    }

    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => LoginScreen(controller: widget.controller),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppInk.page,
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 40, 24, 32),
              child: ConstrainedBox(
                constraints: BoxConstraints(
                  minHeight: constraints.maxHeight - 72,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const SizedBox(height: 32),

                    // ── App logo ─────────────────────────────────────
                    Center(
                      child: Container(
                        width: 132,
                        height: 132,
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(32),
                          border: Border.all(color: AppInk.rule),
                          boxShadow: [
                            BoxShadow(
                              color: AppTheme.midBlue.withValues(alpha: 0.08),
                              blurRadius: 28,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        child: Image.asset(
                          'assets/img/icon-logo.png',
                          fit: BoxFit.contain,
                        ),
                      ),
                    ),
                    const SizedBox(height: 28),

                    // ── Title ────────────────────────────────────────
                    const Center(
                      child: Text(
                        'Welcome',
                        style: TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w800,
                          color: AppInk.heading,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Center(
                      child: Text(
                        'Connect to your school portal to get started.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 14,
                          color: AppInk.muted,
                          fontWeight: FontWeight.w500,
                          height: 1.4,
                        ),
                      ),
                    ),
                    const SizedBox(height: 36),

                    // ── Error banner ─────────────────────────────────
                    if ((_error ?? '').isNotEmpty) ...[
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 14, vertical: 12),
                        decoration: BoxDecoration(
                          color: AppInk.critical.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                              color:
                                  AppInk.critical.withValues(alpha: 0.25)),
                        ),
                        child: Row(
                          children: [
                            const Icon(AppIcons.error_outline,
                                size: 18, color: AppInk.critical),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                _error!,
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
                      ),
                      const SizedBox(height: 16),
                    ],

                    // ── URL input ────────────────────────────────────
                    AppInput(
                      controller: _urlController,
                      label: 'School Portal URL',
                      hint: 'e.g. fbmso.srmsportal.com',
                      keyboardType: TextInputType.url,
                      textInputAction: TextInputAction.go,
                      prefixIcon: AppIcons.link_rounded,
                      onSubmitted: (_) => _continue(),
                    ),
                    const SizedBox(height: 20),

                    // ── Connect button ───────────────────────────────
                    AppButton(
                      label: 'Connect',
                      fullWidth: true,
                      size: AppButtonSize.lg,
                      loading: _probing,
                      disabled: _probing,
                      onTap: _continue,
                    ),

                    const SizedBox(height: 32),
                    const Center(
                      child: Text(
                        'One app, many schools — ask your school for its portal address.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 12,
                          color: AppInk.muted,
                          height: 1.5,
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
