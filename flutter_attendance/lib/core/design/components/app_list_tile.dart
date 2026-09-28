import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_icons.dart';

/// A list row: optional leading widget, title, subtitle, trailing widget and
/// a soft pressed highlight.
class AppListTile extends StatelessWidget {
  const AppListTile({
    super.key,
    required this.title,
    this.subtitle,
    this.leading,
    this.trailing,
    this.onTap,
    this.padding = const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
  });

  final String title;
  final String? subtitle;
  final Widget? leading;
  final Widget? trailing;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    final content = Padding(
      padding: padding,
      child: Row(
        children: [
          if (leading != null) ...[
            leading!,
            const SizedBox(width: 14),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(title, style: AppType.row),
                if (subtitle != null && subtitle!.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(subtitle!, style: AppType.rowSub),
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

    if (onTap == null) return content;
    return _TileTap(onTap: onTap, child: content);
  }
}

/// A leading icon on a tinted rounded square. Use as [AppListTile.leading].
class AppIconBox extends StatelessWidget {
  const AppIconBox({
    super.key,
    required this.icon,
    this.color = AppInk.accent,
    this.size = 38,
    this.iconSize = 20,
  });

  final IconData icon;
  final Color color;
  final double size;
  final double iconSize;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(size * 0.32),
      ),
      child: Icon(icon, size: iconSize, color: color),
    );
  }
}

/// A trailing chevron for tappable rows.
class AppChevron extends StatelessWidget {
  const AppChevron({super.key, this.color});

  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Icon(
      AppIcons.chevron_right_rounded,
      size: 18,
      color: color ?? AppInk.faint,
    );
  }
}

class _TileTap extends StatefulWidget {
  const _TileTap({required this.onTap, required this.child});
  final VoidCallback? onTap;
  final Widget child;

  @override
  State<_TileTap> createState() => _TileTapState();
}

class _TileTapState extends State<_TileTap> {
  bool _pressed = false;

  void _set(bool v) {
    if (_pressed != v) setState(() => _pressed = v);
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => _set(true),
      onTapUp: (_) => _set(false),
      onTapCancel: () => _set(false),
      onTap: widget.onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: AppMotion.fast,
        color: _pressed ? AppInk.page : Colors.transparent,
        child: widget.child,
      ),
    );
  }
}
