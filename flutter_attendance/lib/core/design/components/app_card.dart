import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import 'app_squircle.dart';

/// A clean, minimal card with a hairline border and no shadow.
///
/// This is the card for the clean & minimal style. It uses a 1px border
/// instead of elevation, which looks crisper and more modern on mobile.
/// If you need a shadow (e.g. floating action area), use [AppCard.elevated].
class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.onTap,
    this.padding = const EdgeInsets.all(16),
    this.radius = 16,
    this.borderColor,
    this.background = Colors.white,
    this.margin,
    this.elevated = false,
  });

  /// Card with a subtle shadow — for floating elements, hero stats, or
  /// when the card sits on a white page and needs separation.
  const AppCard.elevated({
    super.key,
    required this.child,
    this.onTap,
    this.padding = const EdgeInsets.all(16),
    this.radius = 16,
    this.borderColor,
    this.background = Colors.white,
    this.margin,
  }) : elevated = true;

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;
  final double radius;
  final Color? borderColor;
  final Color background;
  final EdgeInsetsGeometry? margin;
  final bool elevated;

  @override
  Widget build(BuildContext context) {
    final shape = SquircleBorder(
      radius: radius,
      side: BorderSide(
        color: borderColor ?? AppInk.rule,
        width: 1,
      ),
    );

    final decoration = elevated
        ? ShapeDecoration(
            color: background,
            shape: shape,
            shadows: [
              BoxShadow(
                color: const Color(0xFF0F172A).withValues(alpha: 0.04),
                blurRadius: 12,
                offset: const Offset(0, 2),
              ),
            ],
          )
        : ShapeDecoration(color: background, shape: shape);

    Widget card = DecoratedBox(
      decoration: decoration,
      child: Padding(padding: padding, child: child),
    );

    if (margin != null) {
      card = Padding(padding: margin!, child: card);
    }

    if (onTap != null) {
      return _TapFeedback(onTap: onTap, child: card);
    }

    return card;
  }
}

/// A group of cards stacked vertically with consistent spacing.
/// Replaces manual `SizedBox(height: X)` between every card.
class AppCardStack extends StatelessWidget {
  const AppCardStack({
    super.key,
    required this.children,
    this.spacing = 10,
    this.padding,
  });

  final List<Widget> children;
  final double spacing;
  final EdgeInsetsGeometry? padding;

  @override
  Widget build(BuildContext context) {
    final items = <Widget>[];
    for (var i = 0; i < children.length; i++) {
      items.add(children[i]);
      if (i < children.length - 1) {
        items.add(SizedBox(height: spacing));
      }
    }

    return padding != null
        ? Padding(padding: padding!, child: Column(children: items))
        : Column(children: items);
  }
}

/// Tap feedback without ripple — opacity dip, iOS-style.
class _TapFeedback extends StatefulWidget {
  const _TapFeedback({required this.onTap, required this.child});
  final VoidCallback? onTap;
  final Widget child;

  @override
  State<_TapFeedback> createState() => _TapFeedbackState();
}

class _TapFeedbackState extends State<_TapFeedback> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: widget.onTap != null ? (_) => setState(() => _pressed = true) : null,
      onTapUp: widget.onTap != null ? (_) => setState(() => _pressed = false) : null,
      onTapCancel: widget.onTap != null ? () => setState(() => _pressed = false) : null,
      onTap: widget.onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedOpacity(
        opacity: _pressed ? 0.85 : 1.0,
        duration: const Duration(milliseconds: 80),
        child: widget.child,
      ),
    );
  }
}
