import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import 'auth_controller.dart';
import '../../../core/theme/app_icons.dart';

/// Email-only password recovery. The server sends a temporary password to
/// the registered address; passwords can no longer be reset directly from
/// identifying information entered in the app.
class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key, required this.controller});

  final AuthController controller;

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  late final TextEditingController _emailController;
  bool _busy = false;
  String? _error;
  String? _success;

  @override
  void initState() {
    super.initState();
    _emailController = TextEditingController();
  }

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final email = _emailController.text.trim();
    if (email.isEmpty || !email.contains('@')) {
      setState(() => _error = 'Please enter a valid email address.');
      return;
    }

    setState(() {
      _busy = true;
      _error = null;
      _success = null;
    });

    final error = await widget.controller.forgotPassword(email);
    if (!mounted) return;

    setState(() {
      _busy = false;
      if (error != null) {
        _error = error;
      } else {
        _success = 'If that email exists, a temporary password has been sent.';
      }
    });

    if (error == null) {
      Future.delayed(const Duration(seconds: 2), () {
        if (mounted) Navigator.of(context).pop();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppInk.page,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Align(
                alignment: Alignment.centerLeft,
                child: AppBackButton(),
              ),
              const SizedBox(height: 28),
              const Align(
                alignment: Alignment.centerLeft,
                child: AppIconBox(
                  icon: AppIcons.lock_key,
                  size: 52,
                  iconSize: 26,
                ),
              ),
              const SizedBox(height: 20),
              Text('Reset your password',
                  style: AppType.title.copyWith(fontSize: 28)),
              const SizedBox(height: 8),
              Text(
                'Enter the email on your account. We will send you a '
                'temporary password to sign in with.',
                style: AppType.body.copyWith(color: AppInk.muted),
              ),
              const SizedBox(height: 28),
              AppInput(
                controller: _emailController,
                label: 'Email address',
                hint: 'you@example.com',
                prefixIcon: AppIcons.email_outlined,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.done,
                autocorrect: false,
                autofillHints: const [AutofillHints.email],
                onSubmitted: (_) => _submit(),
              ),
              if (_error != null) ...[
                const SizedBox(height: 16),
                AppNotice(message: _error!),
              ],
              if (_success != null) ...[
                const SizedBox(height: 16),
                AppNotice(message: _success!, tone: AppInk.positive),
              ],
              const SizedBox(height: 24),
              AppButton(
                label: 'Send temporary password',
                fullWidth: true,
                size: AppButtonSize.lg,
                loading: _busy,
                onTap: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
