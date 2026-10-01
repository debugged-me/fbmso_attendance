import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/time_format.dart';
import '../../../core/widgets/anim_helpers.dart';
import '../../../core/widgets/notification_bell.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../accounting/data/accounting_api.dart';
import '../../accounting/domain/accounting_models.dart';
import '../../auth/domain/app_session.dart';
import '../../auth/domain/staff_permissions.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../attendance/presentation/activity_state_style.dart';
import '../../misc/data/misc_api.dart';
import '../../misc/domain/misc_models.dart';
import '../../student/data/student_api.dart';
import '../../student/domain/student_models.dart';
import 'activity_detail_sheet.dart';
import '../../../core/theme/app_icons.dart';

/// Dashboard: welcome header, announcements feed, activities list.
/// Admin-level roles additionally get the same stat cards and Student
/// Summary breakdowns the web admin dashboard (Page/admin) shows.
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.session, this.menuButton});

  final AppSession session;
  final Widget? menuButton;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late final MiscApi _miscApi;
  late final AttendanceApi _attApi;
  late final StudentApi _studentApi;
  late final AccountingApi _acctApi;
  List<Announcement> _announcements = [];
  List<Activity> _activities = [];
  DashboardStats? _stats;
  FlagStatus? _flag;
  AccountingDashboard? _cashierStats;
  CommitteeDashboard? _committeeStats;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _miscApi = MiscApi();
    _attApi = AttendanceApi();
    _studentApi = StudentApi();
    _acctApi = AccountingApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final perms = StaffPermissions.of(widget.session);
      final ann = _miscApi.announcements(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      // Cashier is denied /activities (web allowlist is accounting-only) —
      // skip the call so their dashboard doesn't trip a 403. Students get no
      // activity list on the web either (activities/index is not on their
      // route allowlist; their dashboard shows announcements only).
      final act = perms.isCashier || perms.isStudent
          ? Future<List<Activity>>.value(const [])
          : _attApi.activities(
              baseUrl: widget.session.baseUrl,
              token: widget.session.token,
            );
      // Stats endpoint is admin-level only; skip the call entirely for
      // students/committee/cashier so they never see a 403.
      final stf = perms.canViewDashboardStats
          ? _miscApi
              .dashboardStats(
                baseUrl: widget.session.baseUrl,
                token: widget.session.token,
              )
              .then<DashboardStats?>((s) => s)
              .catchError((_) => null)
          : Future<DashboardStats?>.value(null);
      // Flagged-account state — students only, same as the web dashboard's
      // "pending concern" warning.
      final flag = perms.isStudent
          ? _studentApi.flagStatus(
              baseUrl: widget.session.baseUrl,
              token: widget.session.token,
            )
          : Future<FlagStatus?>.value(null);
      // Cashier and Auditor share the accounting dashboard. Auditor accounting
      // actions remain read-only in its destination screens.
      final cashier = (perms.isCashier || perms.isAuditor)
          ? _acctApi
              .dashboard(
                baseUrl: widget.session.baseUrl,
                token: widget.session.token,
              )
              .then<AccountingDashboard?>((s) => s)
              .catchError((_) => null)
          : Future<AccountingDashboard?>.value(null);
      // Committee: the web Page::committee scan-ops stats.
      final committee = perms.isCommittee
          ? _attApi
              .committeeDashboard(
                baseUrl: widget.session.baseUrl,
                token: widget.session.token,
              )
              .then<CommitteeDashboard?>((s) => s)
              .catchError((_) => null)
          : Future<CommitteeDashboard?>.value(null);
      final results =
          await Future.wait<Object?>([ann, act, stf, flag, cashier, committee]);
      if (!mounted) return;
      setState(() {
        _announcements = results[0] as List<Announcement>;
        _activities = results[1] as List<Activity>;
        _stats = results[2] as DashboardStats?;
        _flag = results[3] as FlagStatus?;
        _cashierStats = results[4] as AccountingDashboard?;
        _committeeStats = results[5] as CommitteeDashboard?;
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

  @override
  Widget build(BuildContext context) {
    final perms = StaffPermissions.of(widget.session);
    return AppScaffold(
      titleWidget: const SizedBox.shrink(),
      showBackButton: false,
      leading: widget.menuButton,
      actions: const [NotificationBell()],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const ListSkeleton(itemCount: 5)
                  : _error != null
                      ? ListView(
                          children: [
                            const SizedBox(height: 80),
                            AppEmptyState(
                              icon: AppIcons.cloud_off_rounded,
                              title: "Couldn't load the dashboard",
                              subtitle: _error,
                              action: 'Try again',
                              onAction: _load,
                            ),
                          ],
                        )
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                          children: [
                            // ── Greeting ────────────────────────────────
                            FadeSlideIn(
                              child: _WelcomeHeader(session: widget.session),
                            ),
                            const SizedBox(height: 20),

                            // ── Student: digital ID card ────────────────
                            if (perms.isStudent) ...[
                              FadeSlideIn(
                                delay: const Duration(milliseconds: 60),
                                child: _StudentIdCard(session: widget.session),
                              ),
                              const SizedBox(height: 16),
                            ],

                            // ── Flagged account (web parity: the
                            // "pending concern" banner + details modal) ──
                            if (_flag?.isFlagged == true) ...[
                              _FlagBanner(flag: _flag!),
                              const SizedBox(height: 16),
                            ],

                            // ── Admin: student overview ─────────────────
                            if (_stats != null) ...[
                              FadeSlideIn(
                                delay: const Duration(milliseconds: 60),
                                child: _StudentOverviewCard(stats: _stats!),
                              ),
                              const SizedBox(height: 16),
                            ],

                            // ── Cashier / Auditor: collections ─────────
                            if (_cashierStats != null) ...[
                              FadeSlideIn(
                                delay: const Duration(milliseconds: 60),
                                child: _CashierOverviewCard(
                                  stats: _cashierStats!,
                                  isAuditor: perms.isAuditor,
                                ),
                              ),
                              const SizedBox(height: 16),
                            ],

                            // ── Committee: scan operations ─────────────
                            if (_committeeStats != null) ...[
                              FadeSlideIn(
                                delay: const Duration(milliseconds: 60),
                                child: _CommitteeOverviewCard(
                                    stats: _committeeStats!),
                              ),
                              const SizedBox(height: 16),
                            ],

                            // ── Activities ─────────────────────────────
                            if (_activities.isNotEmpty) ...[
                              const SizedBox(height: 8),
                              const _SectionLabel('Activities'),
                              const SizedBox(height: 10),
                              AppCard(
                                padding: EdgeInsets.zero,
                                child: ClipRRect(
                                  borderRadius:
                                      BorderRadius.circular(AppRadius.lg),
                                  child: Column(
                                    children: appRuled(
                                      [
                                        for (final a in _activities.take(5))
                                          ActivityRow(
                                            activity: a,
                                            onTap: () =>
                                                showActivityDetailSheet(
                                                    context, a, widget.session),
                                          ),
                                      ],
                                      indent: 74,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 24),
                            ],

                            // ── Announcements ──────────────────────────
                            const _SectionLabel('Announcements'),
                            const SizedBox(height: 10),
                            if (_announcements.isEmpty)
                              const _EmptyCard(
                                icon: AppIcons.campaign_outlined,
                                message: 'No announcements right now.',
                              )
                            else
                              ..._announcements
                                  .take(3)
                                  .map((a) => _AnnouncementCard(item: a)),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

String _thousands(num v) {
  final neg = v < 0;
  final whole = v.abs().truncate().toString();
  final out = StringBuffer();
  for (var i = 0; i < whole.length; i++) {
    if (i > 0 && (whole.length - i) % 3 == 0) out.write(',');
    out.write(whole[i]);
  }
  return '${neg ? '-' : ''}$out';
}

String _peso(double v) {
  final cents = ((v.abs() * 100).round() % 100).toString().padLeft(2, '0');
  return '₱${_thousands(v.truncate())}.$cents';
}

/// A scan time as "8:23 AM", with "Sep 30" beside it when it was not today.
/// The raw "2026-10-01 08:23:45" took the width the student's name needs.
({String time, String day}) _scanWhen(String raw) {
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
      'Sep', 'Oct', 'Nov', 'Dec'];
  final at = DateTime.tryParse(raw.trim());
  if (at == null) return (time: raw, day: '');
  final now = DateTime.now();
  final today =
      at.year == now.year && at.month == now.month && at.day == now.day;
  return (
    time: to12HourFromDateTime(raw.trim()),
    day: today ? '' : '${months[at.month - 1]} ${at.day}',
  );
}

String _pesoCompact(double v) {
  if (v >= 1000000) return '₱${(v / 1000000).toStringAsFixed(1)}M';
  if (v >= 10000) return '₱${(v / 1000).toStringAsFixed(1)}k';
  return '₱${_thousands(v)}';
}

class _WelcomeHeader extends StatelessWidget {
  const _WelcomeHeader({required this.session});
  final AppSession session;

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
  }

  String _today() {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday',
        'Saturday', 'Sunday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    final now = DateTime.now();
    return '${days[now.weekday - 1]}, ${months[now.month - 1]} ${now.day}';
  }

  @override
  Widget build(BuildContext context) {
    final first = session.firstName.trim().isNotEmpty
        ? session.firstName.trim()
        : session.displayName;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_today(),
                    style: AppType.caption.copyWith(fontSize: 13.5)),
                const SizedBox(height: 4),
                Text('${_greeting()}, $first', style: AppType.title),
              ],
            ),
          ),
          const SizedBox(width: 12),
          AppAvatar(name: session.displayName, url: session.avatar, size: 48),
        ],
      ),
    );
  }
}

/// Digital student ID: who, which number, which term. White card with the
/// app mark's gradient as a thin top edge.
class _StudentIdCard extends StatelessWidget {
  const _StudentIdCard({required this.session});
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    final number =
        session.idNumber.isNotEmpty ? session.idNumber : session.username;
    return AppCard(
      padding: EdgeInsets.zero,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppRadius.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              height: 4,
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    AppTheme.brandCyan,
                    AppTheme.accent,
                    AppTheme.brandViolet,
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 16, 18, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          session.schoolName.isNotEmpty
                              ? session.schoolName
                              : 'Student ID',
                          style: AppType.caption.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      const AppChip(
                        label: 'Student',
                        tone: AppInk.accent,
                        icon: AppIcons.badge_outlined,
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      AppAvatar(
                        name: session.displayName,
                        url: session.avatar,
                        size: 56,
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              session.displayName,
                              style: AppType.headline,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              number,
                              style: AppType.value.copyWith(
                                fontSize: 14,
                                color: AppInk.secondary,
                                letterSpacing: 0.4,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  if (session.activeSy.isNotEmpty ||
                      session.activeSem.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    IntrinsicHeight(
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Expanded(
                            child: _IdField(
                                label: 'School year', value: session.activeSy),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _IdField(
                                label: 'Semester', value: session.activeSem),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _IdField extends StatelessWidget {
  const _IdField({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: AppInk.page,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: AppType.caption.copyWith(fontSize: 12)),
          const SizedBox(height: 2),
          Text(
            value.isEmpty ? '—' : value,
            style: AppType.row.copyWith(fontSize: 14),
          ),
        ],
      ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 2),
      child: Text(text, style: AppType.headline.copyWith(fontSize: 17)),
    );
  }
}

class _EmptyCard extends StatelessWidget {
  const _EmptyCard({required this.icon, required this.message});
  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(18),
      child: Row(
        children: [
          AppIconBox(icon: icon, color: AppInk.muted),
          const SizedBox(width: 12),
          Expanded(
            child: Text(message, style: AppType.rowSub.copyWith(fontSize: 14)),
          ),
        ],
      ),
    );
  }
}

/// The dashboard is the only place an announcement is read in the app, so a
/// long message opens in place instead of stopping at three lines.
class _AnnouncementCard extends StatefulWidget {
  const _AnnouncementCard({required this.item});
  final Announcement item;

  @override
  State<_AnnouncementCard> createState() => _AnnouncementCardState();
}

class _AnnouncementCardState extends State<_AnnouncementCard> {
  static const _previewLines = 3;
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final item = widget.item;
    final messageStyle = AppType.body.copyWith(fontSize: 14, height: 1.45);
    final meta = [item.author, item.datePosted]
        .where((e) => e.trim().isNotEmpty)
        .join(' · ');
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const AppIconBox(icon: AppIcons.megaphone, size: 40),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(item.title, style: AppType.row),
                  const SizedBox(height: 6),
                  LayoutBuilder(
                    builder: (context, constraints) {
                      final painter = TextPainter(
                        text: TextSpan(text: item.message, style: messageStyle),
                        textDirection: TextDirection.ltr,
                        textScaler: MediaQuery.textScalerOf(context),
                        maxLines: _previewLines,
                      )..layout(maxWidth: constraints.maxWidth);
                      final long = painter.didExceedMaxLines;
                      painter.dispose();
                      final open = _expanded || !long;
                      return GestureDetector(
                        onTap: long
                            ? () => setState(() => _expanded = !_expanded)
                            : null,
                        behavior: HitTestBehavior.opaque,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              item.message,
                              style: messageStyle,
                              maxLines: open ? null : _previewLines,
                              overflow: open ? null : TextOverflow.ellipsis,
                            ),
                            if (long)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text(
                                  _expanded ? 'Show less' : 'Read more',
                                  style: AppType.caption.copyWith(
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w600,
                                    color: AppInk.accent,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      );
                    },
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      if (item.audience.isNotEmpty) ...[
                        AppChip(label: item.audience, tone: AppInk.accent),
                        const SizedBox(width: 8),
                      ],
                      Expanded(
                        child: Text(
                          meta,
                          style: AppType.caption,
                        ),
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
  }
}

/// Flagged-account warning — same content as the web student dashboard's
/// amber alert + flag details modal (dashboard_student.php).
class _FlagBanner extends StatelessWidget {
  const _FlagBanner({required this.flag});
  final FlagStatus flag;

  @override
  Widget build(BuildContext context) {
    return AppNotice(
      tone: AppInk.caution,
      title: 'Your account has a pending concern',
      message: 'Settle it with the office that flagged it to avoid holds.',
      action: 'View details',
      onAction: () => _showDetails(context),
    );
  }

  void _showDetails(BuildContext context) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Flagged account'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (final row in [
              ('Reason', flag.reason),
              ('Flagged by', flag.flaggedBy),
              ('Office', flag.office),
              ('School year', flag.sy),
              ('Semester', flag.semester),
            ])
              if (row.$2.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(row.$1, style: AppType.caption),
                      const SizedBox(height: 2),
                      Text(row.$2, style: AppType.row.copyWith(fontSize: 14)),
                    ],
                  ),
                ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }
}

/// Card header: small label with an icon, optional trailing chip.
class _CardHeader extends StatelessWidget {
  const _CardHeader({required this.icon, required this.label, this.chip});
  final IconData icon;
  final String label;
  final String? chip;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        AppIconBox(icon: icon, size: 32, iconSize: 17),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            label,
            style: AppType.caption.copyWith(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: AppInk.secondary,
            ),
          ),
        ),
        if (chip != null && chip!.isNotEmpty)
          AppChip(label: chip!, tone: AppInk.muted),
      ],
    );
  }
}

/// Student overview for admin roles: the head count, year levels as one
/// stacked bar with a legend, and course/section breakdowns behind a tap.
/// Same numbers as the web admin dashboard (Page/admin).
class _StudentOverviewCard extends StatelessWidget {
  const _StudentOverviewCard({required this.stats});
  final DashboardStats stats;

  @override
  Widget build(BuildContext context) {
    final years = stats.yearCards;
    final enrolled = years.fold<int>(0, (s, y) => s + y.count);
    final term = [stats.sy, stats.sem].where((e) => e.isNotEmpty).join(' · ');
    return AppCard(
      padding: const EdgeInsets.fromLTRB(18, 16, 18, 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _CardHeader(
            icon: AppIcons.users_three,
            label: 'Registered students',
          ),
          const SizedBox(height: 14),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(_thousands(stats.registeredStudents),
                  style: AppType.display),
              const SizedBox(width: 10),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 5),
                  child: Text(
                    [
                      '${_thousands(enrolled)} enrolled',
                      if (term.isNotEmpty) term,
                    ].join(' · '),
                    style: AppType.caption.copyWith(fontSize: 13),
                  ),
                ),
              ),
            ],
          ),
          if (years.isNotEmpty && enrolled > 0) ...[
            const SizedBox(height: 16),
            ClipRRect(
              borderRadius: BorderRadius.circular(99),
              child: SizedBox(
                height: 10,
                child: Row(
                  children: [
                    for (var i = 0; i < years.length; i++)
                      if (years[i].count > 0)
                        Expanded(
                          flex: years[i].count,
                          child: Container(
                            margin: EdgeInsets.only(
                                right: i == years.length - 1 ? 0 : 2),
                            color: AppChart.at(i),
                          ),
                        ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 16,
              runSpacing: 8,
              children: [
                for (var i = 0; i < years.length; i++)
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          color: AppChart.at(i),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        years[i].label,
                        style: AppType.caption.copyWith(fontSize: 13),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        _thousands(years[i].count),
                        style: AppType.value.copyWith(fontSize: 13),
                      ),
                    ],
                  ),
              ],
            ),
          ],
          const SizedBox(height: 6),
          Theme(
            data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
            child: ExpansionTile(
              tilePadding: EdgeInsets.zero,
              childrenPadding: const EdgeInsets.only(bottom: 10),
              iconColor: AppInk.accent,
              collapsedIconColor: AppInk.muted,
              title: Text(
                'Breakdowns',
                style: AppType.row.copyWith(
                  fontSize: 14,
                  color: AppInk.accent,
                ),
              ),
              subtitle: const Text(
                'By course, major, year level, sex and section',
                style: AppType.caption,
              ),
              children: [
                _BarBreakdown(title: 'By course', slices: stats.byCourse),
                _BarBreakdown(title: 'By major', slices: stats.byMajor),
                _BarBreakdown(
                    title: 'By year level', slices: stats.byYearLevel),
                _BarBreakdown(title: 'By sex', slices: stats.bySex),
                _BarBreakdown(
                    title: 'By section', slices: stats.bySection, maxRows: 10),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Slim label-bar-count rows for one breakdown. Long tails roll into
/// "Others".
class _BarBreakdown extends StatelessWidget {
  const _BarBreakdown({
    required this.title,
    required this.slices,
    this.maxRows = 6,
  });

  final String title;
  final List<StatSlice> slices;
  final int maxRows;

  @override
  Widget build(BuildContext context) {
    if (slices.isEmpty) return const SizedBox.shrink();

    final sorted = [...slices]..sort((a, b) => b.count.compareTo(a.count));
    final shown = sorted.take(maxRows).toList();
    final rest = sorted.skip(maxRows).fold<int>(0, (s, e) => s + e.count);
    if (rest > 0) shown.add(StatSlice(label: 'Others', count: rest));
    final max = shown.fold<int>(0, (m, e) => e.count > m ? e.count : m);
    final total = shown.fold<int>(0, (s, e) => s + e.count);

    // Section codes fit beside their bar; full course names ("Bachelor of
    // Science in …") do not, and cut to one width they all read the same.
    // Any label too wide for the column puts every label above its bar.
    const labelWidth = 112.0;
    final labelStyle = AppType.caption.copyWith(
      fontSize: 12.5,
      color: AppInk.body,
      fontWeight: FontWeight.w500,
    );
    String labelOf(StatSlice s) => s.label.isEmpty ? 'Not set' : s.label;
    final scaler = MediaQuery.textScalerOf(context);
    final stacked = shown.any((s) {
      final p = TextPainter(
        text: TextSpan(text: labelOf(s), style: labelStyle),
        textDirection: TextDirection.ltr,
        textScaler: scaler,
        maxLines: 1,
      )..layout();
      final width = p.width;
      p.dispose();
      return width > labelWidth;
    });

    Widget bar(int i) => ClipRRect(
          borderRadius: BorderRadius.circular(99),
          child: LinearProgressIndicator(
            value: max > 0 ? shown[i].count / max : 0,
            minHeight: 6,
            backgroundColor: AppInk.subtle,
            valueColor: AlwaysStoppedAnimation(AppChart.at(i)),
          ),
        );
    Widget count(int i) => Text(
          _thousands(shown[i].count),
          textAlign: TextAlign.right,
          style: AppType.value.copyWith(fontSize: 12.5),
        );

    return Padding(
      padding: const EdgeInsets.only(top: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text(title, style: AppType.section)),
              Text('Total ${_thousands(total)}', style: AppType.caption),
            ],
          ),
          const SizedBox(height: 10),
          for (var i = 0; i < shown.length; i++)
            Padding(
              padding: EdgeInsets.only(bottom: stacked ? 12 : 10),
              child: stacked
                  ? Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: Text(labelOf(shown[i]), style: labelStyle),
                            ),
                            const SizedBox(width: 10),
                            count(i),
                          ],
                        ),
                        const SizedBox(height: 6),
                        bar(i),
                      ],
                    )
                  : Row(
                      children: [
                        SizedBox(
                          width: labelWidth,
                          child: Text(labelOf(shown[i]), style: labelStyle),
                        ),
                        Expanded(child: bar(i)),
                        const SizedBox(width: 10),
                        SizedBox(width: 40, child: count(i)),
                      ],
                    ),
            ),
        ],
      ),
    );
  }
}

/// Cashier overview — the web Page::accounting dashboard as one card:
/// today's collection, month/year totals, accounts with a balance, and the
/// latest payments.
class _CashierOverviewCard extends StatelessWidget {
  const _CashierOverviewCard({required this.stats, this.isAuditor = false});
  final AccountingDashboard stats;
  final bool isAuditor;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _CardHeader(
            icon: AppIcons.wallet,
            label: isAuditor
                ? 'Audit snapshot · collections today'
                : 'Collections today',
          ),
          const SizedBox(height: 12),
          Text(_peso(stats.collectionToday), style: AppType.display),
          const SizedBox(height: 16),
          // Equal-height tiles when a label wraps on a narrow phone.
          IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _MiniStat(
                  label: 'This month',
                  value: _pesoCompact(stats.collectionMonth),
                ),
                const SizedBox(width: 8),
                _MiniStat(
                  label: 'This year',
                  value: _pesoCompact(stats.collectionYear),
                ),
                const SizedBox(width: 8),
                _MiniStat(
                  label: 'With balance',
                  value: _thousands(stats.accountsBalance),
                ),
              ],
            ),
          ),
          if (stats.recentPayments.isNotEmpty) ...[
            const SizedBox(height: 18),
            const Text('Recent payments', style: AppType.section),
            const SizedBox(height: 4),
            ...appRuled(
              [
                for (final p in stats.recentPayments.take(5))
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                p.studentName.isNotEmpty
                                    ? p.studentName
                                    : p.studentNumber,
                                style: AppType.row.copyWith(fontSize: 14),
                              ),
                              Text(p.description, style: AppType.rowSub),
                            ],
                          ),
                        ),
                        const SizedBox(width: 10),
                        Text(_peso(p.amount), style: AppType.value),
                      ],
                    ),
                  ),
              ],
              indent: 0,
            ),
          ],
        ],
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  const _MiniStat({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: AppInk.page,
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: AppType.caption.copyWith(fontSize: 12)),
            const Spacer(),
            const SizedBox(height: 2),
            // An amount is never cut: it shrinks to fit the tile.
            FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                value,
                maxLines: 1,
                style: AppType.value.copyWith(fontSize: 15.5),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// One "Recent scans" line: who, which activity, and when.
class _RecentScanRow extends StatelessWidget {
  const _RecentScanRow({required this.scan});
  final Map<String, dynamic> scan;

  @override
  Widget build(BuildContext context) {
    // The API sends '' (not null) for a student with no profile row.
    final rawName = '${scan['student_name'] ?? ''}'.trim();
    final name =
        rawName.isNotEmpty ? rawName : '${scan['student_number'] ?? ''}';
    final activity = '${scan['activity_title'] ?? ''}'.trim();
    final when =
        _scanWhen('${scan['checked_in_at'] ?? scan['scanned_at'] ?? ''}');
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 9),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppAvatar(name: name, size: 32),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: AppType.row.copyWith(fontSize: 14)),
                if (activity.isNotEmpty)
                  Text(activity, style: AppType.rowSub),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(when.time, style: AppType.caption),
              if (when.day.isNotEmpty)
                Text(
                  when.day,
                  style: AppType.caption.copyWith(color: AppInk.faint),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Committee overview — the web Page::committee dashboard: open activities,
/// today's scan count, the 14-day trend as slim bars, and recent scans.
class _CommitteeOverviewCard extends StatelessWidget {
  const _CommitteeOverviewCard({required this.stats});
  final CommitteeDashboard stats;

  @override
  Widget build(BuildContext context) {
    final maxTrend =
        stats.trend.fold<int>(0, (m, t) => t.count > m ? t.count : m);

    return AppCard(
      padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _CardHeader(
            icon: AppIcons.scan,
            label: 'Scans today',
            chip: '${stats.openCount} open · ${stats.totalCount} total',
          ),
          const SizedBox(height: 12),
          Text(_thousands(stats.todayScans), style: AppType.display),
          if (stats.trend.isNotEmpty) ...[
            const SizedBox(height: 16),
            SizedBox(
              height: 56,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  for (var i = 0; i < stats.trend.length; i++)
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 2),
                        child: Container(
                          height: maxTrend > 0
                              ? (stats.trend[i].count / maxTrend) * 50 + 4
                              : 4,
                          decoration: BoxDecoration(
                            color: i == stats.trend.length - 1
                                ? AppInk.accent
                                : stats.trend[i].count > 0
                                    ? AppInk.accent.withValues(alpha: 0.28)
                                    : AppInk.subtle,
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                Text('14 days ago', style: AppType.caption.copyWith(fontSize: 11.5)),
                const Spacer(),
                Text('Today', style: AppType.caption.copyWith(fontSize: 11.5)),
              ],
            ),
          ],
          if (stats.recentScans.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Text('Recent scans', style: AppType.section),
            const SizedBox(height: 4),
            ...appRuled(
              [
                for (final s in stats.recentScans.take(5))
                  _RecentScanRow(scan: s),
              ],
              indent: 42,
            ),
          ],
        ],
      ),
    );
  }
}
