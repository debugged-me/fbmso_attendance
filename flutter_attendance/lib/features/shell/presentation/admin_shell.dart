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
import '../../attendance/presentation/activity_state_style.dart';
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
  /// roles like Committee, Cashier, and Auditor only see the modules their web
  /// allowlist grants them, instead of a drawer full of 403s.
  StaffPermissions get _perms => StaffPermissions.of(widget.session);

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
          group: 'Attendance',
          icon: AppIcons.school_outlined,
          title: 'Registered students',
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
          group: 'Attendance',
          icon: AppIcons.history_rounded,
          title: 'Attendance logs',
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
          group: 'Attendance',
          icon: AppIcons.edit_calendar_rounded,
          title: 'Manage activities',
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
          group: 'Updates & reports',
          icon: AppIcons.campaign_outlined,
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
          group: 'Updates & reports',
          icon: AppIcons.assessment_outlined,
          title: 'Activity reports',
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
      // Auditor sees the same records with all mutations removed.
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.payments_outlined,
          title: p.isAuditor ? 'Payment records' : 'Payment entry',
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
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.receipt_long_outlined,
          title: 'School expenses',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => ExpensesScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.summarize_outlined,
          title: 'Expense reports',
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
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.sell_outlined,
          title: 'Fees setup',
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
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.description_outlined,
          title: 'Collection reports',
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
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.menu_book_outlined,
          title: p.isAuditor ? 'Cash inflow & outflow' : 'Ledger',
          onTap: (ctx) {
            Navigator.of(ctx).pop();
            Navigator.of(ctx).push(
              MaterialPageRoute(
                builder: (_) => LedgerScreen(session: widget.session),
              ),
            );
          },
        ),
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.hourglass_bottom_rounded,
          title: 'Partial payments',
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
      if (p.canViewAccounting)
        DrawerItem(
          group: 'Accounting',
          icon: AppIcons.manage_history_rounded,
          title: 'Payment activity log',
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
      if (p.canViewDepartments)
        DrawerItem(
          group: 'Manage',
          icon: AppIcons.school_outlined,
          title: 'Courses',
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
          group: 'Manage',
          icon: AppIcons.group_outlined,
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
          group: 'Manage',
          icon: AppIcons.manage_accounts_rounded,
          title: 'Admin accounts',
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
          group: 'Manage',
          icon: AppIcons.people_outline_rounded,
          title: 'FBMSO officials',
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
    //   Cashier → Dashboard + Expenses
    //   Auditor → Dashboard + Activities (no scanner)
    //   others  → Dashboard + Activities + Scan when permitted
    final destinations = <AppNavItem>[
      const AppNavItem(
        icon: AppIcons.house,
        selectedIcon: AppIcons.house_fill,
        label: 'Home',
      ),
      if (p.isCashier)
        const AppNavItem(
          icon: AppIcons.receipt_long_outlined,
          selectedIcon: AppIcons.receipt_fill,
          label: 'Expenses',
        )
      else ...[
        const AppNavItem(
          icon: AppIcons.calendar_blank,
          selectedIcon: AppIcons.calendar_fill,
          label: 'Activities',
        ),
        if (p.canScan)
          const AppNavItem(
            icon: AppIcons.scan,
            label: 'Scan',
            prominent: true,
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
              if (p.canScan)
                _ScanPicker(session: session, menuButton: menu),
            ],
          ];
          return tabs[_index.clamp(0, tabs.length - 1)];
        },
      ),
      bottomNavigationBar: AppBottomNav(
        currentIndex: _index.clamp(0, destinations.length - 1),
        onSelect: (i) => setState(() => _index = i),
        items: destinations,
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
      titleWidget: const SizedBox.shrink(),
      showBackButton: false,
      leading: widget.menuButton,
      body: Column(
        children: [
          const SyncStatusBanner(),
          if (_posterMode && !_loading)
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 8, 16, 4),
              child: AppNotice(
                tone: AppInk.accent,
                icon: AppIcons.image_rounded,
                title: 'Poster mode is on',
                message: 'Tap an activity to show its check-in QR poster for '
                    'students to scan.',
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
                                  ? AppIcons.image_outlined
                                  : AppIcons.qr_code_scanner_rounded,
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
                                    ? 'Poster mode'
                                    : 'Start scanning',
                                icon: _posterMode
                                    ? AppIcons.qr_code_2_rounded
                                    : AppIcons.qr_code_scanner_rounded,
                                subtitle: _posterMode
                                    ? 'Pick an activity to show its QR poster.'
                                    : 'Pick an open activity. Scans work offline.',
                              );
                            }
                            final a = _activities[i - 1];
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: AppCard(
                                padding: EdgeInsets.zero,
                                child: ClipRRect(
                                  borderRadius:
                                      BorderRadius.circular(AppRadius.lg),
                                  child: ActivityRow(
                                    activity: a,
                                    badge: a.activityId == _lastActivityId
                                        ? 'Last used'
                                        : null,
                                    trailing: AppCircleButton(
                                      icon: _posterMode
                                          ? AppIcons.qr_code_2
                                          : AppIcons.scan,
                                      tooltip: _posterMode
                                          ? 'Show poster'
                                          : 'Scan',
                                      background: AppInk.accentSoft,
                                      color: AppInk.accentInk,
                                      onTap: () => _open(a),
                                    ),
                                    onTap: () => _open(a),
                                  ),
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

  void _open(Activity a) {
    SharedPreferences.getInstance()
        .then((p) => p.setInt(_kLastActivityKey, a.activityId));
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _posterMode
            ? ActivityPosterScreen(
                session: widget.session,
                activityId: a.activityId,
                activityTitle: a.title,
              )
            : ScanScreen(
                session: widget.session,
                activityId: a.activityId,
                activityTitle: a.title,
              ),
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
      titleWidget: const SizedBox.shrink(),
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const ListSkeleton(itemCount: 6)
                  : _activities.isEmpty
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          const AppEmptyState(
                            icon: AppIcons.history_rounded,
                            title: 'No activities',
                          ),
                        ])
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                          itemCount: _activities.length + 1,
                          itemBuilder: (context, i) {
                            if (i == 0) {
                              return const AppPageHeader(
                                title: 'Attendance logs',
                                subtitle: 'Choose an activity to see who checked in.',
                              );
                            }
                            final a = _activities[i - 1];
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: AppCard(
                                padding: EdgeInsets.zero,
                                child: ClipRRect(
                                  borderRadius:
                                      BorderRadius.circular(AppRadius.lg),
                                  child: ActivityRow(
                                    activity: a,
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
                                  ),
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
      titleWidget: const SizedBox.shrink(),
      showBackButton: true,
      actions: [
        IconButton(
          tooltip: 'Export CSV',
          onPressed: _exporting ? null : _exportCsv,
          icon: _exporting
              ? const SizedBox(
                  width: 18, height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(AppIcons.download_rounded),
        ),
      ],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: AppSearchField(
              controller: _searchController,
              hint: 'Search name or student ID',
              onChanged: _onSearchChanged,
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const ListSkeleton(itemCount: 6)
                  : _error != null
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          AppEmptyState(
                            icon: AppIcons.cloud_off_rounded,
                            title: 'Failed to load',
                            subtitle: _error,
                            action: 'Retry',
                            onAction: _load,
                          ),
                        ])
                      : _logs.isEmpty
                          ? ListView(
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              children: [
                                AppPageHeader(title: widget.activity.title),
                                const SizedBox(height: 48),
                                const AppEmptyState(
                                  icon: AppIcons.history_rounded,
                                  title: 'No attendance records',
                                ),
                              ],
                            )
                          : ListView.builder(
                              controller: _scrollController,
                              padding:
                                  const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount:
                                  _logs.length + 1 + (_loadingMore ? 1 : 0),
                              itemBuilder: (context, i) {
                                if (i == 0) {
                                  return AppPageHeader(
                                    title: widget.activity.title,
                                    subtitle:
                                        'Showing ${_logs.length} of $_total',
                                  );
                                }
                                i -= 1;
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
                                    padding: const EdgeInsets.fromLTRB(
                                        14, 12, 14, 12),
                                    child: Row(
                                      children: [
                                        AppAvatar(
                                          name: name.isEmpty ? studentNo : name,
                                          size: 40,
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                name.isEmpty ? studentNo : name,
                                                style: AppType.row,
                                              ),
                                              const SizedBox(height: 2),
                                              Text(
                                                [
                                                  studentNo,
                                                  if (sessionLabel != '—')
                                                    sessionLabel,
                                                  if (source.isNotEmpty)
                                                    source.toUpperCase(),
                                                ].join(' · '),
                                                style: AppType.rowSub,
                                              ),
                                            ],
                                          ),
                                        ),
                                        const SizedBox(width: 10),
                                        Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.end,
                                          children: [
                                            _TimeMark(
                                              icon: AppIcons.login_rounded,
                                              tone: AppInk.positive,
                                              time: checkedIn,
                                            ),
                                            if (checkedOut.isNotEmpty) ...[
                                              const SizedBox(height: 4),
                                              _TimeMark(
                                                icon: AppIcons.logout_rounded,
                                                tone: AppInk.muted,
                                                time: checkedOut,
                                              ),
                                            ],
                                          ],
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

/// A check-in/out time with its direction icon.
class _TimeMark extends StatelessWidget {
  const _TimeMark({required this.icon, required this.tone, required this.time});

  final IconData icon;
  final Color tone;
  final String time;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: tone),
        const SizedBox(width: 4),
        Text(
          time,
          style: AppType.caption.copyWith(
            color: AppInk.body,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}
