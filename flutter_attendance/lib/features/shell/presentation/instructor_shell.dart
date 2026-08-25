import 'package:flutter/material.dart';

import '../../../core/services/biometric_service.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/notification_bell.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../activities/presentation/activities_screen.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/scan_screen.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../misc/presentation/announcements_screen.dart';
import '../../misc/presentation/notes_screen.dart';
import '../../misc/presentation/todos_screen.dart';

/// Instructor/personnel shell: Home (activities list), Scan (pick activity →
/// camera), Logs (per-activity attendance), Profile.
class InstructorShell extends StatefulWidget {
  const InstructorShell({
    super.key,
    required this.session,
    required this.controller,
  });

  final AppSession session;
  final AuthController controller;

  @override
  State<InstructorShell> createState() => _InstructorShellState();
}

class _InstructorShellState extends State<InstructorShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      ActivitiesScreen(session: widget.session),
      _ScanPicker(session: widget.session),
      _InstructorHub(session: widget.session),
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
            icon: Icon(Icons.qr_code_scanner_outlined),
            selectedIcon: Icon(Icons.qr_code_scanner),
            label: 'Scan',
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

/// Activity picker → opens the camera scanner for the selected activity.
class _ScanPicker extends StatefulWidget {
  const _ScanPicker({required this.session});
  final AppSession session;

  @override
  State<_ScanPicker> createState() => _ScanPickerState();
}

class _ScanPickerState extends State<_ScanPicker> {
  late final AttendanceApi _api;
  List<Activity> _activities = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final list = await _api.activities(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
    );
    if (!mounted) return;
    setState(() {
      _activities = list.where((a) => a.isOpen).toList();
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Scan — pick activity')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _activities.isEmpty
                      ? const Center(
                          child: Text('No open activities to scan.',
                              style: TextStyle(color: AppTheme.textMuted)),
                        )
                      : ListView.builder(
                          itemCount: _activities.length,
                          itemBuilder: (context, i) {
                            final a = _activities[i];
                            return ListTile(
                              title: Text(a.title),
                              subtitle: Text(a.activityDate),
                              trailing: const Icon(Icons.qr_code_scanner),
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => ScanScreen(
                                      session: widget.session,
                                      activityId: a.activityId,
                                      activityTitle: a.title,
                                    ),
                                  ),
                                );
                              },
                            );
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Instructor "More" hub — announcements, notes, todos.
class _InstructorHub extends StatelessWidget {
  const _InstructorHub({required this.session});
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    final tiles = <_HubTile>[
      _HubTile(
        icon: Icons.campaign_outlined,
        title: 'Announcements',
        subtitle: 'School-wide notices',
        target: AnnouncementsScreen(session: session),
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
      appBar: AppBar(title: const Text('More')),
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
              children: tiles.map((t) => _HubCard(tile: t)).toList(),
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
  const _HubCard({required this.tile});
  final _HubTile tile;

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
              Text(tile.title, style: Theme.of(context).textTheme.titleMedium),
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
      appBar: AppBar(
        title: const Text('Profile'),
        actions: const [NotificationBell()],
      ),
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
                        _row('Email', session.email),
                        _row('School', session.schoolName),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const _BiometricToggle(),
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
      return Card(
        child: ListTile(
          leading: const Icon(Icons.fingerprint, color: AppTheme.textMuted),
          title: const Text('Biometric Unlock'),
          subtitle: const Text('Not available on this device.'),
        ),
      );
    }
    return Card(
      child: SwitchListTile(
        secondary: const Icon(Icons.fingerprint),
        title: const Text('Biometric Unlock'),
        subtitle: Text(_enabled
            ? 'App will require biometrics on startup.'
            : 'Require fingerprint/face to open the app.'),
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
    );
  }
}
