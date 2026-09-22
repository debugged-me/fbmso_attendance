import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
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
      // skip the call so their dashboard doesn't trip a 403.
      final act = perms.isCashier
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
      // Cashier: the web Page::accounting collection stats.
      final cashier = perms.isCashier
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
      title: 'Dashboard',
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
                      ? Center(
                          child: Padding(
                            padding: const EdgeInsets.all(32),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.cloud_off_rounded,
                                    size: 48, color: AppInk.muted),
                                const SizedBox(height: 14),
                                Text(_error!,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
                                        color: AppInk.muted)),
                                const SizedBox(height: 16),
                                AppButton(label: 'Retry', onTap: _load),
                              ],
                            ),
                          ),
                        )
                      : ListView(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                      children: [
                        // ── Welcome header ──────────────────────────────
                        _WelcomeHeader(
                          name: widget.session.displayName,
                        ),
                        const SizedBox(height: 20),

                        // ── Student ID-card hero — the digital student
                        // card: avatar, name, number, SY/sem. ──────────
                        if (perms.isStudent) ...[
                          _StudentIdCard(session: widget.session),
                          const SizedBox(height: 24),
                        ],

                        // ── Flagged account warning (web parity: the
                        // "pending concern" banner + details modal) ─────
                        if (_flag?.isFlagged == true) ...[
                          _FlagBanner(flag: _flag!),
                          const SizedBox(height: 24),
                        ],

                        // ── Admin stats: one glanceable card, breakdowns
                        // tucked behind a tap instead of a wall of KPIs.
                        if (_stats != null) ...[
                          _StudentOverviewCard(stats: _stats!),
                          const SizedBox(height: 24),
                        ],

                        // ── Cashier: the web Page::accounting dashboard —
                        // collections today/month/year + recent payments.
                        if (_cashierStats != null) ...[
                          _CashierOverviewCard(stats: _cashierStats!),
                          const SizedBox(height: 24),
                        ],

                        // ── Committee: the web Page::committee scan-ops
                        // dashboard — today's scans + 14-day trend.
                        if (_committeeStats != null) ...[
                          _CommitteeOverviewCard(stats: _committeeStats!),
                          const SizedBox(height: 24),
                        ],

                        // ── Announcements ──────────────────────────────
                        const _SectionLabel('Announcements'),
                        const SizedBox(height: 10),
                        if (_announcements.isEmpty)
                          const _EmptyCard(
                            icon: Icons.campaign_outlined,
                            message: 'No active announcements.',
                          )
                        else
                          ..._announcements
                              .take(3)
                              .map((a) => _AnnouncementCard(item: a)),
                        const SizedBox(height: 24),

                        // ── Recent activities ─────────────────────────
                        if (_activities.isNotEmpty) ...[
                          const _SectionLabel('Recent Activities'),
                          const SizedBox(height: 10),
                          ..._activities
                              .map((a) => _ActivityMiniCard(
                                    activity: a,
                                    session: widget.session,
                                  )),
                        ],
                      ],
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

class _WelcomeHeader extends StatelessWidget {
  const _WelcomeHeader({required this.name});
  final String name;

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    if (hour < 21) return 'Good evening';
    return 'Good night';
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        const _WavingHand(),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${_greeting()},',
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: AppInk.muted,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                name,
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.w800,
                  color: AppInk.heading,
                  height: 1.15,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Animated waving hand emoji.
class _WavingHand extends StatefulWidget {
  const _WavingHand();

  @override
  State<_WavingHand> createState() => _WavingHandState();
}

class _WavingHandState extends State<_WavingHand>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    // Wave 3 times then stop.
    _ctrl.addStatusListener((status) {
      if (status == AnimationStatus.completed && _ctrl.value < 1.0) {
        // no-op
      }
    });
    _ctrl.repeat(reverse: true);
    // Stop after ~4 seconds.
    Future.delayed(const Duration(seconds: 4), () {
      if (mounted) _ctrl.stop();
    });
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (context, child) {
        return Transform.rotate(
          angle: (_ctrl.value - 0.5) * 0.6,
          child: child,
        );
      },
      child: const Text(
        '👋',
        style: TextStyle(fontSize: 32),
      ),
    );
  }
}

/// Digital student ID card — gradient hero with avatar, name, student
/// number and the active SY/sem. Rendered only on the student dashboard.
class _StudentIdCard extends StatelessWidget {
  const _StudentIdCard({required this.session});
  final AppSession session;

  String get _initials {
    final f = session.firstName.trim();
    final l = session.lastName.trim();
    final a = f.isNotEmpty ? f[0] : '';
    final b = l.isNotEmpty ? l[0] : '';
    return (a + b).isEmpty ? '?' : (a + b).toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF14294B), Color(0xFF1E3FA0), Color(0xFF4A7CF7)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1E3FA0).withValues(alpha: 0.35),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.16),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                      color: Colors.white.withValues(alpha: 0.25)),
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: session.avatar.isNotEmpty
                      ? Image.network(
                          session.avatar,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => _initialsBox(),
                        )
                      : _initialsBox(),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      session.displayName,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                        height: 1.15,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      session.idNumber.isNotEmpty
                          ? session.idNumber
                          : session.username,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Colors.white.withValues(alpha: 0.8),
                        letterSpacing: 0.4,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(Icons.badge_outlined,
                  size: 26, color: Colors.white.withValues(alpha: 0.5)),
            ],
          ),
          const SizedBox(height: 16),
          Container(height: 1, color: Colors.white.withValues(alpha: 0.18)),
          const SizedBox(height: 12),
          Row(
            children: [
              _IdChip(label: 'SY', value: session.activeSy),
              const SizedBox(width: 10),
              _IdChip(label: 'SEM', value: session.activeSem),
              const Spacer(),
              Text(
                'STUDENT',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1.2,
                  color: Colors.white.withValues(alpha: 0.6),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _initialsBox() => Center(
        child: Text(
          _initials,
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: Colors.white,
          ),
        ),
      );
}

class _IdChip extends StatelessWidget {
  const _IdChip({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    if (value.isEmpty) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '$label ',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.5,
              color: Colors.white.withValues(alpha: 0.65),
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
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
    return Text(
      text.toUpperCase(),
      style: const TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w800,
        letterSpacing: 0.8,
        color: AppInk.muted,
      ),
    );
  }
}

class _EmptyCard extends StatelessWidget {
  const _EmptyCard({required this.icon, required this.message});
  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppInk.rule),
      ),
      child: Row(
        children: [
          Icon(icon, size: 24, color: AppInk.muted),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                fontSize: 13,
                color: AppInk.muted,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AnnouncementCard extends StatelessWidget {
  const _AnnouncementCard({required this.item});
  final Announcement item;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppInk.rule),
      ),
      clipBehavior: Clip.antiAlias,
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Accent spine — marks it as a bulletin item.
            Container(width: 4, color: AppInk.accent),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  item.title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppInk.heading,
                    height: 1.3,
                  ),
                ),
              ),
              if (item.audience.isNotEmpty) ...[
                const SizedBox(width: 8),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppInk.accent.withValues(alpha: 0.10),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    item.audience,
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: AppInk.accent,
                    ),
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 8),
          Text(
            item.message,
            style: const TextStyle(
              fontSize: 13,
              color: AppInk.body,
              height: 1.5,
            ),
            maxLines: 3,
            overflow: TextOverflow.ellipsis,
          ),
          if (item.author.isNotEmpty || item.datePosted.isNotEmpty) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                if (item.author.isNotEmpty)
                  Text(
                    item.author,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppInk.muted,
                    ),
                  ),
                if (item.author.isNotEmpty && item.datePosted.isNotEmpty)
                  const Text('  •  ',
                      style: TextStyle(fontSize: 12, color: AppInk.muted)),
                if (item.datePosted.isNotEmpty)
                  Text(
                    item.datePosted,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppInk.muted,
                    ),
                  ),
              ],
            ),
          ],
        ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ActivityMiniCard extends StatelessWidget {
  const _ActivityMiniCard({required this.activity, required this.session});
  final Activity activity;
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => showActivityDetailSheet(context, activity, session),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppInk.rule),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    activity.title,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: AppInk.heading,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 3),
                  Text(
                    activity.activityDate,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppInk.muted,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            ActivityStatePill(activity: activity, dense: true),
            const SizedBox(width: 6),
            Icon(Icons.chevron_right_rounded,
                size: 18, color: AppInk.muted),
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
    return Material(
      color: const Color(0xFFFFF8E1),
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _showDetails(context),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFFFC107)),
          ),
          child: const Row(
            children: [
              Icon(Icons.warning_amber_rounded,
                  size: 22, color: Color(0xFF856404)),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Your account has a pending concern. Tap for details.',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF856404),
                    height: 1.35,
                  ),
                ),
              ),
              Icon(Icons.chevron_right_rounded,
                  size: 18, color: Color(0xFF856404)),
            ],
          ),
        ),
      ),
    );
  }

  void _showDetails(BuildContext context) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Flagged Account Details'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (final row in [
              ('Reason', flag.reason),
              ('Flagged By', flag.flaggedBy),
              ('Office', flag.office),
              ('School Year', flag.sy),
              ('Semester', flag.semester),
            ])
              if (row.$2.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Text.rich(
                    TextSpan(
                      children: [
                        TextSpan(
                          text: '${row.$1}: ',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        TextSpan(text: row.$2),
                      ],
                    ),
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

/// Native-feeling student overview: one card with the headline number and
/// year-level chips up front; course/year/section breakdowns stay tucked
/// behind a tap so the dashboard opens on content, not a wall of KPIs.
/// Same numbers as the web admin dashboard (Page/admin).
class _StudentOverviewCard extends StatelessWidget {
  const _StudentOverviewCard({required this.stats});
  final DashboardStats stats;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.fromLTRB(18, 16, 18, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'REGISTERED STUDENTS',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.8,
                        color: AppInk.muted,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${stats.registeredStudents}',
                      style: const TextStyle(
                        fontSize: 34,
                        fontWeight: FontWeight.w800,
                        color: AppInk.heading,
                        height: 1.05,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: AppInk.accent.withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.groups_rounded,
                        size: 18, color: AppInk.accent),
                    const SizedBox(width: 6),
                    Text(
                      '${stats.yearCards.fold<int>(0, (s, y) => s + y.count)} enrolled',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppInk.accent,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Year-level chips — one glanceable row instead of four cards.
          Row(
            children: [
              for (var i = 0; i < stats.yearCards.length; i++) ...[
                if (i > 0) const SizedBox(width: 8),
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    decoration: BoxDecoration(
                      color: AppChart.at(i)
                          .withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      children: [
                        Text(
                          '${stats.yearCards[i].count}',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color:
                                AppChart.at(i),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          stats.yearCards[i].label,
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            color: AppInk.muted,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),

          // Progressive disclosure — breakdowns on tap.
          Theme(
            data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
            child: ExpansionTile(
              tilePadding: EdgeInsets.zero,
              childrenPadding: const EdgeInsets.only(bottom: 10),
              title: const Text(
                'Breakdowns',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppInk.accent,
                ),
              ),
              subtitle: const Text(
                'By course, year level & section',
                style: TextStyle(fontSize: 11.5, color: AppInk.muted),
              ),
              children: [
                _BarBreakdown(title: 'By Course', slices: stats.byCourse),
                _BarBreakdown(title: 'By Major', slices: stats.byMajor),
                _BarBreakdown(title: 'By Year Level', slices: stats.byYearLevel),
                _BarBreakdown(title: 'By Sex', slices: stats.bySex),
                _BarBreakdown(
                    title: 'By Section', slices: stats.bySection, maxRows: 10),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Slim label-bar-count rows for one breakdown — more compact and readable
/// on a phone than a donut + legend. Long tails roll into "Others".
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

    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title.toUpperCase(),
                  style: const TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.8,
                    color: AppInk.muted,
                  ),
                ),
              ),
              Text(
                'Total: $total',
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: AppInk.accent,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          for (var i = 0; i < shown.length; i++)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  SizedBox(
                    width: 92,
                    child: Text(
                      shown[i].label.isEmpty ? 'Not Set' : shown[i].label,
                      style: const TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: AppInk.heading,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: max > 0 ? shown[i].count / max : 0,
                        minHeight: 8,
                        backgroundColor:
                            AppInk.rule.withValues(alpha: 0.5),
                        valueColor: AlwaysStoppedAnimation(
                          AppChart.at(i),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  SizedBox(
                    width: 34,
                    child: Text(
                      '${shown[i].count}',
                      textAlign: TextAlign.right,
                      style: const TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w700,
                        color: AppInk.muted,
                      ),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

/// Cashier overview — the web Page::accounting dashboard as one glanceable
/// card: today's collection headline, month/year totals, accounts with
/// balance, and the most recent payments. Same numbers, disclosed
/// progressively instead of a KPI grid.
class _CashierOverviewCard extends StatelessWidget {
  const _CashierOverviewCard({required this.stats});
  final AccountingDashboard stats;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1E3FA0), Color(0xFF285CCC), Color(0xFF4A7CF7)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppInk.accent.withValues(alpha: 0.35),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'COLLECTIONS TODAY',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.9,
                    color: Colors.white.withValues(alpha: 0.75),
                  ),
                ),
              ),
              Icon(Icons.account_balance_wallet_outlined,
                  size: 18,
                  color: Colors.white.withValues(alpha: 0.75)),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            '₱${stats.collectionToday.toStringAsFixed(2)}',
            style: const TextStyle(
              fontSize: 34,
              fontWeight: FontWeight.w800,
              color: Colors.white,
              height: 1.05,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _CashierMiniStat(
                label: 'This month',
                value: '₱${_compact(stats.collectionMonth)}',
              ),
              const SizedBox(width: 10),
              _CashierMiniStat(
                label: 'This year',
                value: '₱${_compact(stats.collectionYear)}',
              ),
              const SizedBox(width: 10),
              _CashierMiniStat(
                label: 'With balance',
                value: '${stats.accountsBalance}',
              ),
            ],
          ),
          if (stats.recentPayments.isNotEmpty) ...[
            const SizedBox(height: 16),
            Container(height: 1, color: Colors.white.withValues(alpha: 0.18)),
            const SizedBox(height: 12),
            Text(
              'RECENT PAYMENTS',
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.6,
                color: Colors.white.withValues(alpha: 0.7),
              ),
            ),
            const SizedBox(height: 8),
            ...stats.recentPayments.take(5).map((p) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${p.studentName.isNotEmpty ? p.studentName : p.studentNumber} · ${p.description}',
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w500,
                            color: Colors.white.withValues(alpha: 0.92),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        '₱${p.amount.toStringAsFixed(2)}',
                        style: const TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ],
      ),
    );
  }

  String _compact(double v) {
    if (v >= 1000000) return '${(v / 1000000).toStringAsFixed(1)}M';
    if (v >= 1000) return '${(v / 1000).toStringAsFixed(1)}k';
    return v.toStringAsFixed(0);
  }
}

class _CashierMiniStat extends StatelessWidget {
  const _CashierMiniStat({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.14),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Text(
              label.toUpperCase(),
              style: TextStyle(
                fontSize: 9,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.5,
                color: Colors.white.withValues(alpha: 0.7),
              ),
            ),
            const SizedBox(height: 3),
            Text(
              value,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

/// Committee overview — the web Page::committee dashboard: open activities,
/// today's scan count, and the 14-day scan trend as slim bars. Scan-ops
/// first, since scanning is the committee's job.
class _CommitteeOverviewCard extends StatelessWidget {
  const _CommitteeOverviewCard({required this.stats});
  final CommitteeDashboard stats;

  @override
  Widget build(BuildContext context) {
    final maxTrend = stats.trend.fold<int>(
        0, (m, t) => t.count > m ? t.count : m);

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0B6E4F), Color(0xFF0F8A5F), Color(0xFF22B07E)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F8A5F).withValues(alpha: 0.35),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'SCANS TODAY',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.9,
                    color: Colors.white.withValues(alpha: 0.75),
                  ),
                ),
              ),
              Icon(Icons.qr_code_scanner_rounded,
                  size: 18,
                  color: Colors.white.withValues(alpha: 0.75)),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${stats.todayScans}',
                style: const TextStyle(
                  fontSize: 34,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                  height: 1.05,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(width: 12),
              Padding(
                padding: const EdgeInsets.only(bottom: 5),
                child: Text(
                  '${stats.openCount} open · ${stats.totalCount} activities',
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    color: Colors.white.withValues(alpha: 0.85),
                  ),
                ),
              ),
            ],
          ),
          if (stats.trend.isNotEmpty) ...[
            const SizedBox(height: 16),
            Container(height: 1, color: Colors.white.withValues(alpha: 0.18)),
            const SizedBox(height: 12),
            Text(
              'LAST 14 DAYS',
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.6,
                color: Colors.white.withValues(alpha: 0.7),
              ),
            ),
            const SizedBox(height: 10),
            // Slim bar strip — same trend the web committee dashboard draws.
            SizedBox(
              height: 48,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  for (final t in stats.trend)
                    Expanded(
                      child: Padding(
                        padding:
                            const EdgeInsets.symmetric(horizontal: 1.5),
                        child: Container(
                          height: maxTrend > 0
                              ? (t.count / maxTrend) * 44 + 4
                              : 4,
                          decoration: BoxDecoration(
                            color: t.count > 0
                                ? Colors.white
                                : Colors.white.withValues(alpha: 0.22),
                            borderRadius: BorderRadius.circular(3),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
          if (stats.recentScans.isNotEmpty) ...[
            const SizedBox(height: 14),
            Container(height: 1, color: Colors.white.withValues(alpha: 0.18)),
            const SizedBox(height: 12),
            Text(
              'RECENT SCANS',
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.6,
                color: Colors.white.withValues(alpha: 0.7),
              ),
            ),
            const SizedBox(height: 8),
            ...stats.recentScans.take(5).map((s) => Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Row(
                    children: [
                      Icon(Icons.qr_code_scanner_rounded,
                          size: 14,
                          color: Colors.white.withValues(alpha: 0.8)),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '${s['student_name'] ?? s['student_number'] ?? ''}'
                          '${s['activity_title'] != null ? ' · ${s['activity_title']}' : ''}',
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w500,
                            color: Colors.white.withValues(alpha: 0.92),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        (s['checked_in_at'] ?? s['scanned_at'] ?? '')
                            .toString(),
                        style: TextStyle(
                            fontSize: 11,
                            color: Colors.white.withValues(alpha: 0.65)),
                      ),
                    ],
                  ),
                )),
          ],
        ],
      ),
    );
  }
}
