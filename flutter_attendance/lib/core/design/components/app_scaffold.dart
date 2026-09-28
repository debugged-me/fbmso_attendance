import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_icons.dart';
import 'app_button.dart';

/// The page scaffold: canvas background, an optional flat app bar with a
/// round back button, and safe-area handling.
///
/// Use [AppScaffold] for pages with a custom body, or [AppScaffold.scroll]
/// for a simple scrollable page with padding.
class AppScaffold extends StatelessWidget {
  const AppScaffold({
    super.key,
    this.title,
    this.titleWidget,
    this.actions,
    this.leading,
    this.body,
    this.bottomNav,
    this.floatingActionButton,
    this.backgroundColor,
    this.appBarBackgroundColor,
    this.centerTitle = false,
    this.showBackButton = true,
    this.automaticallyImplyLeading = true,
  }) : _scrollable = false;

  /// A scrollable page with standard horizontal padding.
  const AppScaffold.scroll({
    super.key,
    this.title,
    this.titleWidget,
    this.actions,
    this.leading,
    this.body,
    this.bottomNav,
    this.floatingActionButton,
    this.backgroundColor,
    this.appBarBackgroundColor,
    this.centerTitle = false,
    this.showBackButton = true,
    this.automaticallyImplyLeading = true,
  }) : _scrollable = true;

  final String? title;
  final Widget? titleWidget;
  final List<Widget>? actions;
  final Widget? leading;
  final Widget? body;
  final Widget? bottomNav;
  final Widget? floatingActionButton;
  final Color? backgroundColor;
  final Color? appBarBackgroundColor;
  final bool centerTitle;
  final bool showBackButton;
  final bool automaticallyImplyLeading;
  final bool _scrollable;

  @override
  Widget build(BuildContext context) {
    final bg = backgroundColor ?? AppInk.page;
    final canPop = Navigator.canPop(context);

    return Scaffold(
      backgroundColor: bg,
      appBar: (title != null || titleWidget != null || actions != null)
          ? AppBar(
              backgroundColor: appBarBackgroundColor ?? bg,
              surfaceTintColor: Colors.transparent,
              elevation: 0,
              scrolledUnderElevation: 0,
              centerTitle: centerTitle,
              automaticallyImplyLeading: automaticallyImplyLeading,
              leadingWidth: 60,
              leading: leading ??
                  (showBackButton && canPop
                      ? const Padding(
                          padding: EdgeInsets.only(left: 12),
                          child: Center(child: AppBackButton()),
                        )
                      : null),
              title: titleWidget ?? (title != null ? Text(title!) : null),
              actions: [
                ...?actions,
                const SizedBox(width: 8),
              ],
            )
          : null,
      body: body != null && _scrollable
          ? SafeArea(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                child: body!,
              ),
            )
          : body != null
              ? SafeArea(child: body!)
              : null,
      bottomNavigationBar: bottomNav,
      floatingActionButton: floatingActionButton,
    );
  }
}

/// A round, hairline-bordered back button.
class AppBackButton extends StatelessWidget {
  const AppBackButton({super.key, this.onPressed, this.icon});

  final VoidCallback? onPressed;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return AppCircleButton(
      icon: icon ?? AppIcons.arrow_back_ios_new_rounded,
      tooltip: MaterialLocalizations.of(context).backButtonTooltip,
      onTap: onPressed ?? () => Navigator.of(context).maybePop(),
    );
  }
}

/// A 40px round icon button on a white disc: back, close, overflow.
class AppCircleButton extends StatelessWidget {
  const AppCircleButton({
    super.key,
    required this.icon,
    required this.onTap,
    this.tooltip,
    this.size = 40,
    this.color,
    this.background = Colors.white,
    this.badge = false,
  });

  final IconData icon;
  final VoidCallback? onTap;
  final String? tooltip;
  final double size;
  final Color? color;
  final Color background;

  /// Shows a small accent dot (e.g. unread notifications).
  final bool badge;

  @override
  Widget build(BuildContext context) {
    Widget button = Material(
      color: background,
      shape: CircleBorder(
        side: background == Colors.white
            ? const BorderSide(color: AppInk.rule)
            : BorderSide.none,
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: SizedBox(
          width: size,
          height: size,
          child: Icon(icon, size: size * 0.48, color: color ?? AppInk.heading),
        ),
      ),
    );
    if (badge) {
      button = Stack(
        clipBehavior: Clip.none,
        children: [
          button,
          Positioned(
            right: 2,
            top: 2,
            child: Container(
              width: 10,
              height: 10,
              decoration: BoxDecoration(
                color: AppInk.critical,
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white, width: 2),
              ),
            ),
          ),
        ],
      );
    }
    return tooltip == null ? button : Tooltip(message: tooltip!, child: button);
  }
}

/// A section header: sentence-case title with an optional trailing link.
class AppSectionHeader extends StatelessWidget {
  const AppSectionHeader({
    super.key,
    required this.title,
    this.action,
    this.onAction,
    this.padding = const EdgeInsets.fromLTRB(16, 0, 16, 10),
  });

  final String title;
  final String? action;
  final VoidCallback? onAction;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: padding,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: Text(
              _sentence(title),
              style: AppType.headline.copyWith(fontSize: 16.5),
            ),
          ),
          if (action != null)
            GestureDetector(
              onTap: onAction,
              behavior: HitTestBehavior.opaque,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
                child: Text(
                  action!,
                  style: AppType.caption.copyWith(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w600,
                    color: AppInk.accent,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// "RECENT SCANS" → "Recent scans": older call sites pass shouty titles.
String _sentence(String s) {
  final t = s.trim();
  if (t.isEmpty || t != t.toUpperCase() || !t.contains(RegExp('[A-Z]'))) {
    return t;
  }
  final lower = t.toLowerCase();
  return lower[0].toUpperCase() + lower.substring(1);
}

/// An empty state: a soft icon disc, a message and an optional action.
///
/// Pass [svgAsset] to render an SVG illustration instead of the icon disc.
class AppEmptyState extends StatelessWidget {
  const AppEmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.action,
    this.onAction,
    this.tone,
    this.svgAsset,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final String? action;
  final VoidCallback? onAction;

  /// Optional colour for the icon. Defaults to a neutral grey.
  final Color? tone;

  /// Optional SVG asset path, shown instead of the icon disc.
  final String? svgAsset;

  @override
  Widget build(BuildContext context) {
    final iconColor = tone ?? AppInk.secondary;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 28),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          if (svgAsset != null)
            SvgPicture.asset(
              svgAsset!,
              width: 160,
              height: 160,
              fit: BoxFit.contain,
            )
          else
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: tone == null
                    ? AppInk.subtle
                    : iconColor.withValues(alpha: 0.10),
                shape: BoxShape.circle,
                border: Border.all(
                  color: tone == null
                      ? AppInk.page
                      : iconColor.withValues(alpha: 0.05),
                  width: 8,
                  strokeAlign: BorderSide.strokeAlignOutside,
                ),
              ),
              child: Icon(icon, size: 28, color: iconColor),
            ),
          const SizedBox(height: 20),
          Text(
            title,
            textAlign: TextAlign.center,
            style: AppType.headline.copyWith(fontSize: 16.5),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 6),
            Text(
              subtitle!,
              textAlign: TextAlign.center,
              style: AppType.body.copyWith(fontSize: 14, color: AppInk.muted),
            ),
          ],
          if (action != null && onAction != null) ...[
            const SizedBox(height: 20),
            AppButton(label: action!, onTap: onAction),
          ],
        ],
      ),
    );
  }
}
