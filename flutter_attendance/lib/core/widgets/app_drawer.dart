import 'package:flutter/material.dart';

import '../design/components/components.dart';
import '../design/tokens/app_brand.dart';
import '../design/tokens/app_tokens.dart';
import '../services/biometric_service.dart';
import '../services/connectivity_service.dart';
import '../services/outbox_service.dart';
import '../theme/app_icons.dart';
import '../../features/auth/domain/app_session.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/change_avatar_screen.dart';
import '../../features/auth/presentation/change_password_screen.dart';

/// A consistent app drawer used by every shell: the signed-in person at the
/// top, navigation grouped by area, account actions at the bottom. It is
/// attached to the shell's Scaffold so it's available on every page via the
/// menu button or by swiping from the left edge.
class AppAppDrawer extends StatelessWidget {
  const AppAppDrawer({
    super.key,
    required this.session,
    required this.controller,
    required this.items,
  });

  final AppSession session;
  final AuthController controller;
  final List<DrawerItem> items;

  @override
  Widget build(BuildContext context) {
    final term = [session.activeSy, session.activeSem]
        .where((e) => e.trim().isNotEmpty)
        .join(' · ');

    final nav = <Widget>[];
    String? lastGroup;
    for (final item in items) {
      if (item.group != null && item.group != lastGroup) {
        nav.add(_GroupLabel(item.group!));
        lastGroup = item.group;
      }
      nav.add(_DrawerTile(item: item));
    }

    return Drawer(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(AppRadius.xl),
          bottomRight: Radius.circular(AppRadius.xl),
        ),
      ),
      width: 300,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Who is signed in ─────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
              child: Row(
                children: [
                  AppAvatar(
                    name: session.displayName,
                    url: session.avatar,
                    size: 52,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          session.displayName,
                          style: AppType.row.copyWith(fontSize: 16),
                        ),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            AppChip(
                              label: _roleLabel(session.position),
                              tone: AppInk.accent,
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            if (term.isNotEmpty || session.schoolName.isNotEmpty)
              Container(
                width: double.infinity,
                margin: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: AppInk.page,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Row(
                  children: [
                    const Icon(AppIcons.school_outlined,
                        size: 18, color: AppInk.muted),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (session.schoolName.isNotEmpty)
                            Text(
                              session.schoolName,
                              style: AppType.caption.copyWith(
                                color: AppInk.heading,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          if (term.isNotEmpty)
                            Text(term, style: AppType.caption),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            const AppRule(),
            // ── Navigation ───────────────────────────────────────────
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(10, 6, 10, 12),
                children: [
                  ...nav,
                  const _GroupLabel('Account'),
                  _DrawerTile(
                    item: DrawerItem(
                      icon: AppIcons.lock_key,
                      title: 'Change password',
                      onTap: (ctx) {
                        Navigator.of(ctx).pop();
                        Navigator.of(ctx).push(
                          MaterialPageRoute(
                            builder: (_) =>
                                ChangePasswordScreen(session: session),
                          ),
                        );
                      },
                    ),
                  ),
                  _DrawerTile(
                    item: DrawerItem(
                      icon: AppIcons.camera,
                      title: 'Profile photo',
                      onTap: (ctx) {
                        Navigator.of(ctx).pop();
                        Navigator.of(ctx).push(
                          MaterialPageRoute(
                            builder: (_) =>
                                ChangeAvatarScreen(session: session),
                          ),
                        );
                      },
                    ),
                  ),
                  _DrawerTile(
                    item: DrawerItem(
                      icon: AppIcons.sign_out,
                      title: 'Sign out',
                      iconColor: AppInk.critical,
                      onTap: (ctx) => _confirmLogout(ctx),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 14),
              child: Text(
                '${AppBrand.name} · ${AppBrand.aboutVersion}',
                style: AppType.caption.copyWith(color: AppInk.faint),
              ),
            ),
          ],
        ),
      ),
    );
  }

  static String _roleLabel(String position) {
    final p = position.trim();
    if (p.isEmpty) return 'Staff';
    return p[0].toUpperCase() + p.substring(1);
  }

  void _confirmLogout(BuildContext context) async {
    Navigator.of(context).pop();

    // Signing out is easy to undo online and a dead end offline: signing back
    // in needs the server. Say so, and say what is still waiting to upload.
    final pending = await OutboxService.queuedCount() +
        await OutboxService.authBlockedCount() +
        await OutboxService.conflictCount();
    final online = await ConnectivityService.isReachable();
    if (!context.mounted) return;
    final warnings = [
      if (!online)
        'This phone is offline. You will not be able to sign in again '
            'until it has a connection.',
      if (pending > 0)
        '$pending item(s) have not reached the server yet. They stay on this '
            'phone and upload after the next sign-in — under that account.',
    ];

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        icon: const AppIconBox(
          icon: AppIcons.sign_out,
          color: AppInk.critical,
          size: 52,
          iconSize: 24,
        ),
        title: const Text('Sign out?'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'You will need to sign in again to continue.',
              textAlign: TextAlign.center,
            ),
            for (final w in warnings) ...[
              const SizedBox(height: 12),
              AppNotice(message: w, tone: AppInk.caution),
            ],
            const SizedBox(height: 24),
            // Stacked: half of a dialog is too narrow for "Sign out anyway".
            AppButton(
              label: warnings.isEmpty ? 'Sign out' : 'Sign out anyway',
              style: AppButtonStyle.destructive,
              fullWidth: true,
              onTap: () => Navigator.pop(ctx, true),
            ),
            const SizedBox(height: 8),
            AppButton(
              label: 'Cancel',
              style: AppButtonStyle.outline,
              fullWidth: true,
              onTap: () => Navigator.pop(ctx, false),
            ),
          ],
        ),
      ),
    );
    if (ok == true) {
      await BiometricService.disable();
      await controller.logout();
    }
  }
}

/// One navigation entry in the drawer.
class DrawerItem {
  const DrawerItem({
    required this.icon,
    required this.title,
    required this.onTap,
    this.iconColor,
    this.group,
  });

  final IconData icon;
  final String title;
  final void Function(BuildContext context) onTap;

  /// Only for exceptions (Sign out). Navigation uses the neutral ink.
  final Color? iconColor;

  /// Heading shown above the first item of each group.
  final String? group;
}

class _GroupLabel extends StatelessWidget {
  const _GroupLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 16, 12, 6),
      child: Text(text, style: AppType.section.copyWith(fontSize: 12.5)),
    );
  }
}

class _DrawerTile extends StatelessWidget {
  const _DrawerTile({required this.item});
  final DrawerItem item;

  @override
  Widget build(BuildContext context) {
    final tone = item.iconColor;
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(AppRadius.md),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => item.onTap(context),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
          child: Row(
            children: [
              Icon(item.icon, size: 20, color: tone ?? AppInk.secondary),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  item.title,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                    color: tone ?? AppInk.body,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
