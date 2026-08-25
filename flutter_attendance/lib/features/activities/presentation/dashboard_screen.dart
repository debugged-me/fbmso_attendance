import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/notification_bell.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../../attendance/data/attendance_api.dart';
import '../../attendance/domain/attendance_models.dart';
import '../../misc/data/misc_api.dart';
import '../../misc/domain/misc_models.dart';
import 'activity_detail_sheet.dart';

/// Student dashboard: welcome header, quick stats, announcements feed,
/// and a link to the full activities list.
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
  List<Announcement> _announcements = [];
  List<Activity> _activities = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _miscApi = MiscApi();
    _attApi = AttendanceApi();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final ann = _miscApi.announcements(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      final act = _attApi.activities(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      final results = await Future.wait([ann, act]);
      if (!mounted) return;
      setState(() {
        _announcements = results[0] as List<Announcement>;
        _activities = results[1] as List<Activity>;
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
                  ? const Center(child: CircularProgressIndicator())
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
                        const SizedBox(height: 24),

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

                        // ── Recent activities preview ──────────────────
                        if (_activities.isNotEmpty) ...[
                          const _SectionLabel('Recent Activities'),
                          const SizedBox(height: 10),
                          ..._activities
                              .take(3)
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
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppInk.rule),
      ),
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
    );
  }
}

class _ActivityMiniCard extends StatelessWidget {
  const _ActivityMiniCard({required this.activity, required this.session});
  final Activity activity;
  final AppSession session;

  @override
  Widget build(BuildContext context) {
    final isOpen = activity.isOpen;
    final color = isOpen ? AppInk.positive : AppInk.muted;

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
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 6,
                    height: 6,
                    decoration: BoxDecoration(
                      color: color,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 5),
                  Text(
                    isOpen ? 'Open' : 'Closed',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: color,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 6),
            Icon(Icons.chevron_right_rounded,
                size: 18, color: AppInk.muted),
          ],
        ),
      ),
    );
  }
}
