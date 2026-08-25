import 'package:flutter/material.dart';

// ─── Fade + Slide entrance animation ──────────────────────────────────────────
class FadeSlideIn extends StatefulWidget {
  const FadeSlideIn({
    super.key,
    required this.child,
    this.delay = Duration.zero,
    this.duration = const Duration(milliseconds: 480),
    this.offset = const Offset(0, 18),
    this.curve = Curves.easeOutCubic,
  });

  final Widget child;
  final Duration delay;
  final Duration duration;
  final Offset offset;
  final Curve curve;

  @override
  State<FadeSlideIn> createState() => _FadeSlideInState();
}

class _FadeSlideInState extends State<FadeSlideIn>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;
  late final Animation<double> _opacity;
  late final Animation<Offset> _slide;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(vsync: this, duration: widget.duration);
    _opacity = CurvedAnimation(parent: _ctrl, curve: Curves.easeOut);
    _slide = Tween<Offset>(
      begin: widget.offset,
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _ctrl, curve: widget.curve));
    Future.delayed(widget.delay, () {
      if (mounted) _ctrl.forward();
    });
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (context, child) => Transform.translate(
        offset: _slide.value,
        child: Opacity(opacity: _opacity.value, child: child),
      ),
      child: widget.child,
    );
  }
}

// ─── Staggered list item wrapper ──────────────────────────────────────────────
class StaggeredItem extends StatelessWidget {
  const StaggeredItem({
    super.key,
    required this.index,
    required this.child,
    this.baseDelay = 60,
    this.maxItems = 12,
    this.offset = const Offset(0, 14),
  });

  final int index;
  final Widget child;
  final int baseDelay;
  final int maxItems;
  final Offset offset;

  @override
  Widget build(BuildContext context) {
    final clamped = index.clamp(0, maxItems - 1);
    return FadeSlideIn(
      delay: Duration(milliseconds: clamped * baseDelay),
      offset: offset,
      child: child,
    );
  }
}

// ─── Custom page route with slide-up + fade transition ────────────────────────
class SlideUpRoute<T> extends PageRouteBuilder<T> {
  SlideUpRoute({
    required WidgetBuilder builder,
    super.settings,
    super.fullscreenDialog,
    this.transitionDuration = const Duration(milliseconds: 380),
  }) : super(
         pageBuilder: (context, animation, secondaryAnimation) =>
             builder(context),
         transitionsBuilder: (context, animation, secondaryAnimation, child) {
           const begin = Offset(0.0, 0.08);
           const end = Offset.zero;
           const curve = Curves.easeOutCubic;

           final tween = Tween(
             begin: begin,
             end: end,
           ).chain(CurveTween(curve: curve));
           final offsetAnimation = animation.drive(tween);

           return FadeTransition(
             opacity: CurvedAnimation(parent: animation, curve: curve),
             child: SlideTransition(position: offsetAnimation, child: child),
           );
         },
       );

  @override
  final Duration transitionDuration;
}

// ─── Animated scale-on-tap wrapper ────────────────────────────────────────────
class TapScale extends StatefulWidget {
  const TapScale({
    super.key,
    required this.child,
    this.onTap,
    this.scaleDown = 0.96,
    this.duration = const Duration(milliseconds: 120),
  });

  final Widget child;
  final VoidCallback? onTap;
  final double scaleDown;
  final Duration duration;

  @override
  State<TapScale> createState() => _TapScaleState();
}

class _TapScaleState extends State<TapScale> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) {
        setState(() => _pressed = false);
        widget.onTap?.call();
      },
      onTapCancel: () => setState(() => _pressed = false),
      child: AnimatedScale(
        scale: _pressed ? widget.scaleDown : 1.0,
        duration: widget.duration,
        curve: Curves.easeOut,
        child: widget.child,
      ),
    );
  }
}

// ─── Count-up number animation ─────────────────────────────────────────────────
class CountUp extends StatefulWidget {
  const CountUp({
    super.key,
    required this.end,
    required this.style,
    this.duration = const Duration(milliseconds: 950),
  });

  final double end;
  final TextStyle style;
  final Duration duration;

  @override
  State<CountUp> createState() => _CountUpState();
}

class _CountUpState extends State<CountUp> with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl = AnimationController(
    vsync: this,
    duration: widget.duration,
  )..forward();
  late final Animation<double> _anim = CurvedAnimation(
    parent: _ctrl,
    curve: Curves.easeOutCubic,
  );

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _anim,
      builder: (_, __) {
        final v = _anim.value * widget.end;
        return Text(
          '${v.round()}',
          textAlign: TextAlign.center,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: widget.style,
        );
      },
    );
  }
}

// ─── Shimmer loading effect ───────────────────────────────────────────────────
class ShimmerLoading extends StatefulWidget {
  const ShimmerLoading({super.key, required this.child});
  final Widget child;

  @override
  State<ShimmerLoading> createState() => _ShimmerLoadingState();
}

class _ShimmerLoadingState extends State<ShimmerLoading>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1200),
  )..repeat();

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (context, child) => ShaderMask(
        blendMode: BlendMode.srcATop,
        shaderCallback: (bounds) => LinearGradient(
          begin: Alignment(-1.5 + _ctrl.value * 3.0, 0),
          end: Alignment(-0.5 + _ctrl.value * 3.0, 0),
          colors: const [
            Color(0xFFE2E8F0),
            Color(0xFFF1F5F9),
            Color(0xFFE2E8F0),
          ],
        ).createShader(bounds),
        child: widget.child,
      ),
    );
  }
}

// ─── Skeleton box for shimmer placeholders ─────────────────────────────────────
class SkeletonBox extends StatelessWidget {
  const SkeletonBox({
    super.key,
    required this.width,
    required this.height,
    this.borderRadius = 16,
  });

  final double width;
  final double height;
  final double borderRadius;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: const Color(0xFFE2E8F0),
        borderRadius: BorderRadius.circular(borderRadius),
      ),
    );
  }
}
