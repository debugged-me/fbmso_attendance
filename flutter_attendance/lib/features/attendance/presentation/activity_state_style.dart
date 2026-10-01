import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../domain/attendance_models.dart';
import '../../../core/theme/app_icons.dart';

/// How each activity state is drawn. Kept in one place so the student list,
/// the dashboard, the detail sheet and the admin manage screen never disagree
/// about what "Ended" looks like.
///
/// The states come from the server (`activity_state()` in
/// application/helpers/activity_state_helper.php) — this only styles them.
class ActivityStateStyle {
  const ActivityStateStyle._();

  static Color colorFor(Activity a) => colorForState(a.state);

  static Color colorForState(String state) {
    switch (state) {
      case 'open':
        return AppInk.positive;
      case 'scheduled':
        return AppInk.info;
      case 'ended':
        return AppInk.caution;
      case 'closed':
      case 'draft':
      case 'archived':
        return AppInk.muted;
      default:
        return AppInk.muted;
    }
  }

  static IconData iconFor(Activity a) => iconForState(a.state);

  static IconData iconForState(String state) {
    switch (state) {
      case 'open':
        return AppIcons.lock_open_rounded;
      case 'scheduled':
        return AppIcons.schedule_rounded;
      case 'ended':
        return AppIcons.timer_off_rounded;
      case 'draft':
        return AppIcons.edit_note_rounded;
      case 'archived':
        return AppIcons.inventory_2_outlined;
      default:
        return AppIcons.lock_outline_rounded;
    }
  }

  /// Short label for a pill. Falls back to Open/Closed on older servers.
  static String labelFor(Activity a) =>
      a.stateLabel.isNotEmpty ? a.stateLabel : (a.isOpen ? 'Open' : 'Closed');

  /// Sentence explaining a closed activity, safe to show to students.
  static String reasonFor(Activity a) =>
      a.closedReason ?? 'This activity is not accepting check-ins right now.';
}

/// The open/closed pill shown on activity cards and in the detail sheet.
class ActivityStatePill extends StatelessWidget {
  const ActivityStatePill({super.key, required this.activity, this.dense = false});

  final Activity activity;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final color = ActivityStateStyle.colorFor(activity);
    return Container(
      padding: EdgeInsets.fromLTRB(dense ? 7 : 8, dense ? 3 : 4, dense ? 9 : 10,
          dense ? 3 : 4),
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
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 5),
          Text(
            ActivityStateStyle.labelFor(activity),
            style: TextStyle(
              fontSize: dense ? 11.5 : 12,
              fontWeight: FontWeight.w600,
              color: AppInk.onTint(color),
            ),
          ),
        ],
      ),
    );
  }
}

/// Full-width explanation banner for a closed activity. Renders nothing when
/// the activity is open.
class ActivityClosedNotice extends StatelessWidget {
  const ActivityClosedNotice({super.key, required this.activity});

  final Activity activity;

  @override
  Widget build(BuildContext context) {
    if (activity.isOpen) return const SizedBox.shrink();
    return AppNotice(
      message: ActivityStateStyle.reasonFor(activity),
      tone: ActivityStateStyle.colorFor(activity),
      icon: ActivityStateStyle.iconFor(activity),
    );
  }
}

/// A calendar-leaf badge: month over day, for the left edge of a row.
class ActivityDateBadge extends StatelessWidget {
  const ActivityDateBadge({super.key, required this.date, this.highlight = false});

  /// `Y-m-d`.
  final String date;

  /// Tints the badge (today, or an open activity).
  final bool highlight;

  static const _months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL',
      'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

  @override
  Widget build(BuildContext context) {
    final d = DateTime.tryParse(date.trim());
    return Container(
      width: 46,
      height: 50,
      decoration: BoxDecoration(
        color: highlight ? AppInk.accentSoft : AppInk.page,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: highlight ? AppInk.accent.withValues(alpha: 0.18) : AppInk.rule,
        ),
      ),
      child: d == null
          ? const Icon(AppIcons.event_outlined, size: 20, color: AppInk.muted)
          : Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  _months[d.month - 1],
                  style: TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.6,
                    color: highlight ? AppInk.accent : AppInk.muted,
                  ),
                ),
                Text(
                  '${d.day}',
                  style: TextStyle(
                    fontSize: 18,
                    height: 1.15,
                    fontWeight: FontWeight.w700,
                    color: highlight ? AppInk.accentInk : AppInk.heading,
                  ),
                ),
              ],
            ),
    );
  }
}

/// One activity in a list: date badge, title, time and place, state.
class ActivityRow extends StatelessWidget {
  const ActivityRow({
    super.key,
    required this.activity,
    this.onTap,
    this.trailing,
    this.badge,
    this.padding = const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
  });

  final Activity activity;
  final VoidCallback? onTap;

  /// Replaces the state pill + chevron on the right.
  final Widget? trailing;

  /// Small label under the meta line (e.g. "Last used").
  final String? badge;
  final EdgeInsetsGeometry padding;

  static String meta(Activity a) {
    String hm(String t) {
      final m = RegExp(r'^(\d{1,2}):(\d{2})').firstMatch(t.trim());
      if (m == null) return t.trim();
      final h = int.parse(m[1]!);
      final h12 = h % 12 == 0 ? 12 : h % 12;
      return '$h12:${m[2]} ${h < 12 ? 'AM' : 'PM'}';
    }

    final time = [a.startTime, a.endTime]
        .where((t) => t.trim().isNotEmpty)
        .map(hm)
        .join(' – ');
    return [time, a.location.trim()].where((e) => e.isNotEmpty).join(' · ');
  }

  @override
  Widget build(BuildContext context) {
    final sub = meta(activity);
    final row = Padding(
      padding: padding,
      child: Row(
        children: [
          ActivityDateBadge(
            date: activity.activityDate,
            highlight: activity.isOpen,
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(activity.title, style: AppType.row),
                    ),
                    if (trailing == null) ...[
                      const SizedBox(width: 8),
                      ActivityStatePill(activity: activity, dense: true),
                    ],
                  ],
                ),
                if (sub.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(sub, style: AppType.rowSub),
                ],
                if (badge != null) ...[
                  const SizedBox(height: 6),
                  AppChip(label: badge!, tone: AppInk.accent),
                ],
              ],
            ),
          ),
          if (trailing != null) ...[
            const SizedBox(width: 10),
            trailing!,
          ],
        ],
      ),
    );
    if (onTap == null) return row;
    return InkWell(onTap: onTap, child: row);
  }
}
