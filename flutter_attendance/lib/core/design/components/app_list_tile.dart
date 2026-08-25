import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_theme.dart';

/// A clean, minimal list tile with optional leading icon, title, subtitle,
/// trailing widget, and tap feedback.
///
/// This replaces the 300+ inline Row+Column+Container patterns across the
/// app with a single, consistent list item.
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
            const SizedBox(width: 12),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontFamily: AppTheme.fontFamily,
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: AppInk.heading,
                    height: 1.3,
                  ),
                ),
                if (subtitle != null && subtitle!.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(
                    subtitle!,
                    style: TextStyle(
                      fontFamily: AppTheme.fontFamily,
                      fontSize: 13,
                      fontWeight: FontWeight.w500,
                      color: AppInk.muted,
                      height: 1.35,
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (trailing != null) ...[
            const SizedBox(width: 8),
            trailing!,
          ],
        ],
      ),
    );

    if (onTap == null) return content;

    return _TileTap(onTap: onTap, child: content);
  }
}

/// A leading icon in a rounded square container. Use as [AppListTile.leading].
class AppIconBox extends StatelessWidget {
  const AppIconBox({
    super.key,
    required this.icon,
    this.color = AppInk.accent,
    this.size = 36,
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
        borderRadius: BorderRadius.circular(size * 0.3),
      ),
      child: Icon(icon, size: iconSize, color: color),
    );
  }
}

/// A trailing chevron — use as [AppListTile.trailing] for tappable items.
class AppChevron extends StatelessWidget {
  const AppChevron({super.key, this.color});

  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Icon(
      Icons.chevron_right_rounded,
      size: 22,
      color: color ?? const Color(0xFFCBD5E1),
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

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: widget.onTap != null ? (_) => setState(() => _pressed = true) : null,
      onTapUp: widget.onTap != null ? (_) => setState(() => _pressed = false) : null,
      onTapCancel: widget.onTap != null ? () => setState(() => _pressed = false) : null,
      onTap: widget.onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 80),
        color: _pressed ? const Color(0xFFF8FAFC) : Colors.transparent,
        child: widget.child,
      ),
    );
  }
}
