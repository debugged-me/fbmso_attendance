import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_theme.dart';

/// Button variants: each is a distinct visual role, not just a colour swap.
enum AppButtonStyle {
  /// Solid accent fill. The primary action on the screen (one per view).
  primary,

  /// Accent tint. A secondary action that doesn't need to shout.
  tonal,

  /// White with a border. Tertiary action, or on a coloured surface.
  outline,

  /// No fill, no border. Inline actions inside cards and rows.
  ghost,

  /// Solid red. Destructive actions (delete, withdraw, drop).
  destructive,
}

enum AppButtonSize { sm, md, lg }

/// The app's button: a pill with a short press-scale.
///
/// ```dart
/// AppButton(label: 'Sign in', onTap: _submit, fullWidth: true)
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
    final inactive = disabled || loading || onTap == null;
    final c = _colors(inactive: disabled || onTap == null, loading: loading);
    final (height, hPad, fontSize, iconSize) = switch (size) {
      AppButtonSize.sm => (36.0, 14.0, 13.5, 16.0),
      AppButtonSize.md => (46.0, 20.0, 15.0, 18.0),
      AppButtonSize.lg => (54.0, 24.0, 16.0, 20.0),
    };

    final content = Row(
      mainAxisSize: fullWidth ? MainAxisSize.max : MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (loading)
          Padding(
            padding: const EdgeInsets.only(right: 10),
            child: SizedBox(
              width: iconSize - 2,
              height: iconSize - 2,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation(c.fg),
              ),
            ),
          )
        else if (icon != null) ...[
          Icon(icon, size: iconSize, color: c.fg),
          const SizedBox(width: 8),
        ],
        Flexible(
          child: Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontFamily: AppTheme.fontFamily,
              fontSize: fontSize,
              fontWeight: FontWeight.w600,
              letterSpacing: -0.1,
              color: c.fg,
            ),
          ),
        ),
        if (trailingIcon != null && !loading) ...[
          const SizedBox(width: 6),
          Icon(trailingIcon, size: iconSize - 2, color: c.fg),
        ],
      ],
    );

    return Semantics(
      button: true,
      enabled: !inactive,
      child: _PressScale(
        onTap: inactive ? null : onTap,
        child: AnimatedContainer(
          duration: AppMotion.base,
          curve: AppMotion.ease,
          height: height,
          padding: EdgeInsets.symmetric(horizontal: hPad),
          decoration: ShapeDecoration(
            color: c.bg,
            shape: StadiumBorder(side: c.border),
            shadows: c.shadow,
          ),
          child: content,
        ),
      ),
    );
  }

  ({Color bg, Color fg, BorderSide border, List<BoxShadow> shadow}) _colors({
    required bool inactive,
    required bool loading,
  }) {
    if (inactive && !loading) {
      return (
        bg: style == AppButtonStyle.ghost ? Colors.transparent : AppInk.subtle,
        fg: AppInk.faint,
        border: BorderSide.none,
        shadow: const [],
      );
    }
    switch (style) {
      case AppButtonStyle.primary:
        return (
          bg: loading ? AppInk.accent.withValues(alpha: 0.85) : AppInk.accent,
          fg: Colors.white,
          border: BorderSide.none,
          shadow: const [
            BoxShadow(
                color: Color(0x292F5BEA), blurRadius: 12, offset: Offset(0, 4)),
          ],
        );
      case AppButtonStyle.tonal:
        return (
          bg: AppInk.accentSoft,
          fg: AppInk.accentInk,
          border: BorderSide.none,
          shadow: const [],
        );
      case AppButtonStyle.outline:
        return (
          bg: Colors.white,
          fg: AppTheme.ink700,
          border: const BorderSide(color: AppInk.ruleStrong),
          shadow: AppShadow.xs,
        );
      case AppButtonStyle.ghost:
        return (
          bg: Colors.transparent,
          fg: AppInk.secondary,
          border: BorderSide.none,
          shadow: const [],
        );
      case AppButtonStyle.destructive:
        return (
          bg: AppInk.critical,
          fg: Colors.white,
          border: BorderSide.none,
          shadow: const [],
        );
    }
  }
}

/// Press feedback: a short scale-down and a slight fade, no ripple.
class _PressScale extends StatefulWidget {
  const _PressScale({required this.onTap, required this.child});

  final VoidCallback? onTap;
  final Widget child;

  @override
  State<_PressScale> createState() => _PressScaleState();
}

class _PressScaleState extends State<_PressScale> {
  bool _pressed = false;

  void _set(bool v) {
    if (_pressed != v) setState(() => _pressed = v);
  }

  @override
  Widget build(BuildContext context) {
    final interactive = widget.onTap != null;
    return GestureDetector(
      onTapDown: interactive ? (_) => _set(true) : null,
      onTapUp: interactive ? (_) => _set(false) : null,
      onTapCancel: interactive ? () => _set(false) : null,
      onTap: widget.onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedScale(
        scale: _pressed ? 0.97 : 1,
        duration: AppMotion.fast,
        curve: AppMotion.ease,
        child: AnimatedOpacity(
          opacity: _pressed ? 0.88 : 1,
          duration: AppMotion.fast,
          child: widget.child,
        ),
      ),
    );
  }
}
