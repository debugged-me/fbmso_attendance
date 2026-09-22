import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import 'auth_controller.dart';

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

    // No navigation here on purpose. loadConfig() set the base URL + config
    // and notified, so the root flow in app.dart swaps this screen for the
    // login screen. Pushing/replacing instead would tear the root route out
    // of the tree, detaching it from the AuthController — after which a
    // successful login would notify with nobody listening and the screen
    // would never change.
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppInk.page,
      body: SafeArea(
        top: false,
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              child: ConstrainedBox(
                constraints: BoxConstraints(
                  minHeight: constraints.maxHeight,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // ── Gradient hero band ────────────────────────────
                    Container(
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [
                            Color(0xFF14294B),
                            Color(0xFF1E3FA0),
                            Color(0xFF4A7CF7),
                          ],
                        ),
                        borderRadius: BorderRadius.vertical(
                            bottom: Radius.circular(32)),
                      ),
                      child: SafeArea(
                        bottom: false,
                        child: Padding(
                          padding:
                              const EdgeInsets.fromLTRB(24, 32, 24, 40),
                          child: Column(
                            children: [
                              const SizedBox(height: 16),
                              Container(
                                width: 120,
                                height: 120,
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  shape: BoxShape.circle,
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black
                                          .withValues(alpha: 0.15),
                                      blurRadius: 28,
                                      offset: const Offset(0, 10),
                                    ),
                                  ],
                                ),
                                child: ClipOval(
                                  child: Image.asset(
                                    'assets/img/icon-logo.png',
                                    fit: BoxFit.cover,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 22),
                              const Text(
                                'Welcome',
                                style: TextStyle(
                                  fontSize: 26,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Connect to your school portal to get started.',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 14,
                                  color: Colors.white
                                      .withValues(alpha: 0.8),
                                  fontWeight: FontWeight.w500,
                                  height: 1.4,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),

                    Padding(
                      padding: const EdgeInsets.fromLTRB(24, 28, 24, 32),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [

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
