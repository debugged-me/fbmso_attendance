import 'package:flutter/material.dart';

import '../design/components/components.dart';
import '../design/tokens/app_tokens.dart';
import '../services/biometric_service.dart';
import '../../features/auth/domain/app_session.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/change_avatar_screen.dart';
import '../../features/auth/presentation/change_password_screen.dart';

/// A consistent app drawer used by every shell. Shows the user header,
/// navigation items, and account actions. This drawer is attached to the
/// shell's Scaffold so it's available on every page via the hamburger icon
/// in the app bar or by swiping from the left edge.
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
    return Drawer(
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
      ),
      width: 290,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Header ───────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
              child: Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: AppInk.accent.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.person_rounded,
                        color: AppInk.accent, size: 26),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          session.displayName,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: AppInk.heading,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          session.position,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppInk.muted,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: AppRule(),
            ),
            // ── Navigation items ─────────────────────────────────────
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  ...items.map((item) => _DrawerTile(item: item)),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    child: const AppRule(),
                  ),
                  // ── Account actions (always present) ──────────────
                  _DrawerTile(
                    item: DrawerItem(
                      icon: Icons.lock_outline_rounded,
                      title: 'Change Password',
                      subtitle: 'Update your account password',
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
                      icon: Icons.photo_camera_outlined,
                      title: 'Change Avatar',
                      subtitle: 'Update your profile picture',
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
                      icon: Icons.logout_rounded,
                      title: 'Sign out',
                      subtitle: 'Exit the app',
                      iconColor: Colors.red,
                      onTap: (ctx) => _confirmLogout(ctx),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _confirmLogout(BuildContext context) async {
    Navigator.of(context).pop();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Sign out?'),
        content: const Text('You will need to sign in again to continue.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Sign out'),
          ),
        ],
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
    required this.subtitle,
    required this.onTap,
    this.iconColor = AppInk.accent,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final void Function(BuildContext context) onTap;
  final Color iconColor;
}

class _DrawerTile extends StatelessWidget {
  const _DrawerTile({required this.item});
  final DrawerItem item;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => item.onTap(context),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          child: Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: item.iconColor.withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(item.icon, size: 20, color: item.iconColor),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.title,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: AppInk.heading,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      item.subtitle,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppInk.muted,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded,
                  color: AppInk.muted, size: 20),
            ],
          ),
        ),
      ),
    );
  }
}
