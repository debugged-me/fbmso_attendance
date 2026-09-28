import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import 'app_squircle.dart';

/// A white surface with a hairline border and a barely-there shadow.
///
/// Use [AppCard.elevated] for the one block on a screen that should float
/// (a hero figure, a summary above a list).
class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.onTap,
    this.padding = const EdgeInsets.all(16),
    this.radius = AppRadius.lg,
    this.borderColor,
    this.background = Colors.white,
    this.margin,
    this.elevated = false,
  });

  /// Card with a softer, wider shadow for floating elements.
  const AppCard.elevated({
    super.key,
    required this.child,
    this.onTap,
    this.padding = const EdgeInsets.all(16),
    this.radius = AppRadius.lg,
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
    final decoration = ShapeDecoration(
      color: background,
      shape: SquircleBorder(
        radius: radius,
        side: BorderSide(color: borderColor ?? AppInk.rule),
      ),
      shadows: elevated ? AppShadow.lg : AppShadow.xs,
    );

    Widget card = DecoratedBox(
      decoration: decoration,
      child: Padding(padding: padding, child: child),
    );

    if (onTap != null) {
      card = _TapFeedback(onTap: onTap, child: card);
    }
    if (margin != null) {
      card = Padding(padding: margin!, child: card);
    }
    return card;
  }
}

/// A group of cards stacked vertically with consistent spacing.
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

/// Press feedback for tappable cards: a slight scale-down, no ripple.
class _TapFeedback extends StatefulWidget {
  const _TapFeedback({required this.onTap, required this.child});
  final VoidCallback? onTap;
  final Widget child;

  @override
  State<_TapFeedback> createState() => _TapFeedbackState();
}

class _TapFeedbackState extends State<_TapFeedback> {
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
      child: AnimatedScale(
        scale: _pressed ? 0.985 : 1,
        duration: AppMotion.fast,
        curve: AppMotion.ease,
        child: AnimatedOpacity(
          opacity: _pressed ? 0.92 : 1,
          duration: AppMotion.fast,
          child: widget.child,
        ),
      ),
    );
  }
}
