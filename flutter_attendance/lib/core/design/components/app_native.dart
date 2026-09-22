import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';

/// iOS-style large page header that scrolls with the list content — the big
/// bold title + a live subtitle ("67 sections", "₱1,240 total") sits at the
/// top of the scroll view while the app bar keeps the small centered title.
class AppPageHeader extends StatelessWidget {
  const AppPageHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.padding = const EdgeInsets.fromLTRB(4, 8, 4, 16),
  });

  final String title;
  final String? subtitle;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: padding,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w800,
              color: AppInk.heading,
              height: 1.15,
              letterSpacing: -0.4,
            ),
          ),
          if (subtitle != null && subtitle!.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              subtitle!,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: AppInk.muted,
              ),
            ),
          ],
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
    this.padding = const EdgeInsets.fromLTRB(16, 0, 16, 12),
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
      height: 36,
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
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: selected
              ? AppInk.accent
              : action
                  ? AppInk.accent.withValues(alpha: 0.08)
                  : Colors.white,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(
            color: selected
                ? AppInk.accent
                : action
                    ? AppInk.accent.withValues(alpha: 0.3)
                    : AppInk.rule,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w700,
            color: selected
                ? Colors.white
                : action
                    ? AppInk.accent
                    : AppInk.body,
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
        icon: Icons.edit_outlined,
        label: 'Edit',
        alignment: Alignment.centerLeft,
      ),
      secondaryBackground: _SwipeBackground(
        color: AppInk.critical,
        icon: Icons.delete_outline_rounded,
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
        borderRadius: BorderRadius.circular(16),
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
