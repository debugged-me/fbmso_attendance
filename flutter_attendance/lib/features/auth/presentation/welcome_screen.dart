import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_brand.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/widgets/anim_helpers.dart';
import 'auth_controller.dart';

/// First-run / unpaired screen: the user types their school's URL.
///
/// There is no hardcoded host. The typed URL is saved and a `/config` probe
/// confirms the server before login.
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
      setState(() => _error = 'Enter your school portal address.');
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
      body: Stack(
        children: [
          const AppDotGrid(height: 360),
          SafeArea(
            child: LayoutBuilder(
              builder: (context, constraints) {
                return SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(24, 12, 24, 24),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(
                      minHeight: constraints.maxHeight - 36,
                    ),
                    child: IntrinsicHeight(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Spacer(flex: 2),
                          const FadeSlideIn(
                            child: Align(
                              alignment: Alignment.centerLeft,
                              child: AppBrandMark(size: 64),
                            ),
                          ),
                          const SizedBox(height: 28),
                          FadeSlideIn(
                            delay: const Duration(milliseconds: 60),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Connect to your school',
                                  style: AppType.title.copyWith(fontSize: 28),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'Enter the portal address your school gave '
                                  'you. You only need to do this once.',
                                  style: AppType.body
                                      .copyWith(color: AppInk.muted),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 28),
                          FadeSlideIn(
                            delay: const Duration(milliseconds: 120),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                AppInput(
                                  controller: _urlController,
                                  label: 'Portal address',
                                  hint: 'portal.yourschool.edu',
                                  keyboardType: TextInputType.url,
                                  textInputAction: TextInputAction.go,
                                  prefixIcon: AppIcons.language,
                                  autocorrect: false,
                                  autofillHints: const [AutofillHints.url],
                                  onSubmitted: (_) => _continue(),
                                  onChanged: (_) {
                                    if (_error != null) {
                                      setState(() => _error = null);
                                    }
                                  },
                                ),
                                AnimatedSize(
                                  duration: AppMotion.base,
                                  curve: AppMotion.ease,
                                  child: (_error ?? '').isEmpty
                                      ? const SizedBox(width: double.infinity)
                                      : Padding(
                                          padding:
                                              const EdgeInsets.only(top: 14),
                                          child: AppNotice(message: _error!),
                                        ),
                                ),
                                const SizedBox(height: 20),
                                AppButton(
                                  label: _probing ? 'Connecting…' : 'Continue',
                                  trailingIcon: _probing
                                      ? null
                                      : AppIcons.arrow_forward_rounded,
                                  fullWidth: true,
                                  size: AppButtonSize.lg,
                                  loading: _probing,
                                  onTap: _continue,
                                ),
                              ],
                            ),
                          ),
                          const Spacer(flex: 3),
                          Text(
                            AppBrand.name,
                            textAlign: TextAlign.center,
                            style: AppType.caption,
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
}
