import 'dart:io';

import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/utils/time_format.dart';
import '../../../core/widgets/app_drawer.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../accounting/presentation/collection_report_screen.dart';
import '../../accounting/presentation/expenses_report_screen.dart';
import '../../accounting/presentation/fees_setup_screen.dart';
import '../../accounting/presentation/ledger_screen.dart';
import '../../accounting/presentation/partial_payments_screen.dart';
import '../../accounting/presentation/payment_audit_log_screen.dart';
import '../../accounting/presentation/payment_entry_screen.dart';
import '../../activities/presentation/activities_screen.dart';
import '../../activities/presentation/dashboard_screen.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/activity_poster_screen.dart';
import '../../attendance/presentation/manage_activities_screen.dart';
import '../../attendance/presentation/scan_screen.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/domain/staff_permissions.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../misc/presentation/announcements_manage_screen.dart';
import '../../misc/presentation/departments_screen.dart';
import '../../misc/presentation/reports_screen.dart';
import '../../misc/presentation/sections_screen.dart';
import '../../misc/presentation/expenses_screen.dart';
import '../../misc/presentation/personnel_manage_screen.dart';
import '../../misc/presentation/registered_students_screen.dart';
import '../../misc/presentation/user_accounts_screen.dart';

/// Admin shell: Dashboard + Activities + Scan in the bottom nav.
/// A consistent drawer sidebar is available on every page with all
/// admin features (Manage Activities, Attendance Logs, Personnel,
/// Registered Students, Admin Accounts, Expenses, Announcements,
/// Change Password, Change Avatar, Sign out).
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

  /// Mirrors the web authorization model (see StaffPermissions): narrow
  /// roles like Committee and Cashier only see the modules their web
  /// allowlist grants them, instead of a drawer full of 403s.
  StaffPermissions get _perms => StaffPermissions.of(widget.session);

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

  /// Mirrors the web admin sidebar (application/views/includes/sidebar.php),
  /// in the same order: Registered Students, Attendance Logs, Activities (QR),
  /// Announcement, Activities Reports, School Expenses, Manage (Course,
  /// Sections, Admin Accounts), FBMSO Officials. Notes and To-Do are
  /// commented out on the web sidebar, so they are not shown here.
  List<DrawerItem> get _drawerItems {
    final p = _perms;
    return [
      if (p.canViewStaffLists)
        DrawerItem(
          icon: Icons.school_outlined,
          title: 'Registered Students',
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
      if (p.canViewAttendanceLogs)
        DrawerItem(
          icon: Icons.history_rounded,
          title: 'Attendance Logs',
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
      if (p.canManageActivities)
        DrawerItem(
          icon: Icons.edit_calendar_rounded,
          title: 'Manage Activities',
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
      if (p.canManageAnnouncements)
        DrawerItem(
          icon: Icons.campaign_outlined,
          title: 'Announcements',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    AnnouncementsManageScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canViewStaffLists)
        DrawerItem(
          icon: Icons.assessment_outlined,
          title: 'Activities Reports',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => ReportsScreen(session: widget.session),
              ),
            );
          },
        ),
      // Accounting block — mirrors the web Cashier sidebar in order:
      // Payment Entry, School Expenses (+reports), Payment Setup (fees),
      // Collection Reports, Ledger, Partial Payments, Payment Activity Log.
      // Admin sees the same set (Accounting allows Admin + Cashier).
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.payments_outlined,
          title: 'Payment Entry',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    PaymentEntryScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.receipt_long_outlined,
          title: 'School Expenses',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => ExpensesScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.summarize_outlined,
          title: 'Expenses Reports',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    ExpensesReportScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.sell_outlined,
          title: 'Fees Setup',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    FeesSetupScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.description_outlined,
          title: 'Collection Reports',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    CollectionReportScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.menu_book_outlined,
          title: 'Ledger',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => LedgerScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.hourglass_bottom_rounded,
          title: 'Partial Payments',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    PartialPaymentsScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canUseAccounting)
        DrawerItem(
          icon: Icons.manage_history_rounded,
          title: 'Payment Activity Log',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) =>
                    PaymentAuditLogScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canManageDepartments)
        DrawerItem(
          icon: Icons.school_outlined,
          title: 'Course',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => DepartmentsScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canViewStaffLists)
        DrawerItem(
          icon: Icons.group_outlined,
          title: 'Sections',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => SectionsScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canManageUsers)
        DrawerItem(
          icon: Icons.manage_accounts_rounded,
          title: 'Admin Accounts',
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
      if (p.canManagePersonnel)
        DrawerItem(
          icon: Icons.people_outline_rounded,
          title: 'FBMSO Officials',
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
    ];
  }

  @override
  Widget build(BuildContext context) {
    final session = widget.session;
    final p = _perms;

    // Bottom nav mirrors each role's web landing pages:
    //   Cashier   → Dashboard + Expenses (web lands on Accounting/Payment)
    //   others    → Dashboard + Activities + Scan
    final destinations = <NavigationDestination>[
      const NavigationDestination(
        icon: Icon(Icons.dashboard_outlined),
        selectedIcon: Icon(Icons.dashboard),
        label: 'Dashboard',
      ),
      if (p.isCashier)
        const NavigationDestination(
          icon: Icon(Icons.receipt_long_outlined),
          selectedIcon: Icon(Icons.receipt_long_rounded),
          label: 'Expenses',
        )
      else ...[
        const NavigationDestination(
          icon: Icon(AppIcons.home_outlined),
          selectedIcon: Icon(AppIcons.home_rounded),
          label: 'Activities',
        ),
        const NavigationDestination(
          icon: Icon(Icons.qr_code_scanner_outlined),
          selectedIcon: Icon(Icons.qr_code_scanner),
          label: 'Scan',
        ),
      ],
    ];

    return Scaffold(
      drawer: AppAppDrawer(
        session: session,
        controller: widget.controller,
        items: _drawerItems,
      ),
      body: Builder(
        builder: (context) {
          final menu = _menuButton(context);
          final tabs = <Widget>[
            DashboardScreen(session: session, menuButton: menu),
            if (p.isCashier)
              ExpensesScreen(session: session, menuButton: menu)
            else ...[
              ActivitiesScreen(session: session, menuButton: menu),
              _ScanPicker(session: session, menuButton: menu),
            ],
          ];
          return tabs[_index.clamp(0, tabs.length - 1)];
        },
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index.clamp(0, destinations.length - 1),
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: destinations,
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
  static const _kLastActivityKey = 'last_scan_activity_id';

  late final AttendanceApi _api;
  List<Activity> _activities = [];
  int? _lastActivityId;
  bool _loading = true;
  bool _posterMode = false;

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
    final pm = await _api.posterMode(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
    );
    final prefs = await SharedPreferences.getInstance();
    if (!mounted) return;

    // Surface the last-used activity first — scanners usually run the same
    // event repeatedly across a session.
    _lastActivityId = prefs.getInt(_kLastActivityKey);
    final open = list.where((a) => a.isOpen).toList();
    final lastIdx =
        open.indexWhere((a) => a.activityId == _lastActivityId);
    if (lastIdx > 0) open.insert(0, open.removeAt(lastIdx));

    setState(() {
      _activities = open;
      _posterMode = pm;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: _posterMode ? 'Poster Mode' : 'Scan',
      showBackButton: false,
      leading: widget.menuButton,
      body: Column(
        children: [
          const SyncStatusBanner(),
          if (_posterMode && !_loading)
            Container(
              margin: const EdgeInsets.fromLTRB(16, 8, 16, 4),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: AppInk.accent.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                    color: AppInk.accent.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.image_rounded,
                      size: 22, color: AppInk.accent),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Poster Mode is ON — tap an activity to display its QR poster for students to scan.',
                      style: TextStyle(
                        fontSize: 12,
                        color: AppInk.accent.withValues(alpha: 0.9),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const ListSkeleton(itemCount: 4)
                  : _activities.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 80),
                            AppEmptyState(
                              icon: _posterMode
                                  ? Icons.image_outlined
                                  : Icons.qr_code_scanner_rounded,
                              title: _posterMode
                                  ? 'No open activities'
                                  : 'No open activities',
                              subtitle: _posterMode
                                  ? 'Open activities will appear here so you can display their QR poster.'
                                  : 'Open activities will appear here so you can start scanning.',
                              tone: AppInk.muted,
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                          itemCount: _activities.length + 1,
                          itemBuilder: (context, i) {
                            if (i == 0) {
                              return AppPageHeader(
                                title: _posterMode
                                    ? 'Poster Mode'
                                    : 'Select Activity',
                                subtitle: _posterMode
                                    ? 'Tap to show the check-in QR poster'
                                    : 'Open activities you can scan for',
                              );
                            }
                            final a = _activities[i - 1];
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: AppCard(
                                radius: 16,
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 14, vertical: 14),
                                onTap: () {
                                  SharedPreferences.getInstance().then(
                                      (p) => p.setInt(_kLastActivityKey,
                                          a.activityId));
                                  if (_posterMode) {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => ActivityPosterScreen(
                                          session: widget.session,
                                          activityId: a.activityId,
                                          activityTitle: a.title,
                                        ),
                                      ),
                                    );
                                  } else {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => ScanScreen(
                                          session: widget.session,
                                          activityId: a.activityId,
                                          activityTitle: a.title,
                                        ),
                                      ),
                                    );
                                  }
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
                                      child: Icon(
                                          _posterMode
                                              ? Icons.qr_code_2_rounded
                                              : Icons.qr_code_scanner_rounded,
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
                                          if (a.activityId ==
                                              _lastActivityId) ...[
                                            const SizedBox(height: 4),
                                            Container(
                                              padding: const EdgeInsets
                                                  .symmetric(
                                                  horizontal: 7,
                                                  vertical: 2),
                                              decoration: BoxDecoration(
                                                color: AppInk.accent
                                                    .withValues(alpha: 0.10),
                                                borderRadius:
                                                    BorderRadius.circular(6),
                                              ),
                                              child: const Text(
                                                'LAST USED',
                                                style: TextStyle(
                                                  fontSize: 9,
                                                  fontWeight:
                                                      FontWeight.w800,
                                                  letterSpacing: 0.6,
                                                  color: AppInk.accent,
                                                ),
                                              ),
                                            ),
                                          ],
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
                          ),
                        ])
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                          itemCount: _activities.length + 1,
                          itemBuilder: (context, i) {
                            if (i == 0) {
                              return const AppPageHeader(
                                title: 'Attendance Logs',
                              );
                            }
                            final a = _activities[i - 1];
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

  bool _exporting = false;

  /// Download the CSV export — same file the web Attendance Logs page's
  /// export button produces.
  Future<void> _exportCsv() async {
    if (_exporting) return;
    setState(() => _exporting = true);
    try {
      final csv = await _api.exportLogsCsv(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: widget.activity.activityId,
      );
      final dir = await getTemporaryDirectory();
      final safe = widget.activity.title
          .replaceAll(RegExp(r'[^A-Za-z0-9_-]+'), '_')
          .replaceAll(RegExp(r'_+'), '_');
      final file = File('${dir.path}/attendance_${safe.isEmpty ? widget.activity.activityId : safe}.csv');
      await file.writeAsString(csv);
      if (!mounted) return;
      await SharePlus.instance.share(
        ShareParams(
          files: [XFile(file.path)],
          subject: 'Attendance — ${widget.activity.title}',
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('Export failed: $e')));
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: widget.activity.title,
      showBackButton: true,
      actions: [
        IconButton(
          tooltip: 'Export CSV',
          onPressed: _exporting ? null : _exportCsv,
          icon: _exporting
              ? const SizedBox(
                  width: 18, height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(Icons.download_rounded),
        ),
      ],
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
                                    to12HourFromDateTime((log['checked_in_at'] ?? '').toString());
                                final checkedOut =
                                    to12HourFromDateTime((log['checked_out_at'] ?? '').toString());
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