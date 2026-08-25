import 'package:flutter/material.dart';

import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';
import '../domain/mobile_config.dart';
import 'auth_controller.dart';
import 'login_screen.dart';

/// First-run / unpaired screen: the user types their school's URL.
///
/// "One app, many clients" — there is no hardcoded host. The typed URL is
/// saved and a `/config` probe shows the school name + logo so the user can
/// confirm they reached the right server before entering credentials.
class WelcomeScreen extends StatefulWidget {
  const WelcomeScreen({super.key, required this.controller});

  final AuthController controller;

  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen> {
  late final TextEditingController _urlController;
  bool _probing = false;

  @override
  void initState() {
    super.initState();
    _urlController =
        TextEditingController(text: widget.controller.baseUrl);
  }

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _continue() async {
    final raw = _urlController.text.trim();
    if (raw.isEmpty) return;

    setState(() => _probing = true);
    await widget.controller.loadConfig(raw);
    if (!mounted) return;
    setState(() => _probing = false);

    if (widget.controller.error != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(widget.controller.error!)),
      );
      return;
    }

    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => LoginScreen(controller: widget.controller),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final config = widget.controller.config;
    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 48),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Icon(AppIcons.school_rounded,
                  size: 64, color: AppTheme.midBlue),
              const SizedBox(height: 16),
              Text(
                'FBMSO Attendance',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 8),
              Text(
                'Enter your school\'s URL to continue.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 32),
              TextField(
                controller: _urlController,
                keyboardType: TextInputType.url,
                autocorrect: false,
                enableSuggestions: false,
                textInputAction: TextInputAction.go,
                decoration: const InputDecoration(
                  labelText: 'School URL',
                  hintText: 'fbmso.srmsportal.com',
                  prefixIcon: Icon(Icons.link),
                  border: OutlineInputBorder(),
                ),
                onSubmitted: (_) => _continue(),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _probing ? null : _continue,
                child: _probing
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white),
                      )
                    : const Text('Continue'),
              ),
              if (config != null && config.ok) ...[
                const SizedBox(height: 32),
                _SchoolPreview(config: config),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _SchoolPreview extends StatelessWidget {
  const _SchoolPreview({required this.config});
  final MobileConfig config;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(config.schoolName,
                style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 4),
            Text('SY ${config.activeSy} • ${config.activeSem}',
                style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}
