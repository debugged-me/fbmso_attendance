import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_icons.dart';

/// Flat presentation primitives.
///
/// These deliberately expose no `border`, `shadow`, `gradient` or `elevation`
/// parameter. Separation comes from [AppRule] and [AppSpace.xxl]; emphasis
/// comes from [AppType].

/// The hairline between two rows. Inset on the left so it starts under the
/// text rather than under the leading icon.
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

/// A titled group of rows. The label is quiet; the content is the point.
///
/// [action] renders a trailing text link on the label line (e.g. "See all").
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
              padded ? AppSpace.lg : 0, AppSpace.sm),
          child: Row(
            children: [
              Expanded(child: Text(title, style: AppType.section)),
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
        dense ? AppSpace.md : 14,
        AppSpace.lg,
        dense ? AppSpace.md : 14,
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
                  const SizedBox(height: 2),
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
            const Icon(AppIcons.chevron_right_rounded,
                size: 18, color: AppInk.faint),
          ],
        ],
      ),
    );

    if (onTap == null) return row;
    return InkWell(onTap: onTap, child: row);
  }
}

/// A headline figure with its label above. The number carries the emphasis
/// instead of a coloured box.
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
        Text(label, style: AppType.caption.copyWith(fontSize: 13)),
        const SizedBox(height: 6),
        Text(
          value,
          style: AppType.display.copyWith(
            fontSize: 28,
            color: valueColor ?? AppInk.heading,
          ),
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

/// A small status pill. The one place a filled shape is always allowed,
/// because the colour *is* the information.
class AppChip extends StatelessWidget {
  const AppChip({
    super.key,
    required this.label,
    required this.tone,
    this.dot = false,
    this.icon,
  });

  final String label;
  final Color tone;

  /// Leading status dot.
  final bool dot;

  /// Leading icon (ignored when [dot] is set).
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final ink = AppInk.onTint(tone);
    return Container(
      padding: EdgeInsets.fromLTRB(dot || icon != null ? 8 : 10, 4, 10, 4),
      decoration: BoxDecoration(
        color: tone.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppRadius.pill),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (dot) ...[
            Container(
              width: 6,
              height: 6,
              decoration: BoxDecoration(color: tone, shape: BoxShape.circle),
            ),
            const SizedBox(width: 6),
          ] else if (icon != null) ...[
            Icon(icon, size: 13, color: ink),
            const SizedBox(width: 4),
          ],
          Text(label, style: AppType.chip.copyWith(color: ink)),
        ],
      ),
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

/// Renders [rows] with a hairline between each: never above the first or
/// below the last.
List<Widget> appRuled(List<Widget> rows, {double indent = AppSpace.lg}) {
  final out = <Widget>[];
  for (var i = 0; i < rows.length; i++) {
    out.add(rows[i]);
    if (i != rows.length - 1) out.add(AppRule(indent: indent));
  }
  return out;
}
