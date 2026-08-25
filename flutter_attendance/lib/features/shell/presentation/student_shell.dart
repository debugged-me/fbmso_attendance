import 'package:flutter/material.dart';

import '../../../core/theme/app_icons.dart';
import '../../../core/widgets/app_drawer.dart';
import '../../activities/presentation/activities_screen.dart';
import '../../activities/presentation/dashboard_screen.dart';
import '../../attendance/presentation/my_logs_screen.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../misc/presentation/announcements_screen.dart';
import '../../misc/presentation/notes_screen.dart';
import '../../misc/presentation/todos_screen.dart';
import '../../student/presentation/finance_screen.dart';
import '../../student/presentation/my_qr_screen.dart';
import '../../student/presentation/profile_screen.dart';

/// Student shell: Dashboard + Activities + My QR in the bottom nav.
/// A consistent drawer sidebar is available on every page with all
/// secondary features (My Logs, Finance, Announcements, Notes, To-Do,
/// Profile, Change Password, Change Avatar, Sign out).
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

  /// Menu button that opens the drawer — used in every page's app bar.
  Widget _menuButton(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.menu_rounded, size: 24),
      onPressed: () => Scaffold.of(context).openDrawer(),
      style: IconButton.styleFrom(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }

  List<DrawerItem> get _drawerItems => [
        DrawerItem(
          icon: Icons.history_rounded,
          title: 'My Logs',
          subtitle: 'Attendance check-in/out history',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => MyLogsScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.account_balance_wallet_outlined,
          title: 'Finance',
          subtitle: 'Payment records & accounting',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => FinanceScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.campaign_outlined,
          title: 'Announcements',
          subtitle: 'School-wide notices',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    AnnouncementsScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.sticky_note_2_outlined,
          title: 'Notes',
          subtitle: 'Your personal notes',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => NotesScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.check_circle_outline,
          title: 'To-Do',
          subtitle: 'Tasks and reminders',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => TodosScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.badge_outlined,
          title: 'My Profile',
          subtitle: 'Personal and academic details',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => ProfileScreen(session: widget.session),
              ),
            );
          },
        ),
      ];

  @override
  Widget build(BuildContext context) {
    final session = widget.session;

    return Scaffold(
      drawer: AppAppDrawer(
        session: session,
        controller: widget.controller,
        items: _drawerItems,
      ),
      body: Builder(
        builder: (context) {
          final menu = _menuButton(context);
          return <Widget>[
            DashboardScreen(
              session: session,
              menuButton: menu,
            ),
            ActivitiesScreen(
              session: session,
              menuButton: menu,
            ),
            MyQrScreen(
              session: session,
              menuButton: menu,
            ),
          ][_index];
        },
      ),
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
        ],
      ),
    );
  }
}
