import 'package:flutter/material.dart';

import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../activities/presentation/activities_screen.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../misc/presentation/announcements_screen.dart';
import '../../misc/presentation/notes_screen.dart';
import '../../misc/presentation/todos_screen.dart';
import '../../student/presentation/cor_screen.dart';
import '../../student/presentation/grades_screen.dart';
import '../../student/presentation/my_qr_screen.dart';
import '../../student/presentation/profile_screen.dart';
import '../../student/presentation/requirements_screen.dart';

/// Student bottom-nav shell: Activities, My QR, More (drawer: profile,
/// requirements, grades, COR), My Logs, Profile.
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
    final pages = <Widget>[
      ActivitiesScreen(session: widget.session),
      MyQrScreen(session: widget.session),
      _StudentHub(session: widget.session),
      AnnouncementsScreen(session: widget.session),
      _ProfilePage(session: widget.session, controller: widget.controller),
    ];

    return Scaffold(
      body: pages[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
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
            icon: Icon(Icons.school_outlined),
            selectedIcon: Icon(Icons.school),
            label: 'Academics',
          ),
          NavigationDestination(
            icon: Icon(Icons.campaign_outlined),
            selectedIcon: Icon(Icons.campaign),
            label: 'Announcements',
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

/// Academics hub — a grid of cards linking to Profile, Requirements, Grades,
/// COR. (Enlistment arrives in a later phase.)
class _StudentHub extends StatelessWidget {
  const _StudentHub({required this.session});
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    final tiles = <_HubTile>[
      _HubTile(
        icon: Icons.assignment_outlined,
        title: 'Requirements',
        subtitle: 'Submit and track required documents',
        target: RequirementsScreen(session: session),
      ),
      _HubTile(
        icon: Icons.grading_outlined,
        title: 'Grades',
        subtitle: 'View your grades by semester',
        target: GradesScreen(session: session),
      ),
      _HubTile(
        icon: Icons.receipt_long_outlined,
        title: 'Certificate of Registration',
        subtitle: 'Enrolled subjects for this semester',
        target: CorScreen(session: session),
      ),
      _HubTile(
        icon: Icons.badge_outlined,
        title: 'My Profile',
        subtitle: 'Personal and academic details',
        target: ProfileScreen(session: session),
      ),
      _HubTile(
        icon: Icons.sticky_note_2_outlined,
        title: 'Notes',
        subtitle: 'Your personal notes',
        target: NotesScreen(session: session),
      ),
      _HubTile(
        icon: Icons.check_circle_outline,
        title: 'To-Do',
        subtitle: 'Tasks and reminders',
        target: TodosScreen(session: session),
      ),
    ];

    return Scaffold(
      appBar: AppBar(title: const Text('Academics')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: GridView.count(
              padding: const EdgeInsets.all(16),
              crossAxisCount: 2,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 0.95,
              children: tiles
                  .map((t) => _HubCard(tile: t, session: session))
                  .toList(),
            ),
          ),
        ],
      ),
    );
  }
}

class _HubTile {
  const _HubTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.target,
  });
  final IconData icon;
  final String title;
  final String subtitle;
  final Widget target;
}

class _HubCard extends StatelessWidget {
  const _HubCard({required this.tile, required this.session});
  final _HubTile tile;
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => tile.target),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(tile.icon, size: 32, color: AppTheme.midBlue),
              const SizedBox(height: 12),
              Text(tile.title,
                  style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 4),
              Expanded(
                child: Text(tile.subtitle,
                    style: Theme.of(context).textTheme.bodySmall),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProfilePage extends StatelessWidget {
  const _ProfilePage({required this.session, required this.controller});
  final AppSession session;
  final AuthController controller;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(24),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(session.displayName,
                            style:
                                Theme.of(context).textTheme.headlineSmall),
                        const SizedBox(height: 8),
                        _row('Role', session.position),
                        _row('Username', session.username),
                        _row('ID Number', session.idNumber),
                        _row('Email', session.email),
                        _row('School', session.schoolName),
                        _row('SY', session.activeSy),
                        _row('Semester', session.activeSem),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: () => _logout(context),
                  icon: const Icon(Icons.logout),
                  label: const Text('Sign out'),
                  style: FilledButton.styleFrom(
                    backgroundColor: AppTheme.error,
                  ),
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
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 96,
            child: Text(label,
                style: const TextStyle(
                    color: AppTheme.textMuted, fontWeight: FontWeight.w600)),
          ),
          Expanded(child: Text(value.isEmpty ? '—' : value)),
        ],
      ),
    );
  }

  void _logout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
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
      await controller.logout();
    }
  }
}
