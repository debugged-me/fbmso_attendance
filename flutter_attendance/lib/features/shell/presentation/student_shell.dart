import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/widgets/app_drawer.dart';
import '../../activities/presentation/dashboard_screen.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../misc/presentation/personnel_screen.dart';
import '../../student/presentation/finance_screen.dart';
import '../../student/presentation/my_qr_screen.dart';
import '../../student/presentation/profile_screen.dart';
import '../../../core/theme/app_icons.dart';

/// Student shell: Dashboard + My QR in the bottom nav, matching the web
/// student sidebar (Dashboard, My Profile, My Payment Records, My QR Code,
/// FBMSO Officials). Announcements appear on the dashboard itself, as on web.
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
    return Padding(
      padding: const EdgeInsets.only(left: 12),
      child: Center(
        child: AppCircleButton(
          icon: AppIcons.menu_rounded,
          tooltip: 'Menu',
          onTap: () => Scaffold.of(context).openDrawer(),
        ),
      ),
    );
  }

  List<DrawerItem> get _drawerItems => [
        DrawerItem(
          group: 'You',
          icon: AppIcons.badge_outlined,
          title: 'My profile',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => ProfileScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: AppIcons.account_balance_wallet_outlined,
          title: 'Payment records',
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
          group: 'Organization',
          icon: AppIcons.groups_outlined,
          title: 'FBMSO officials',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => PersonnelScreen(session: widget.session),
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
            MyQrScreen(
              session: session,
              menuButton: menu,
            ),
          ][_index];
        },
      ),
      bottomNavigationBar: AppBottomNav(
        currentIndex: _index,
        onSelect: (i) => setState(() => _index = i),
        items: const [
          AppNavItem(
            icon: AppIcons.house,
            selectedIcon: AppIcons.house_fill,
            label: 'Home',
          ),
          AppNavItem(
            icon: AppIcons.qr_code_2,
            label: 'My QR',
            prominent: true,
          ),
        ],
      ),
    );
  }
}
