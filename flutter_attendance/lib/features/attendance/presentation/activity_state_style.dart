import 'package:flutter/material.dart';

import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_theme.dart';
import '../domain/attendance_models.dart';

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
        return AppTheme.success;
      case 'scheduled':
        return AppTheme.info;
      case 'ended':
        return AppTheme.warning;
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
        return Icons.lock_open_rounded;
      case 'scheduled':
        return Icons.schedule_rounded;
      case 'ended':
        return Icons.timer_off_rounded;
      case 'draft':
        return Icons.edit_note_rounded;
      case 'archived':
        return Icons.inventory_2_outlined;
      default:
        return Icons.lock_outline_rounded;
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
      padding: EdgeInsets.symmetric(horizontal: dense ? 8 : 10, vertical: dense ? 4 : 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
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
              fontSize: dense ? 10 : 11,
              fontWeight: FontWeight.w700,
              color: color,
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

    final color = ActivityStateStyle.colorFor(activity);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(ActivityStateStyle.iconFor(activity), size: 18, color: color),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              ActivityStateStyle.reasonFor(activity),
              style: TextStyle(
                fontSize: 12.5,
                height: 1.35,
                fontWeight: FontWeight.w500,
                color: AppInk.body,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
