import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_icons.dart';

/// iOS-style large page header that scrolls with the list content — the big
/// bold title + a live subtitle ("67 sections", "₱1,240 total") sits at the
/// top of the scroll view while the app bar keeps the small centered title.
class AppPageHeader extends StatelessWidget {
  const AppPageHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.icon,
    this.iconColor,
    this.padding = const EdgeInsets.fromLTRB(4, 8, 4, 16),
  });

  final String title;
  final String? subtitle;

  /// Optional leading icon orb — a tinted squircle badge beside the title,
  /// the fintech-style section marker used across the app.
  final IconData? icon;
  final Color? iconColor;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    final ic = iconColor ?? AppInk.accent;
    return Padding(
      padding: padding,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          if (icon != null) ...[
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: ic.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: ic, size: 22),
            ),
            const SizedBox(width: 14),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppType.title),
                if (subtitle != null && subtitle!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    subtitle!,
                    style: AppType.body.copyWith(
                      fontSize: 14,
                      color: AppInk.muted,
                      height: 1.35,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Horizontally scrolling filter chips — replaces cryptic app-bar icons for
/// category/status filtering. Includes an optional trailing action chip
/// (e.g. "Manage") that opens the management sheet.
class AppFilterChips extends StatelessWidget {
  const AppFilterChips({
    super.key,
    required this.labels,
    required this.selected,
    required this.onSelected,
    this.manageLabel,
    this.onManage,
    this.padding = const EdgeInsets.fromLTRB(16, 4, 16, 8),
  });

  /// Chip labels; index 0 is conventionally "All".
  final List<String> labels;
  final int selected;
  final ValueChanged<int> onSelected;

  /// When set, a tonal action chip is appended after the filter chips.
  final String? manageLabel;
  final VoidCallback? onManage;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 46,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: padding,
        children: [
          for (var i = 0; i < labels.length; i++) ...[
            _chip(
              label: labels[i],
              selected: i == selected,
              onTap: () => onSelected(i),
            ),
            const SizedBox(width: 8),
          ],
          if (manageLabel != null && onManage != null)
            _chip(
              label: manageLabel!,
              selected: false,
              action: true,
              onTap: onManage!,
            ),
        ],
      ),
    );
  }

  Widget _chip({
    required String label,
    required bool selected,
    required VoidCallback onTap,
    bool action = false,
  }) {
    final Color bg = selected
        ? AppInk.heading
        : action
            ? AppInk.accentSoft
            : Colors.white;
    final Color fg = selected
        ? Colors.white
        : action
            ? AppInk.accentInk
            : AppInk.body;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: AppMotion.base,
        curve: AppMotion.ease,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(AppRadius.pill),
          border: Border.all(
            color: selected || action ? bg : AppInk.rule,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: fg,
          ),
        ),
      ),
    );
  }
}

/// iOS-style swipe actions for list rows.
///
/// Swipe right reveals the edit affordance (accent) and fires [onEdit]
/// without dismissing. Swipe left reveals delete (critical); the row is
/// dismissed only if [confirmDelete] resolves true, then [onDeleted] runs.
class AppSwipeActions extends StatelessWidget {
  const AppSwipeActions({
    super.key,
    required this.dismissKey,
    required this.child,
    this.onEdit,
    this.confirmDelete,
    this.onDeleted,
  });

  final Key dismissKey;
  final Widget child;

  /// Called on a right-swipe (edit). The row snaps back afterwards.
  final VoidCallback? onEdit;

  /// Called on a left-swipe to confirm; return true to dismiss the row.
  final Future<bool> Function()? confirmDelete;

  /// Called after a confirmed dismiss — run the actual delete here.
  final VoidCallback? onDeleted;

  @override
  Widget build(BuildContext context) {
    final canDelete = confirmDelete != null && onDeleted != null;
    final direction = onEdit != null && canDelete
        ? DismissDirection.horizontal
        : onEdit != null
            ? DismissDirection.startToEnd
            : canDelete
                ? DismissDirection.endToStart
                : DismissDirection.none;

    return Dismissible(
      key: dismissKey,
      direction: direction,
      confirmDismiss: (dir) async {
        if (dir == DismissDirection.startToEnd) {
          onEdit?.call();
          return false;
        }
        return await confirmDelete?.call() ?? false;
      },
      onDismissed: (_) => onDeleted?.call(),
      background: _SwipeBackground(
        color: AppInk.accent,
        icon: AppIcons.edit_outlined,
        label: 'Edit',
        alignment: Alignment.centerLeft,
      ),
      secondaryBackground: _SwipeBackground(
        color: AppInk.critical,
        icon: AppIcons.delete_outline_rounded,
        label: 'Delete',
        alignment: Alignment.centerRight,
      ),
      child: child,
    );
  }
}

class _SwipeBackground extends StatelessWidget {
  const _SwipeBackground({
    required this.color,
    required this.icon,
    required this.label,
    required this.alignment,
  });

  final Color color;
  final IconData icon;
  final String label;
  final Alignment alignment;

  @override
  Widget build(BuildContext context) {
    return Container(
      alignment: alignment,
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 20),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white, size: 20),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}
