import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/services/biometric_service.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/widgets/notification_bell.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../activities/presentation/activities_screen.dart';
import '../../activities/presentation/dashboard_screen.dart';
import '../../attendance/presentation/my_logs_screen.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/change_avatar_screen.dart';
import '../../auth/presentation/change_password_screen.dart';
import '../../misc/presentation/announcements_screen.dart';
import '../../misc/presentation/notes_screen.dart';
import '../../misc/presentation/todos_screen.dart';
import '../../student/presentation/finance_screen.dart';
import '../../student/presentation/my_qr_screen.dart';
import '../../student/presentation/profile_screen.dart';

/// Student shell: Dashboard, Activities, My QR, More, Profile.
/// Bottom nav bar stays clean; extra items live in the "More" tab.
class StudentShell extends StatefulWidget {
  const StudentShell({
    super.key,
    required this.session,
    required this.controller,
  });

  final AppSession session;
  final AuthController controller;

  @override
  State<StudentShell> createState() => _StudentShellState();
}

class _StudentShellState extends State<StudentShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final session = widget.session;
    final pages = <Widget>[
      DashboardScreen(session: session),
      ActivitiesScreen(session: session),
      MyQrScreen(session: session),
      _MorePage(session: session),
      _ProfilePage(session: session, controller: widget.controller),
    ];

    return Scaffold(
      body: pages[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Dashboard',
          ),
          NavigationDestination(
            icon: Icon(AppIcons.home_outlined),
            selectedIcon: Icon(AppIcons.home_rounded),
            label: 'Activities',
          ),
          NavigationDestination(
            icon: Icon(Icons.qr_code_2_outlined),
            selectedIcon: Icon(Icons.qr_code_2),
            label: 'My QR',
          ),
          NavigationDestination(
            icon: Icon(Icons.grid_view_outlined),
            selectedIcon: Icon(Icons.grid_view),
            label: 'More',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}

/// "More" page — all extra items in a clean list.
class _MorePage extends StatelessWidget {
  const _MorePage({required this.session});
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    final tiles = <_ListEntry>[
      _ListEntry(
        icon: Icons.history_rounded,
        iconColor: AppInk.accent,
        title: 'My Logs',
        subtitle: 'Your attendance check-in/out history',
        target: MyLogsScreen(session: session),
      ),
      _ListEntry(
        icon: Icons.account_balance_wallet_outlined,
        iconColor: AppInk.positive,
        title: 'Finance',
        subtitle: 'Payment records & accounting',
        target: FinanceScreen(session: session),
      ),
      _ListEntry(
        icon: Icons.campaign_outlined,
        iconColor: AppInk.accent,
        title: 'Announcements',
        subtitle: 'School-wide notices',
        target: AnnouncementsScreen(session: session),
      ),
      _ListEntry(
        icon: Icons.sticky_note_2_outlined,
        iconColor: AppInk.caution,
        title: 'Notes',
        subtitle: 'Your personal notes',
        target: NotesScreen(session: session),
      ),
      _ListEntry(
        icon: Icons.check_circle_outline,
        iconColor: AppInk.positive,
        title: 'To-Do',
        subtitle: 'Tasks and reminders',
        target: TodosScreen(session: session),
      ),
      _ListEntry(
        icon: Icons.lock_outline_rounded,
        iconColor: AppInk.accent,
        title: 'Change Password',
        subtitle: 'Update your account password',
        target: ChangePasswordScreen(session: session),
      ),
      _ListEntry(
        icon: Icons.photo_camera_outlined,
        iconColor: AppInk.accent,
        title: 'Change Avatar',
        subtitle: 'Update your profile picture',
        target: ChangeAvatarScreen(session: session),
      ),
      _ListEntry(
        icon: Icons.badge_outlined,
        iconColor: AppInk.accent,
        title: 'My Profile Details',
        subtitle: 'Personal and academic details',
        target: ProfileScreen(session: session),
      ),
    ];

    return AppScaffold(
      title: 'More',
      showBackButton: false,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              itemCount: tiles.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final t = tiles[i];
                return AppCard(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 14),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => t.target),
                    );
                  },
                  child: Row(
                    children: [
                      Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                          color: t.iconColor.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(t.icon, size: 22, color: t.iconColor),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              t.title,
                              style: const TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                color: AppInk.heading,
                              ),
                            ),
                            const SizedBox(height: 3),
                            Text(
                              t.subtitle,
                              style: const TextStyle(
                                fontSize: 13,
                                color: AppInk.muted,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right_rounded,
                          color: AppInk.muted, size: 22),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _ListEntry {
  const _ListEntry({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.subtitle,
    required this.target,
  });
  final IconData icon;
  final Color iconColor;
  final String title;
  final String subtitle;
  final Widget target;
}

class _ProfilePage extends StatelessWidget {
  const _ProfilePage({required this.session, required this.controller});
  final AppSession session;
  final AuthController controller;

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Profile',
      showBackButton: false,
      actions: const [NotificationBell()],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
              children: [
                AppCard(
                  radius: 20,
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            width: 52,
                            height: 52,
                            decoration: BoxDecoration(
                              color: AppInk.accent.withValues(alpha: 0.12),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.person_rounded,
                                color: AppInk.accent, size: 26),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Text(
                              session.displayName,
                              style: const TextStyle(
                                fontSize: 19,
                                fontWeight: FontWeight.w800,
                                color: AppInk.heading,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      const AppRule(),
                      const SizedBox(height: 12),
                      _row('Role', session.position),
                      _row('Username', session.username),
                      _row('ID Number', session.idNumber),
                      _row('Email', session.email),
                      _row('School', session.schoolName),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                const _BiometricToggle(),
                const SizedBox(height: 20),
                AppButton(
                  label: 'Sign out',
                  icon: Icons.logout_rounded,
                  style: AppButtonStyle.destructive,
                  fullWidth: true,
                  size: AppButtonSize.lg,
                  onTap: () => _logout(context),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppInk.muted,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '—' : value,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: AppInk.heading,
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _logout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20)),
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

class _BiometricToggle extends StatefulWidget {
  const _BiometricToggle();

  @override
  State<_BiometricToggle> createState() => _BiometricToggleState();
}

class _BiometricToggleState extends State<_BiometricToggle> {
  bool _available = false;
  bool _enabled = false;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final available = await BiometricService.isAvailable;
    final enabled = await BiometricService.isEnabled;
    if (!mounted) return;
    setState(() {
      _available = available;
      _enabled = enabled;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const SizedBox.shrink();
    if (!_available) {
      return AppCard(
        radius: 16,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: AppInk.muted.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.fingerprint_rounded,
                  color: AppInk.muted, size: 22),
            ),
            const SizedBox(width: 12),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Biometric Unlock',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                    ),
                  ),
                  SizedBox(height: 3),
                  Text(
                    'Not available on this device.',
                    style: TextStyle(fontSize: 13, color: AppInk.muted),
                  ),
                ],
              ),
            ),
            const Icon(Icons.lock_outline_rounded,
                color: AppInk.muted, size: 20),
          ],
        ),
      );
    }
    return AppCard(
      radius: 16,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: AppInk.accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.fingerprint_rounded,
                color: AppInk.accent, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Biometric Unlock',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppInk.heading,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  _enabled
                      ? 'App will require biometrics on startup.'
                      : 'Require fingerprint/face to open the app.',
                  style: const TextStyle(fontSize: 13, color: AppInk.muted),
                ),
              ],
            ),
          ),
          Switch(
            value: _enabled,
            onChanged: (v) async {
              if (v) {
                final ok = await BiometricService.authenticate(
                  reason: 'Authenticate to enable biometric unlock.',
                );
                if (!ok) return;
              }
              await BiometricService.setEnabled(v);
              setState(() => _enabled = v);
            },
          ),
        ],
      ),
    );
  }
}
