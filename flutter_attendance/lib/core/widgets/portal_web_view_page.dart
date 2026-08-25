import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import '../theme/app_icons.dart';
import 'external_link_fallback.dart';
import 'package:webview_flutter/webview_flutter.dart';

class PortalWebViewPage extends StatefulWidget {
  const PortalWebViewPage._({
    super.key,
    required this.title,
    required this.initialUri,
    this.headers = const <String, String>{},
  });

  factory PortalWebViewPage.url({
    Key? key,
    required String title,
    required String url,
  }) {
    return PortalWebViewPage._(
      key: key,
      title: title,
      initialUri: Uri.parse(url),
    );
  }

  factory PortalWebViewPage.bridge({
    Key? key,
    required String title,
    required String baseUrl,
    required String token,
    required String targetPath,
    String? sy,
    String? semester,
  }) {
    final normalizedBaseUrl = baseUrl.trim().replaceFirst(RegExp(r'/+$'), '');
    final bridgeUri = Uri.parse('$normalizedBaseUrl/api/mobile/auth/bridge')
        .replace(
          queryParameters: {
            'target': targetPath,
            if ((sy ?? '').trim().isNotEmpty) 'sy': sy!.trim(),
            if ((semester ?? '').trim().isNotEmpty)
              'semester': semester!.trim(),
          },
        );

    return PortalWebViewPage._(
      key: key,
      title: title,
      initialUri: bridgeUri,
      headers: <String, String>{
        'Authorization': 'Bearer $token',
      },
    );
  }

  final String title;
  final Uri initialUri;
  final Map<String, String> headers;

  @override
  State<PortalWebViewPage> createState() => _PortalWebViewPageState();
}

class _PortalWebViewPageState extends State<PortalWebViewPage> {
  // Null on Flutter web, where `webview_flutter` has no platform
  // implementation and constructing a controller throws.
  WebViewController? _controller;
  bool _loading = true;
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
      ..setBackgroundColor(const Color(0xFFF8FAFC))
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (!mounted) {
              return;
            }
            setState(() {
              _loading = true;
              _error = null;
            });
          },
          onPageFinished: (_) {
            if (!mounted) {
              return;
            }
            setState(() => _loading = false);
          },
          onWebResourceError: (error) {
            if (!mounted) {
              return;
            }
            setState(() {
              _loading = false;
              _error = error.description.trim().isEmpty
                  ? 'Unable to load this page right now.'
                  : error.description.trim();
            });
          },
        ),
      )
      ..loadRequest(widget.initialUri, headers: widget.headers);
  }

  Future<void> _reload() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    await _controller?.reload();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    if (controller == null) {
      return ExternalLinkFallback(
        title: widget.title,
        uri: widget.initialUri,
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _reload,
            icon: const Icon(AppIcons.refresh_rounded),
          ),
        ],
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: controller),
          if (_loading) const LinearProgressIndicator(minHeight: 2),
          if ((_error ?? '').trim().isNotEmpty)
            Align(
              alignment: Alignment.center,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
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
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          AppIcons.cloud_off_rounded,
                          size: 34,
                          color: Color(0xFF475569),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 14,
                            color: Color(0xFF475569),
                            height: 1.45,
                          ),
                        ),
                        const SizedBox(height: 14),
                        FilledButton.icon(
                          onPressed: _reload,
                          icon: const Icon(AppIcons.refresh_rounded),
                          label: const Text('Try Again'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
