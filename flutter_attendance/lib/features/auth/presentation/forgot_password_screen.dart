import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import 'auth_controller.dart';

/// Forgot password screen — mirrors the web "Reset password" modal.
/// Two modes:
/// 1. Email mode: enter email → server sends temporary password.
/// 2. Manual mode: enter email + username/student ID + new password →
///    server resets password directly (no email needed).
/// A toggle link switches between modes, just like the web.
class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key, required this.controller});

  final AuthController controller;

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  late final TextEditingController _emailController;
  late final TextEditingController _identifierController;
  late final TextEditingController _newPasswordController;
  late final TextEditingController _confirmPasswordController;

  bool _manualMode = false;
  bool _obscureNew = true;
  bool _obscureConfirm = true;
  bool _busy = false;
  String? _error;
  String? _success;

  @override
  void initState() {
    super.initState();
    _emailController = TextEditingController();
    _identifierController = TextEditingController();
    _newPasswordController = TextEditingController();
    _confirmPasswordController = TextEditingController();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _identifierController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final email = _emailController.text.trim();
    if (email.isEmpty) {
      setState(() => _error = 'Please enter a valid email address.');
      return;
    }

    setState(() {
      _busy = true;
      _error = null;
      _success = null;
    });

    String? error;
    if (_manualMode) {
      final identifier = _identifierController.text.trim();
      final newPass = _newPasswordController.text;
      final confirmPass = _confirmPasswordController.text;

      if (identifier.isEmpty) {
        setState(() {
          _busy = false;
          _error = 'Please enter your username or student ID.';
        });
        return;
      }
      if (newPass.isEmpty) {
        setState(() {
          _busy = false;
          _error = 'Please enter a new password.';
        });
        return;
      }
      if (newPass.length < 8) {
        setState(() {
          _busy = false;
          _error = 'Password must be at least 8 characters.';
        });
        return;
      }
      if (newPass != confirmPass) {
        setState(() {
          _busy = false;
          _error = 'Passwords do not match.';
        });
        return;
      }

      error = await widget.controller.forgotPasswordManual(
        email: email,
        identifier: identifier,
        newPassword: newPass,
        confirmPassword: confirmPass,
      );
    } else {
      error = await widget.controller.forgotPassword(email);
    }

    if (!mounted) return;
    setState(() => _busy = false);

    if (error != null) {
      setState(() => _error = error);
    } else {
      setState(() {
        _success = _manualMode
            ? 'Password updated. You can sign in now.'
            : 'If that email exists, a temporary password has been sent.';
      });
      Future.delayed(const Duration(seconds: 2), () {
        if (mounted) Navigator.of(context).pop();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppInk.page,
      appBar: AppBar(
        title: const Text('Reset Password'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 8, 24, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // ── Header ─────────────────────────────────────────────
              const Text(
                'Reset password',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.w800,
                  color: AppInk.heading,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _manualMode
                    ? 'Enter your email, username/student ID, and a new password to reset directly.'
                    : 'Enter your registered email to receive a temporary password. '
                      'Or switch to manual mode below to set a new password yourself.',
                style: const TextStyle(
                  fontSize: 13,
                  color: AppInk.muted,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 24),

              // ── Error / Success ────────────────────────────────────
              if (_error != null) ...[
                _Banner(message: _error!, color: AppInk.critical),
                const SizedBox(height: 16),
              ],
              if (_success != null) ...[
                _Banner(message: _success!, color: AppInk.positive),
                const SizedBox(height: 16),
              ],

              // ── Email ──────────────────────────────────────────────
              AppInput(
                controller: _emailController,
                label: 'Email address',
                hint: 'Enter Email',
                prefixIcon: Icons.email_outlined,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.next,
                autofillHints: const ['email'],
              ),
              const SizedBox(height: 14),

              // ── Manual mode fields ─────────────────────────────────
              if (_manualMode) ...[
                AppInput(
                  controller: _identifierController,
                  label: 'Username / Student ID',
                  hint: 'Enter Username or Student ID',
                  prefixIcon: Icons.badge_outlined,
                  textInputAction: TextInputAction.next,
                ),
                const SizedBox(height: 14),
                AppInput(
                  controller: _newPasswordController,
                  label: 'New password',
                  hint: 'At least 8 characters',
                  prefixIcon: Icons.lock_outline_rounded,
                  obscureText: _obscureNew,
                  textInputAction: TextInputAction.next,
                  autofillHints: const ['new-password'],
                  suffixIcon: GestureDetector(
                    onTap: () => setState(() => _obscureNew = !_obscureNew),
                    child: Icon(
                      _obscureNew ? Icons.visibility_off : Icons.visibility,
                      size: 20,
                      color: AppInk.muted,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                AppInput(
                  controller: _confirmPasswordController,
                  label: 'Confirm password',
                  hint: 'Repeat your new password',
                  prefixIcon: Icons.lock_outline_rounded,
                  obscureText: _obscureConfirm,
                  textInputAction: TextInputAction.done,
                  autofillHints: const ['new-password'],
                  onSubmitted: (_) => _submit(),
                  suffixIcon: GestureDetector(
                    onTap: () =>
                        setState(() => _obscureConfirm = !_obscureConfirm),
                    child: Icon(
                      _obscureConfirm ? Icons.visibility_off : Icons.visibility,
                      size: 20,
                      color: AppInk.muted,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
              ],

              // ── Toggle mode ────────────────────────────────────────
              Center(
                child: TextButton(
                  onPressed: () {
                    setState(() {
                      _manualMode = !_manualMode;
                      _error = null;
                      _success = null;
                    });
                  },
                  child: Text(
                    _manualMode
                        ? 'Send temporary password instead'
                        : 'Set password manually instead',
                    style: const TextStyle(
                      color: AppInk.accent,
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // ── Submit ─────────────────────────────────────────────
              AppButton(
                label: _manualMode ? 'Update password' : 'Send temporary password',
                fullWidth: true,
                size: AppButtonSize.lg,
                loading: _busy,
                disabled: _busy,
                onTap: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({required this.message, required this.color});
  final String message;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Icon(
            color == AppInk.positive
                ? Icons.check_circle_rounded
                : Icons.error_rounded,
            size: 18,
            color: color,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: color,
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
