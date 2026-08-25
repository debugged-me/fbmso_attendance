import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';

/// Flat presentation primitives.
///
/// These deliberately expose no `border`, `shadow`, `gradient` or `elevation`
/// parameter. Separation comes from [AppRule] and [AppSpace.xxl]; emphasis
/// comes from [AppType]. A screen built out of these cannot drift back into
/// nested boxes without importing Material's Card directly.

/// The hairline between two rows. Inset on the left so it starts under the
/// text rather than under the leading icon — that alignment is what makes a
/// list read as one group instead of a stack of separate items.
class AppRule extends StatelessWidget {
  const AppRule({super.key, this.indent = 0});

  final double indent;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(left: indent),
      child: const SizedBox(
        height: 1,
        width: double.infinity,
        child: ColoredBox(color: AppInk.rule),
      ),
    );
  }
}

/// A titled group of rows. The header is quiet; the content is the point.
///
/// [action] renders a trailing text button on the header line (e.g. "See all").
class AppSection extends StatelessWidget {
  const AppSection({
    super.key,
    required this.title,
    required this.children,
    this.action,
    this.onActionTap,
    this.padded = true,
  });

  final String title;
  final List<Widget> children;
  final String? action;
  final VoidCallback? onActionTap;

  /// Whether to apply the screen gutter. Off when the parent already pads.
  final bool padded;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: EdgeInsets.fromLTRB(padded ? AppSpace.lg : 0, 0,
              padded ? AppSpace.lg : 0, AppSpace.md),
          child: Row(
            children: [
              Expanded(child: Text(title.toUpperCase(), style: AppType.section)),
              if (action != null)
                GestureDetector(
                  onTap: onActionTap,
                  behavior: HitTestBehavior.opaque,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: AppSpace.sm,
                      vertical: AppSpace.xs,
                    ),
                    child: Text(
                      action!,
                      style: AppType.section.copyWith(color: AppInk.accent),
                    ),
                  ),
                ),
            ],
          ),
        ),
        ...children,
      ],
    );
  }
}

/// A single flat row: optional leading icon, title, optional subtitle,
/// optional right-hand value, optional chevron.
///
/// This is the widget that replaces the 315 `Card(` instances — one row of
/// information, no container of its own.
class AppRow extends StatelessWidget {
  const AppRow({
    super.key,
    required this.title,
    this.subtitle,
    this.value,
    this.valueColor,
    this.icon,
    this.iconColor,
    this.onTap,
    this.dense = false,
  });

  final String title;
  final String? subtitle;
  final String? value;
  final Color? valueColor;
  final IconData? icon;
  final Color? iconColor;
  final VoidCallback? onTap;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final row = Padding(
      padding: EdgeInsets.fromLTRB(
        AppSpace.lg,
        dense ? AppSpace.md : AppSpace.lg,
        AppSpace.lg,
        dense ? AppSpace.md : AppSpace.lg,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 20, color: iconColor ?? AppInk.muted),
            const SizedBox(width: AppSpace.md),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(title, style: AppType.row),
                if (subtitle != null && subtitle!.isNotEmpty) ...[
                  const SizedBox(height: AppSpace.xs),
                  Text(subtitle!, style: AppType.rowSub),
                ],
              ],
            ),
          ),
          if (value != null && value!.isNotEmpty) ...[
            const SizedBox(width: AppSpace.md),
            Text(
              value!,
              style: valueColor == null
                  ? AppType.value
                  : AppType.value.copyWith(color: valueColor),
            ),
          ],
          if (onTap != null) ...[
            const SizedBox(width: AppSpace.xs),
            const Icon(Icons.chevron_right_rounded,
                size: 20, color: AppInk.muted),
          ],
        ],
      ),
    );

    if (onTap == null) return row;
    return InkWell(onTap: onTap, child: row);
  }
}

/// A headline figure with its label underneath. Used where a stat card used
/// to be — the number carries the emphasis instead of a coloured box.
class AppStat extends StatelessWidget {
  const AppStat({
    super.key,
    required this.label,
    required this.value,
    this.valueColor,
    this.caption,
    this.onTap,
  });

  final String label;
  final String value;
  final Color? valueColor;
  final String? caption;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final content = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(label.toUpperCase(), style: AppType.section),
        const SizedBox(height: AppSpace.sm),
        Text(
          value,
          style: valueColor == null
              ? AppType.display
              : AppType.display.copyWith(color: valueColor),
        ),
        if (caption != null && caption!.isNotEmpty) ...[
          const SizedBox(height: AppSpace.xs),
          Text(caption!, style: AppType.rowSub),
        ],
      ],
    );

    if (onTap == null) return content;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: content,
    );
  }
}

/// A small status pill. The one place a filled shape is still allowed,
/// because the colour *is* the information.
class AppChip extends StatelessWidget {
  const AppChip({super.key, required this.label, required this.tone});

  final String label;
  final Color tone;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpace.sm,
        vertical: AppSpace.xs,
      ),
      decoration: BoxDecoration(
        color: tone.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      child: Text(label, style: AppType.chip.copyWith(color: tone)),
    );
  }
}

/// Vertical separation between two sections.
class AppGap extends StatelessWidget {
  const AppGap({super.key, this.height = AppSpace.xxl});

  final double height;

  @override
  Widget build(BuildContext context) => SizedBox(height: height);
}

/// Renders [rows] with a hairline between each — never above the first or
/// below the last. That "inner rules only" detail is most of what separates a
/// tidy list from a noisy one.
List<Widget> appRuled(List<Widget> rows, {double indent = AppSpace.lg}) {
  final out = <Widget>[];
  for (var i = 0; i < rows.length; i++) {
    out.add(rows[i]);
    if (i != rows.length - 1) out.add(AppRule(indent: indent));
  }
  return out;
}
