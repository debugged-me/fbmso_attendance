import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../theme/app_icons.dart';
import 'external_link_fallback.dart';

/// Dedicated in-app privacy policy screen.
///
/// Loads the school's published `/dataprivacy` page inside a WebView so the
/// mobile app always mirrors the latest published policy without shipping a
/// new build.
class PrivacyPolicyPage extends StatefulWidget {
  const PrivacyPolicyPage({super.key, required this.url, this.title = 'Privacy Policy'});

  final String url;
  final String title;

  @override
  State<PrivacyPolicyPage> createState() => _PrivacyPolicyPageState();
}

class _PrivacyPolicyPageState extends State<PrivacyPolicyPage> {
  static const _bg = Color(0xFFFFFFFF);
  static const _surface = Color(0xFFF8FAFC);
  static const _textDark = Color(0xFF2E3A59);
  static const _textMuted = Color(0xFF64748B);
  static const _border = Color(0xFFE5E7EB);
  static const _brand = Color(0xFF0F5C99);

  // Null on Flutter web — `webview_flutter` has no platform implementation
  // there, so constructing a controller throws.
  WebViewController? _controller;
  bool _loading = true;
  int _progress = 0;
  String? _error;

  @override
  void initState() {
    super.initState();

    if (kIsWeb) {
      _loading = false;
      return;
    }

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(_bg)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (!mounted) return;
            setState(() {
              _loading = true;
              _error = null;
            });
          },
          onProgress: (value) {
            if (!mounted) return;
            setState(() => _progress = value);
          },
          onPageFinished: (_) {
            if (!mounted) return;
            setState(() {
              _loading = false;
              _progress = 100;
            });
          },
          onWebResourceError: (error) {
            if (!mounted) return;
            if (error.isForMainFrame == false) return;
            setState(() {
              _loading = false;
              _error = error.description.trim().isEmpty
                  ? 'Unable to load the privacy policy.'
                  : error.description.trim();
            });
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  Future<void> _reload() async {
    setState(() {
      _loading = true;
      _error = null;
      _progress = 0;
    });
    await _controller?.reload();
  }

  Future<bool> _handleBack() async {
    final controller = _controller;
    if (controller != null && await controller.canGoBack()) {
      await controller.goBack();
      return false;
    }
    return true;
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    if (controller == null) {
      return ExternalLinkFallback(
        title: widget.title,
        uri: Uri.parse(widget.url),
        message: 'The privacy policy opens in a browser tab when you are '
            'using the web version of the app.',
      );
    }

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        final navigator = Navigator.of(context);
        final canClose = await _handleBack();
        if (canClose) {
          navigator.pop();
        }
      },
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.dark.copyWith(
          statusBarColor: Colors.transparent,
          systemNavigationBarColor: _bg,
          systemNavigationBarIconBrightness: Brightness.dark,
        ),
        child: Scaffold(
          backgroundColor: _bg,
          appBar: PreferredSize(
            preferredSize: const Size.fromHeight(56),
            child: _buildAppBar(),
          ),
          body: Column(
            children: [
              _buildProgressBar(),
              Expanded(
                child: Stack(
                  children: [
                    WebViewWidget(controller: controller),
                    if (_error != null) _buildErrorState(),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      backgroundColor: _bg,
      surfaceTintColor: _bg,
      elevation: 0,
      scrolledUnderElevation: 0,
      foregroundColor: _textDark,
      titleSpacing: 0,
      leading: IconButton(
        tooltip: 'Back',
        icon: const Icon(AppIcons.arrow_back_rounded),
        onPressed: () async {
          final navigator = Navigator.of(context);
          final canClose = await _handleBack();
          if (canClose) {
            navigator.pop();
          }
        },
      ),
      title: Row(
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: _brand.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(
              AppIcons.security_rounded,
              size: 16,
              color: _brand,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              widget.title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: _textDark,
              ),
            ),
          ),
        ],
      ),
      actions: [
        IconButton(
          tooltip: 'Reload',
          icon: const Icon(AppIcons.refresh_rounded),
          onPressed: _reload,
        ),
        const SizedBox(width: 4),
      ],
      bottom: PreferredSize(
        preferredSize: const Size.fromHeight(1),
        child: Container(height: 1, color: _border),
      ),
    );
  }

  Widget _buildProgressBar() {
    final visible = _loading && _error == null;
    return SizedBox(
      height: 2,
      child: AnimatedOpacity(
        duration: const Duration(milliseconds: 180),
        opacity: visible ? 1 : 0,
        child: LinearProgressIndicator(
          value: _progress > 0 ? _progress / 100 : null,
          minHeight: 2,
          backgroundColor: _border,
          valueColor: const AlwaysStoppedAnimation<Color>(_brand),
        ),
      ),
    );
  }

  Widget _buildErrorState() {
    return Container(
      color: _bg,
      alignment: Alignment.center,
      padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 360),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: _surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: _border),
              ),
              child: const Icon(
                AppIcons.cloud_off_rounded,
                size: 26,
                color: _textMuted,
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Couldn’t load privacy policy',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: _textDark,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              _error!,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                height: 1.5,
                color: _textMuted,
              ),
            ),
            const SizedBox(height: 18),
            FilledButton.icon(
              onPressed: _reload,
              style: FilledButton.styleFrom(
                backgroundColor: _brand,
                foregroundColor: Colors.white,
                minimumSize: const Size(0, 44),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              icon: const Icon(AppIcons.refresh_rounded, size: 18),
              label: const Text('Try again'),
            ),
          ],
        ),
      ),
    );
  }
}
