import 'package:flutter/material.dart';

import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';
import 'auth_controller.dart';
import 'forgot_password_screen.dart';
import 'welcome_screen.dart';

/// Credential entry. The base URL was already chosen on the welcome screen
/// (and is shown as a tappable chip so the user can switch schools).
///
/// The same username + password that works on the web works here — the
/// mobile `/api/mobile/auth/login` endpoint reuses `Login_model::validate()`.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.controller});

  final AuthController controller;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  late final TextEditingController _usernameController;
  late final TextEditingController _passwordController;
  late final TextEditingController _syController;
  late final TextEditingController _semesterController;

  bool _obscurePassword = true;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _usernameController = TextEditingController();
    _passwordController = TextEditingController();
    _syController = TextEditingController(
      text: widget.controller.config?.activeSy ?? '',
    );
    _semesterController = TextEditingController(
      text: widget.controller.config?.activeSem ?? '',
    );
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    _syController.dispose();
    _semesterController.dispose();
    super.dispose();
  }

  Future<void> _signIn() async {
    final username = _usernameController.text.trim();
    final password = _passwordController.text;

    if (username.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Username and password are required.')),
      );
      return;
    }

    setState(() => _busy = true);
    final ok = await widget.controller.login(
      username: username,
      password: password,
      sy: _syController.text.trim(),
      semester: _semesterController.text.trim(),
    );
    if (!mounted) return;
    setState(() => _busy = false);

    if (!ok && widget.controller.error != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(widget.controller.error!)),
      );
    }
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

  @override
  Widget build(BuildContext context) {
    final config = widget.controller.config;
    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // School switcher chip
              Center(
                child: ActionChip(
                  avatar: Icon(AppIcons.school_outlined,
                      size: 18, color: AppTheme.midBlue),
                  label: Text(config?.schoolName ?? widget.controller.baseUrl),
                  onPressed: _changeSchool,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Sign in',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 4),
              Text(
                'Use the same credentials you use on the web.',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 24),
              TextField(
                controller: _usernameController,
                autocorrect: false,
                enableSuggestions: false,
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Username / ID Number',
                  prefixIcon: Icon(Icons.person_outline),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                textInputAction: TextInputAction.go,
                decoration: InputDecoration(
                  labelText: 'Password',
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    icon: Icon(_obscurePassword
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined),
                    onPressed: () => setState(
                        () => _obscurePassword = !_obscurePassword),
                  ),
                  border: const OutlineInputBorder(),
                ),
                onSubmitted: (_) => _signIn(),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _syController,
                      decoration: const InputDecoration(
                        labelText: 'SY',
                        isDense: true,
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _semesterController,
                      decoration: const InputDecoration(
                        labelText: 'Semester',
                        isDense: true,
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: _busy ? null : _signIn,
                child: _busy
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white),
                      )
                    : const Text('Sign in'),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) =>
                          ForgotPasswordScreen(controller: widget.controller),
                    ),
                  );
                },
                child: const Text('Forgot password?'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
