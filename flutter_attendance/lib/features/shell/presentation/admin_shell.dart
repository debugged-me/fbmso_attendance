import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/widgets/app_drawer.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../activities/presentation/activities_screen.dart';
import '../../activities/presentation/dashboard_screen.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/manage_activities_screen.dart';
import '../../attendance/presentation/scan_screen.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../misc/presentation/announcements_screen.dart';
import '../../misc/presentation/expenses_screen.dart';
import '../../misc/presentation/notes_screen.dart';
import '../../misc/presentation/personnel_manage_screen.dart';
import '../../misc/presentation/registered_students_screen.dart';
import '../../misc/presentation/todos_screen.dart';
import '../../misc/presentation/user_accounts_screen.dart';

/// Admin shell: Dashboard + Activities + Scan in the bottom nav.
/// A consistent drawer sidebar is available on every page with all
/// admin features (Manage Activities, Attendance Logs, Personnel,
/// Registered Students, Manage Users, Expenses, Announcements, Notes,
/// To-Do, Profile, Change Password, Change Avatar, Sign out).
class AdminShell extends StatefulWidget {
  const AdminShell({
    super.key,
    required this.session,
    required this.controller,
  });

  final AppSession session;
  final AuthController controller;

  @override
  State<AdminShell> createState() => _AdminShellState();
}

class _AdminShellState extends State<AdminShell> {
  int _index = 0;

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
          icon: Icons.edit_calendar_rounded,
          title: 'Manage Activities',
          subtitle: 'Create, edit, delete activities',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    ManageActivitiesScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.history_rounded,
          title: 'Attendance Logs',
          subtitle: 'Per-activity attendance records',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    _ActivityLogPicker(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.people_outline_rounded,
          title: 'Personnel',
          subtitle: 'Manage officials and staff',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    PersonnelManageScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.school_outlined,
          title: 'Registered Students',
          subtitle: 'List of registered students',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    RegisteredStudentsScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.manage_accounts_rounded,
          title: 'Manage Users',
          subtitle: 'Admin accounts',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    UserAccountsScreen(session: widget.session),
              ),
            );
          },
        ),
        DrawerItem(
          icon: Icons.receipt_long_outlined,
          title: 'Expenses',
          subtitle: 'Recent accounting expenses',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => ExpensesScreen(session: widget.session),
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
            _ScanPicker(session: session, menuButton: menu),
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
            icon: Icon(Icons.qr_code_scanner_outlined),
            selectedIcon: Icon(Icons.qr_code_scanner),
            label: 'Scan',
          ),
        ],
      ),
    );
  }
}

/// Activity picker → opens the camera scanner for the selected activity.
class _ScanPicker extends StatefulWidget {
  const _ScanPicker({required this.session, this.menuButton});
  final AppSession session;
  final Widget? menuButton;

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
    return AppScaffold(
      title: 'Scan',
      showBackButton: false,
      leading: widget.menuButton,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _activities.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 80),
                            AppEmptyState(
                              icon: Icons.qr_code_scanner_rounded,
                              title: 'No open activities',
                              subtitle:
                                  'Open activities will appear here so you can start scanning.',
                              tone: AppInk.muted,
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                          itemCount: _activities.length,
                          itemBuilder: (context, i) {
                            final a = _activities[i];
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: AppCard(
                                radius: 16,
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 14, vertical: 14),
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
                                child: Row(
                                  children: [
                                    Container(
                                      width: 42,
                                      height: 42,
                                      decoration: BoxDecoration(
                                        color: AppInk.accent
                                            .withValues(alpha: 0.12),
                                        borderRadius:
                                            BorderRadius.circular(12),
                                      ),
                                      child: const Icon(
                                          Icons.qr_code_scanner_rounded,
                                          color: AppInk.accent,
                                          size: 22),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            a.title,
                                            style: const TextStyle(
                                              fontSize: 15,
                                              fontWeight: FontWeight.w700,
                                              color: AppInk.heading,
                                            ),
                                          ),
                                          const SizedBox(height: 3),
                                          Text(
                                            a.activityDate,
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
                              ),
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

/// Activity picker for viewing attendance logs per activity.
class _ActivityLogPicker extends StatefulWidget {
  const _ActivityLogPicker({required this.session});
  final AppSession session;

  @override
  State<_ActivityLogPicker> createState() => _ActivityLogPickerState();
}

class _ActivityLogPickerState extends State<_ActivityLogPicker> {
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
      _activities = list;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Attendance Logs',
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _activities.isEmpty
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          const AppEmptyState(
                            icon: Icons.history_rounded,
                            title: 'No activities',
                            subtitle: 'Activities will appear here.',
                          ),
                        ])
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                          itemCount: _activities.length,
                          itemBuilder: (context, i) {
                            final a = _activities[i];
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: AppCard(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 14, vertical: 14),
                                onTap: () {
                                  Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => _ActivityLogView(
                                        session: widget.session,
                                        activity: a,
                                      ),
                                    ),
                                  );
                                },
                                child: Row(
                                  children: [
                                    Container(
                                      width: 42,
                                      height: 42,
                                      decoration: BoxDecoration(
                                        color: AppInk.accent
                                            .withValues(alpha: 0.12),
                                        borderRadius:
                                            BorderRadius.circular(12),
                                      ),
                                      child: const Icon(Icons.history_rounded,
                                          color: AppInk.accent, size: 22),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            a.title,
                                            style: const TextStyle(
                                              fontSize: 15,
                                              fontWeight: FontWeight.w700,
                                              color: AppInk.heading,
                                            ),
                                          ),
                                          const SizedBox(height: 3),
                                          Text(
                                            a.activityDate,
                                            style: const TextStyle(
                                                fontSize: 13,
                                                color: AppInk.muted),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: (a.isOpen
                                                ? AppInk.positive
                                                : AppInk.muted)
                                            .withValues(alpha: 0.10),
                                        borderRadius:
                                            BorderRadius.circular(999),
                                      ),
                                      child: Text(
                                        a.isOpen ? 'Open' : 'Closed',
                                        style: TextStyle(
                                          fontSize: 11,
                                          fontWeight: FontWeight.w700,
                                          color: a.isOpen
                                              ? AppInk.positive
                                              : AppInk.muted,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
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

/// Shows the attendance log for a single activity.
class _ActivityLogView extends StatefulWidget {
  const _ActivityLogView({required this.session, required this.activity});
  final AppSession session;
  final Activity activity;

  @override
  State<_ActivityLogView> createState() => _ActivityLogViewState();
}

class _ActivityLogViewState extends State<_ActivityLogView> {
  late final AttendanceApi _api;
  final List<Map<String, dynamic>> _logs = [];
  int _total = 0;
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  String _search = '';
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  static const _pageSize = 50;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _scrollController.addListener(_onScroll);
    _load();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      _loadMore();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await _api.activityLogs(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: widget.activity.activityId,
        limit: _pageSize,
        offset: 0,
        search: _search,
      );
      if (!mounted) return;
      setState(() {
        _logs
          ..clear()
          ..addAll(result.rows);
        _total = result.total;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _logs.length >= _total) return;
    setState(() => _loadingMore = true);
    try {
      final result = await _api.activityLogs(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: widget.activity.activityId,
        limit: _pageSize,
        offset: _logs.length,
        search: _search,
      );
      if (!mounted) return;
      setState(() {
        _logs.addAll(result.rows);
        _total = result.total;
        _loadingMore = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
    }
  }

  void _onSearchChanged(String v) {
    _search = v;
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: widget.activity.title,
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Search name or student ID...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppInk.muted),
                suffixIcon: _search.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 20),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
                      )
                    : null,
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppInk.rule, width: 1.5),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppInk.accent, width: 2),
                ),
              ),
            ),
          ),
          if (!_loading && _error == null)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
              child: Row(
                children: [
                  Text(
                    '${_logs.length} of $_total records',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppInk.muted,
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          AppEmptyState(
                            icon: Icons.cloud_off_rounded,
                            title: 'Failed to load',
                            subtitle: _error,
                            action: 'Retry',
                            onAction: _load,
                          ),
                        ])
                      : _logs.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.history_rounded,
                                title: 'No attendance records',
                                subtitle: 'No one has checked in yet.',
                              ),
                            ])
                          : ListView.builder(
                              controller: _scrollController,
                              padding:
                                  const EdgeInsets.fromLTRB(16, 12, 16, 24),
                              itemCount: _logs.length + (_loadingMore ? 1 : 0),
                              itemBuilder: (context, i) {
                                if (i >= _logs.length) {
                                  return const Padding(
                                    padding: EdgeInsets.all(16),
                                    child: Center(
                                      child: SizedBox(
                                        width: 24, height: 24,
                                        child: CircularProgressIndicator(strokeWidth: 2.5),
                                      ),
                                    ),
                                  );
                                }
                                final log = _logs[i];
                                final name =
                                    (log['student_name'] ?? '').toString().trim();
                                final studentNo =
                                    (log['student_number'] ?? '').toString();
                                final checkedIn =
                                    (log['checked_in_at'] ?? '').toString();
                                final checkedOut =
                                    (log['checked_out_at'] ?? '').toString();
                                final sessionLabel =
                                    (log['session_label'] ?? '—').toString();
                                final source =
                                    (log['source'] ?? '').toString();

                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: AppCard(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 14, vertical: 12),
                                    child: Row(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Container(
                                          width: 40,
                                          height: 40,
                                          decoration: BoxDecoration(
                                            color: AppInk.accent
                                                .withValues(alpha: 0.12),
                                            borderRadius:
                                                BorderRadius.circular(12),
                                          ),
                                          child: const Icon(Icons.person_rounded,
                                              color: AppInk.accent, size: 20),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                name.isEmpty
                                                    ? studentNo
                                                    : name,
                                                style: const TextStyle(
                                                  fontSize: 14,
                                                  fontWeight: FontWeight.w700,
                                                  color: AppInk.heading,
                                                ),
                                                maxLines: 1,
                                                overflow:
                                                    TextOverflow.ellipsis,
                                              ),
                                              const SizedBox(height: 4),
                                              Wrap(
                                                spacing: 8,
                                                runSpacing: 4,
                                                crossAxisAlignment:
                                                    WrapCrossAlignment.center,
                                                children: [
                                                  Text(
                                                    studentNo,
                                                    style: const TextStyle(
                                                        fontSize: 12,
                                                        color: AppInk.muted),
                                                  ),
                                                  if (sessionLabel != '—')
                                                    Text(
                                                      sessionLabel,
                                                      style: const TextStyle(
                                                          fontSize: 12,
                                                          color: AppInk.muted),
                                                    ),
                                                  if (source.isNotEmpty)
                                                    Container(
                                                      padding: const EdgeInsets
                                                          .symmetric(
                                                          horizontal: 6,
                                                          vertical: 2),
                                                      decoration: BoxDecoration(
                                                        color: AppInk.accent
                                                            .withValues(
                                                                alpha: 0.08),
                                                        borderRadius:
                                                            BorderRadius
                                                                .circular(6),
                                                      ),
                                                      child: Text(
                                                        source.toUpperCase(),
                                                        style: const TextStyle(
                                                          fontSize: 10,
                                                          fontWeight:
                                                              FontWeight.w700,
                                                          color: AppInk.accent,
                                                        ),
                                                      ),
                                                    ),
                                                ],
                                              ),
                                              const SizedBox(height: 6),
                                              Wrap(
                                                spacing: 12,
                                                runSpacing: 4,
                                                children: [
                                                  Row(
                                                    mainAxisSize:
                                                        MainAxisSize.min,
                                                    children: [
                                                      const Icon(
                                                          Icons.login_rounded,
                                                          size: 14,
                                                          color: AppInk
                                                              .positive),
                                                      const SizedBox(width: 4),
                                                      Text(
                                                        checkedIn,
                                                        style: const TextStyle(
                                                            fontSize: 12,
                                                            color: AppInk
                                                                .muted),
                                                      ),
                                                    ],
                                                  ),
                                                  if (checkedOut.isNotEmpty)
                                                    Row(
                                                      mainAxisSize:
                                                          MainAxisSize.min,
                                                      children: [
                                                        const Icon(
                                                            Icons
                                                                .logout_rounded,
                                                            size: 14,
                                                            color: AppInk
                                                                .critical),
                                                        const SizedBox(width: 4),
                                                        Text(
                                                          checkedOut,
                                                          style: const TextStyle(
                                                              fontSize: 12,
                                                              color: AppInk
                                                                  .muted),
                                                        ),
                                                      ],
                                                    ),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
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