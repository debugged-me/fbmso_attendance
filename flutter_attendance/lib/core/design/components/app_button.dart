import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_theme.dart';
import 'app_squircle.dart';

/// Button variants — each is a distinct visual role, not just a colour swap.
enum AppButtonStyle {
  /// Solid brand fill. Primary action on the screen (one per view max).
  primary,

  /// Subtle brand tint. Secondary action that doesn't need to shout.
  tonal,

  /// White with border. Tertiary action, or when sitting on a coloured surface.
  outline,

  /// No fill, no border. Inline actions inside cards/rows.
  ghost,

  /// Solid red. Destructive actions (delete, withdraw, drop).
  destructive,
}

/// A squircle button with clean, minimal styling.
///
/// Designed to be the only button in the app. Replaces Material's default
/// ElevatedButton / FilledButton / OutlinedButton / TextButton with a single
/// widget that has a consistent squircle shape and four clear roles.
///
/// Usage:
/// ```dart
/// AppButton(label: 'Login', onTap: _submit)
/// AppButton(label: 'Cancel', style: AppButtonStyle.ghost, onTap: _close)
/// AppButton(label: 'Delete', style: AppButtonStyle.destructive, onTap: _delete)
/// ```
class AppButton extends StatelessWidget {
  const AppButton({
    super.key,
    required this.label,
    this.onTap,
    this.style = AppButtonStyle.primary,
    this.icon,
    this.trailingIcon,
    this.fullWidth = false,
    this.loading = false,
    this.disabled = false,
    this.size = AppButtonSize.md,
  });

  final String label;
  final VoidCallback? onTap;
  final AppButtonStyle style;
  final IconData? icon;
  final IconData? trailingIcon;

  /// Stretches to full available width.
  final bool fullWidth;

  /// Shows a spinner and disables interaction.
  final bool loading;

  /// Greys out and disables interaction.
  final bool disabled;

  final AppButtonSize size;

  @override
  Widget build(BuildContext context) {
    final isDisabled = disabled || loading;
    final colors = _resolveColors();
    final padding = _resolvePadding();

    Widget content = Row(
      mainAxisSize: fullWidth ? MainAxisSize.max : MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (loading)
          SizedBox(
            width: 16,
            height: 16,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              valueColor: AlwaysStoppedAnimation(colors.foreground),
            ),
          )
        else if (icon != null) ...[
          Icon(icon, size: 18, color: colors.foreground),
          const SizedBox(width: 8),
        ],
        Text(
          label,
          style: TextStyle(
            fontFamily: AppTheme.fontFamily,
            fontSize: size == AppButtonSize.sm ? 13 : 15,
            fontWeight: FontWeight.w700,
            color: colors.foreground,
          ),
        ),
        if (trailingIcon != null && !loading) ...[
          const SizedBox(width: 6),
          Icon(trailingIcon, size: 16, color: colors.foreground),
        ],
      ],
    );

    final shape = SquircleBorder(
      radius: size == AppButtonSize.sm ? 10 : 14,
      side: colors.border,
    );

    return _FeedbackContainer(
      onTap: isDisabled ? null : onTap,
      shape: shape,
      background: colors.background,
      child: Padding(
        padding: padding,
        child: content,
      ),
    );
  }

  ({Color background, Color foreground, BorderSide border}) _resolveColors() {
    final disabledBg = const Color(0xFFF1F5F9);
    final disabledFg = const Color(0xFF94A3B8);

    if (disabled || loading) {
      return (background: disabledBg, foreground: disabledFg, border: BorderSide.none);
    }

    switch (style) {
      case AppButtonStyle.primary:
        return (background: AppInk.accent, foreground: Colors.white, border: BorderSide.none);
      case AppButtonStyle.tonal:
        return (background: AppInk.accent.withValues(alpha: 0.10), foreground: AppInk.accent, border: BorderSide.none);
      case AppButtonStyle.outline:
        return (background: Colors.white, foreground: AppInk.heading, border: const BorderSide(color: AppInk.rule, width: 1.5));
      case AppButtonStyle.ghost:
        return (background: Colors.transparent, foreground: AppInk.muted, border: BorderSide.none);
      case AppButtonStyle.destructive:
        return (background: AppInk.critical, foreground: Colors.white, border: BorderSide.none);
    }
  }

  EdgeInsets _resolvePadding() {
    switch (size) {
      case AppButtonSize.sm:
        return const EdgeInsets.symmetric(horizontal: 14, vertical: 9);
      case AppButtonSize.md:
        return const EdgeInsets.symmetric(horizontal: 20, vertical: 13);
      case AppButtonSize.lg:
        return const EdgeInsets.symmetric(horizontal: 24, vertical: 16);
    }
  }
}

enum AppButtonSize { sm, md, lg }

/// A container that provides tap feedback (slight opacity dip) without the
/// ripple of InkWell — cleaner and more iOS-like.
class _FeedbackContainer extends StatefulWidget {
  const _FeedbackContainer({
    required this.onTap,
    required this.shape,
    required this.background,
    required this.child,
  });

  final VoidCallback? onTap;
  final ShapeBorder shape;
  final Color background;
  final Widget child;

  @override
  State<_FeedbackContainer> createState() => _FeedbackContainerState();
}

class _FeedbackContainerState extends State<_FeedbackContainer> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final isInteractive = widget.onTap != null;

    return GestureDetector(
      onTapDown: isInteractive ? (_) => setState(() => _pressed = true) : null,
      onTapUp: isInteractive ? (_) => setState(() => _pressed = false) : null,
      onTapCancel: isInteractive ? () => setState(() => _pressed = false) : null,
      onTap: widget.onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedOpacity(
        opacity: _pressed ? 0.7 : 1.0,
        duration: const Duration(milliseconds: 80),
        child: DecoratedBox(
          decoration: ShapeDecoration(
            shape: widget.shape,
            color: widget.background,
          ),
          child: widget.child,
        ),
      ),
    );
  }
}
