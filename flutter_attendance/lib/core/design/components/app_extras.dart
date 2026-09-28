import 'package:flutter/material.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_icons.dart';
import '../../theme/app_theme.dart';

/// The app mark (a transparent squircle cut from the launcher icon), or the
/// school's own logo when the portal supplies one.
class AppBrandMark extends StatelessWidget {
  const AppBrandMark({super.key, this.size = 56, this.logoUrl});

  final double size;

  /// A school logo URL from `/config`. Falls back to the app mark.
  final String? logoUrl;

  static const String asset = 'assets/img/logo-mark.png';

  @override
  Widget build(BuildContext context) {
    final mark = Image.asset(asset, width: size, height: size);
    final url = logoUrl?.trim() ?? '';
    final radius = BorderRadius.circular(size * 0.26);
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: radius,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF101828).withValues(alpha: 0.10),
            blurRadius: size * 0.3,
            offset: Offset(0, size * 0.08),
          ),
        ],
      ),
      child: url.isEmpty
          ? mark
          : Container(
              width: size,
              height: size,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: radius,
                border: Border.all(color: AppInk.rule),
              ),
              padding: EdgeInsets.all(size * 0.12),
              child: Image.network(
                url,
                fit: BoxFit.contain,
                errorBuilder: (_, __, ___) =>
                    Image.asset(asset, width: size, height: size),
              ),
            ),
    );
  }
}

/// A round avatar: the photo when there is one, otherwise initials on an
/// accent tint.
class AppAvatar extends StatelessWidget {
  const AppAvatar({
    super.key,
    required this.name,
    this.url,
    this.size = 40,
    this.ring = false,
  });

  final String name;
  final String? url;
  final double size;

  /// White ring, for avatars sitting on a photo or tinted surface.
  final bool ring;

  static String initialsOf(String name) {
    final parts = name
        .trim()
        .split(RegExp(r'[\s,]+'))
        .where((p) => p.isNotEmpty && RegExp('[A-Za-z]').hasMatch(p[0]))
        .toList();
    if (parts.isEmpty) return '?';
    final first = parts.first[0];
    final last = parts.length > 1 ? parts.last[0] : '';
    return (first + last).toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    final initials = Container(
      color: AppInk.accentSoft,
      alignment: Alignment.center,
      child: Text(
        initialsOf(name),
        style: TextStyle(
          fontFamily: AppTheme.fontFamily,
          fontSize: size * 0.36,
          fontWeight: FontWeight.w600,
          color: AppInk.accentInk,
          letterSpacing: 0.2,
        ),
      ),
    );
    final u = url?.trim() ?? '';
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: ring
            ? Border.all(color: Colors.white, width: 2)
            : Border.all(color: AppInk.rule),
      ),
      child: ClipOval(
        child: u.isEmpty
            ? initials
            : Image.network(
                u,
                width: size,
                height: size,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => initials,
              ),
      ),
    );
  }
}

/// An inline message on a soft tint: errors, warnings, notices.
class AppNotice extends StatelessWidget {
  const AppNotice({
    super.key,
    required this.message,
    this.tone = AppInk.critical,
    this.icon,
    this.title,
    this.action,
    this.onAction,
  });

  final String message;
  final Color tone;
  final IconData? icon;
  final String? title;
  final String? action;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final ink = AppInk.onTint(tone);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(12, 12, 14, 12),
      decoration: BoxDecoration(
        color: tone.withValues(alpha: 0.07),
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: tone.withValues(alpha: 0.22)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 1),
            child: Icon(
              icon ?? _defaultIcon(tone),
              size: 18,
              color: tone,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (title != null) ...[
                  Text(
                    title!,
                    style: AppType.row.copyWith(fontSize: 14, color: ink),
                  ),
                  const SizedBox(height: 2),
                ],
                Text(
                  message,
                  style: AppType.caption.copyWith(
                    fontSize: 13.5,
                    color: ink,
                    height: 1.4,
                  ),
                ),
                if (action != null && onAction != null) ...[
                  const SizedBox(height: 8),
                  GestureDetector(
                    onTap: onAction,
                    child: Text(
                      action!,
                      style: AppType.caption.copyWith(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w700,
                        color: tone,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  static IconData _defaultIcon(Color tone) {
    if (tone == AppInk.positive) return AppIcons.check_circle_rounded;
    if (tone == AppInk.caution) return AppIcons.warning_amber_rounded;
    if (tone == AppInk.critical) return AppIcons.error_rounded;
    return AppIcons.info_rounded;
  }
}

/// A capsule search field with a clear button.
class AppSearchField extends StatefulWidget {
  const AppSearchField({
    super.key,
    this.controller,
    this.hint = 'Search',
    this.onChanged,
    this.onSubmitted,
    this.autofocus = false,
  });

  final TextEditingController? controller;
  final String hint;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onSubmitted;
  final bool autofocus;

  @override
  State<AppSearchField> createState() => _AppSearchFieldState();
}

class _AppSearchFieldState extends State<AppSearchField> {
  TextEditingController? _own;
  TextEditingController get _c =>
      widget.controller ?? (_own ??= TextEditingController());

  @override
  void dispose() {
    _own?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    const border = OutlineInputBorder(
      borderRadius: BorderRadius.all(Radius.circular(AppRadius.pill)),
      borderSide: BorderSide(color: AppInk.rule),
    );
    return DecoratedBox(
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.all(Radius.circular(AppRadius.pill)),
        boxShadow: AppShadow.xs,
      ),
      child: ValueListenableBuilder<TextEditingValue>(
        valueListenable: _c,
        builder: (context, value, _) => TextField(
          controller: _c,
          autofocus: widget.autofocus,
          onChanged: widget.onChanged,
          onSubmitted: widget.onSubmitted,
          textInputAction: TextInputAction.search,
          cursorColor: AppInk.accent,
          style: const TextStyle(
            fontFamily: AppTheme.fontFamily,
            fontSize: 15,
            fontWeight: FontWeight.w500,
            color: AppInk.heading,
          ),
          decoration: InputDecoration(
            hintText: widget.hint,
            isDense: true,
            filled: true,
            fillColor: Colors.white,
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 4, vertical: 13),
            prefixIcon: const Icon(AppIcons.search_rounded,
                size: 20, color: AppInk.muted),
            suffixIcon: value.text.isEmpty
                ? null
                : IconButton(
                    tooltip: 'Clear',
                    icon: const Icon(AppIcons.clear_rounded,
                        size: 18, color: AppInk.muted),
                    onPressed: () {
                      _c.clear();
                      widget.onChanged?.call('');
                    },
                  ),
            border: border,
            enabledBorder: border,
            focusedBorder: border.copyWith(
              borderSide: const BorderSide(color: AppInk.accent, width: 1.5),
            ),
          ),
        ),
      ),
    );
  }
}

/// One destination in [AppBottomNav].
class AppNavItem {
  const AppNavItem({
    required this.icon,
    required this.label,
    this.selectedIcon,
    this.prominent = false,
  });

  final IconData icon;
  final IconData? selectedIcon;
  final String label;

  /// Draws the icon on a filled accent disc: the screen's key action
  /// (Scan) stays easy to find with a thumb.
  final bool prominent;
}

/// A floating capsule tab bar.
class AppBottomNav extends StatelessWidget {
  const AppBottomNav({
    super.key,
    required this.items,
    required this.currentIndex,
    required this.onSelect,
    this.dark = false,
  });

  final List<AppNavItem> items;
  final int currentIndex;
  final ValueChanged<int> onSelect;

  /// Sits on a dark page (the camera): the bar stays white, the gap around it
  /// turns black.
  final bool dark;

  @override
  Widget build(BuildContext context) {
    return ColoredBox(
      color: dark ? Colors.black : AppInk.page,
      child: SafeArea(
        top: false,
        minimum: const EdgeInsets.only(bottom: 10),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 6, 16, 0),
          child: Container(
            height: 66,
            padding: const EdgeInsets.symmetric(horizontal: 6),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppRadius.pill),
              border: Border.all(color: AppInk.rule),
              boxShadow: AppShadow.lg,
            ),
            child: Row(
              children: [
                for (var i = 0; i < items.length; i++)
                  Expanded(
                    child: _NavButton(
                      item: items[i],
                      selected: i == currentIndex,
                      onTap: () => onSelect(i),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  const _NavButton({
    required this.item,
    required this.selected,
    required this.onTap,
  });

  final AppNavItem item;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final icon = selected ? (item.selectedIcon ?? item.icon) : item.icon;
    final Widget glyph;
    if (item.prominent) {
      glyph = AnimatedContainer(
        duration: AppMotion.base,
        curve: AppMotion.ease,
        width: 50,
        height: 32,
        decoration: BoxDecoration(
          color: AppInk.accent,
          borderRadius: BorderRadius.circular(AppRadius.pill),
          boxShadow: const [
            BoxShadow(
                color: Color(0x332F5BEA), blurRadius: 10, offset: Offset(0, 3)),
          ],
        ),
        child: Icon(icon, size: 20, color: Colors.white),
      );
    } else {
      glyph = AnimatedContainer(
        duration: AppMotion.base,
        curve: AppMotion.ease,
        width: 50,
        height: 32,
        decoration: BoxDecoration(
          color: selected ? AppInk.accentSoft : Colors.transparent,
          borderRadius: BorderRadius.circular(AppRadius.pill),
        ),
        child: Icon(
          icon,
          size: 22,
          color: selected ? AppInk.accent : AppInk.muted,
        ),
      );
    }
    return Semantics(
      selected: selected,
      button: true,
      label: item.label,
      excludeSemantics: true,
      child: GestureDetector(
        onTap: onTap,
        behavior: HitTestBehavior.opaque,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            glyph,
            const SizedBox(height: 3),
            AnimatedDefaultTextStyle(
              duration: AppMotion.base,
              style: TextStyle(
                fontFamily: AppTheme.fontFamily,
                fontSize: 11.5,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                color: selected ? AppInk.heading : AppInk.muted,
              ),
              child: Text(item.label, maxLines: 1),
            ),
          ],
        ),
      ),
    );
  }
}

/// A faint dot grid that fades out downwards: texture for the top of the
/// sign-in screens without adding colour.
class AppDotGrid extends StatelessWidget {
  const AppDotGrid({super.key, this.height = 320, this.spacing = 20});

  final double height;
  final double spacing;

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: ShaderMask(
          blendMode: BlendMode.dstIn,
          shaderCallback: (r) => const LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xCC000000), Color(0x00000000)],
          ).createShader(r),
          child: CustomPaint(painter: _DotsPainter(spacing)),
        ),
      ),
    );
  }
}

class _DotsPainter extends CustomPainter {
  _DotsPainter(this.spacing);
  final double spacing;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = AppTheme.ink300;
    for (var y = spacing / 2; y < size.height; y += spacing) {
      for (var x = spacing / 2; x < size.width; x += spacing) {
        canvas.drawCircle(Offset(x, y), 1.1, paint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _DotsPainter old) => old.spacing != spacing;
}
