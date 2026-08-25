import 'package:flutter/material.dart';

/// Wraps a page body to enable iOS-style swipe-to-go-back gesture.
///
/// Swipe right (left edge) → pop the current page.
/// Works on any page pushed via [Navigator.push].
///
/// Usage:
/// ```dart
/// Scaffold(
///   body: AppSwipeBack(
///     child: MyPageContent(),
///   ),
/// )
/// ```
class AppSwipeBack extends StatelessWidget {
  const AppSwipeBack({
    super.key,
    required this.child,
    this.enabled = true,
  });

  final Widget child;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    if (!enabled || !Navigator.canPop(context)) return child;

    return GestureDetector(
      onHorizontalDragEnd: (details) {
        final v = details.primaryVelocity ?? 0;
        // Swipe right from left edge → go back
        if (v > 400) {
          Navigator.of(context).pop();
        }
      },
      behavior: HitTestBehavior.translucent,
      child: child,
    );
  }
}

/// Wraps a page body to enable swipe-to-go-forward gesture.
///
/// Swipe left → call [onForward] (e.g., push next page).
/// Useful for sequential content like LMS lessons.
///
/// Usage:
/// ```dart
/// Scaffold(
///   body: AppSwipeForward(
///     onForward: () => _openNextLesson(),
///     child: MyPageContent(),
///   ),
/// )
/// ```
class AppSwipeForward extends StatelessWidget {
  const AppSwipeForward({
    super.key,
    required this.child,
    required this.onForward,
    this.enabled = true,
  });

  final Widget child;
  final VoidCallback onForward;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    if (!enabled) return child;

    return GestureDetector(
      onHorizontalDragEnd: (details) {
        final v = details.primaryVelocity ?? 0;
        // Swipe left → go forward
        if (v < -400) {
          onForward();
        }
      },
      behavior: HitTestBehavior.translucent,
      child: child,
    );
  }
}

/// Combines both swipe-back and swipe-forward gestures.
///
/// Swipe right → pop (go back).
/// Swipe left → call [onForward] (go forward).
///
/// Usage:
/// ```dart
/// Scaffold(
///   body: AppSwipeNavigation(
///     onForward: () => _openNextPage(),
///     child: MyPageContent(),
///   ),
/// )
/// ```
class AppSwipeNavigation extends StatelessWidget {
  const AppSwipeNavigation({
    super.key,
    required this.child,
    this.onForward,
    this.enableBack = true,
  });

  final Widget child;
  final VoidCallback? onForward;
  final bool enableBack;

  @override
  Widget build(BuildContext context) {
    final canPop = enableBack && Navigator.canPop(context);

    if (!canPop && onForward == null) return child;

    return GestureDetector(
      onHorizontalDragEnd: (details) {
        final v = details.primaryVelocity ?? 0;
        if (v > 400 && canPop) {
          Navigator.of(context).pop();
        } else if (v < -400 && onForward != null) {
          onForward!();
        }
      },
      behavior: HitTestBehavior.translucent,
      child: child,
    );
  }
}
