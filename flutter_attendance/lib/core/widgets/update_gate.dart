import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../design/components/components.dart';
import '../design/tokens/app_tokens.dart';
import '../services/update_service.dart';
import '../theme/app_icons.dart';

/// Wraps the whole app (inside `MaterialApp.builder`). When the portal
/// advertises a newer build the child gains a slim download strip on top;
/// when the installed build is below the server's minimum the child is
/// replaced by a blocking update screen — the same trick the web app's
/// forced-password flow uses.
class UpdateGate extends StatelessWidget {
  const UpdateGate({super.key, required this.child});

  final Widget child;

  static Future<void> openDownload(AppUpdate update) async {
    // Lands in the browser, which downloads the APK; Android then offers the
    // in-place update because the release signature matches.
    await launchUrl(
      Uri.parse(update.url),
      mode: LaunchMode.externalApplication,
    );
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: UpdateService.instance,
      builder: (context, _) {
        final u = UpdateService.instance.update;
        if (u == null) return child;
        if (u.required) return _UpdateRequiredScreen(update: u);
        return Column(
          children: [
            _UpdateStrip(update: u),
            Expanded(child: child),
          ],
        );
      },
    );
  }
}

class _UpdateStrip extends StatelessWidget {
  const _UpdateStrip({required this.update});

  final AppUpdate update;

  @override
  Widget build(BuildContext context) {
    const color = AppInk.caution;
    final ink = AppInk.onTint(color);
    return Material(
      color: Color.alphaBlend(color.withValues(alpha: 0.12), Colors.white),
      child: InkWell(
        onTap: () => UpdateGate.openDownload(update),
        child: SafeArea(
          bottom: false,
          child: Container(
            decoration: BoxDecoration(
              border: Border(
                bottom: BorderSide(color: color.withValues(alpha: 0.18)),
              ),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
            child: Row(
              children: [
                Icon(AppIcons.system_update_alt_rounded, size: 16, color: color),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Update available — v${update.latestVersion}',
                    style: TextStyle(
                      color: ink,
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                Text(
                  'Download',
                  style: TextStyle(
                    color: color,
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(width: 12),
                GestureDetector(
                  onTap: UpdateService.instance.dismiss,
                  child: Icon(AppIcons.close, size: 16, color: ink),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Below the server's minimum build: there is no dismiss. Queued scans and
/// the saved session carry over — the message says so, since a scanner phone
/// mid-event must not panic about losing work.
class _UpdateRequiredScreen extends StatelessWidget {
  const _UpdateRequiredScreen({required this.update});

  final AppUpdate update;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppInk.page,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 24, 24, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              const Center(child: AppBrandMark(size: 72)),
              const SizedBox(height: 24),
              Text(
                'Update required',
                textAlign: TextAlign.center,
                style: AppType.title,
              ),
              const SizedBox(height: 8),
              Text(
                'Install version ${update.latestVersion} to keep using the '
                'app. Your sign-in and any unsent scans carry over.',
                textAlign: TextAlign.center,
                style: AppType.body.copyWith(color: AppInk.muted),
              ),
              const Spacer(),
              AppButton(
                label: 'Download update',
                icon: AppIcons.download_rounded,
                fullWidth: true,
                size: AppButtonSize.lg,
                onTap: () => UpdateGate.openDownload(update),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
