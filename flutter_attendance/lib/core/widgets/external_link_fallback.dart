import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../theme/app_icons.dart';

/// Stands in for an embedded WebView on platforms where `webview_flutter` has
/// no registered implementation (Flutter web).
///
/// On web `WebViewPlatform.instance` is null, so building a `WebViewController`
/// trips an assertion and the whole route dies. Every screen that embeds a
/// WebView guards on [kIsWeb] and renders this instead, which opens the same
/// page in a new browser tab.
class ExternalLinkFallback extends StatelessWidget {
  const ExternalLinkFallback({
    super.key,
    required this.title,
    required this.uri,
    this.message,
  });

  final String title;
  final Uri uri;
  final String? message;

  static const _surface = Color(0xFFF8FAFC);
  static const _textDark = Color(0xFF2E3A59);
  static const _textMuted = Color(0xFF475569);

  Future<void> _open(BuildContext context) async {
    final opened = await launchUrl(
      uri,
      mode: LaunchMode.externalApplication,
      webOnlyWindowName: '_blank',
    );

    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Unable to open this page.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _surface,
      appBar: AppBar(title: Text(title)),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x14000000),
                    blurRadius: 18,
                    offset: Offset(0, 10),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      AppIcons.open_in_new,
                      size: 34,
                      color: _textMuted,
                    ),
                    const SizedBox(height: 14),
                    Text(
                      title,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w700,
                        color: _textDark,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      message ??
                          'This page opens in a browser tab when you are '
                              'using the web version of the app.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 14,
                        color: _textMuted,
                        height: 1.45,
                      ),
                    ),
                    const SizedBox(height: 18),
                    FilledButton.icon(
                      onPressed: () => _open(context),
                      icon: const Icon(AppIcons.open_in_new),
                      label: const Text('Open in Browser'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
