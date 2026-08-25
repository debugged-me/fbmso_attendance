import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_theme.dart';

/// A standard page scaffold with consistent background, optional AppBar,
/// and safe-area handling. This is the base for every screen in the app.
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
              leading: leading ??
                  (showBackButton && Navigator.canPop(context)
                      ? IconButton(
                          icon: const Icon(Icons.chevron_left_rounded, size: 26),
                          onPressed: () => Navigator.of(context).pop(),
                          style: IconButton.styleFrom(
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                        )
                      : null),
              title: titleWidget ??
                  (title != null
                      ? Text(
                          title!,
                          style: TextStyle(
                            fontFamily: AppTheme.fontFamily,
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: AppInk.heading,
                          ),
                        )
                      : null),
              actions: actions,
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

/// A section header — quiet, uppercase, with optional trailing action.
/// Replaces the random `Text('Section', style: TextStyle(fontSize: 13...))`
/// patterns scattered across the app.
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
        children: [
          Expanded(
            child: Text(
              title.toUpperCase(),
              style: TextStyle(
                fontFamily: AppTheme.fontFamily,
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppInk.muted,
                letterSpacing: 0.8,
              ),
            ),
          ),
          if (action != null)
            GestureDetector(
              onTap: onAction,
              behavior: HitTestBehavior.opaque,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                child: Text(
                  action!,
                  style: TextStyle(
                    fontFamily: AppTheme.fontFamily,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
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

/// An empty state placeholder — icon, message, optional action.
/// Use when a list has no items.
///
/// Pass [svgAsset] to render an SVG illustration instead of the default
/// icon orb (e.g. 'assets/images/empty_states/no-grade.svg'). When an SVG
/// is provided, [icon] and [tone] are ignored.
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

  /// Optional color tone for the icon background. Defaults to [AppInk.muted].
  final Color? tone;

  /// Optional SVG asset path. When provided, renders the SVG illustration
  /// instead of the icon orb.
  final String? svgAsset;

  @override
  Widget build(BuildContext context) {
    final iconColor = tone ?? AppInk.muted;
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          if (svgAsset != null)
            SvgPicture.asset(
              svgAsset!,
              width: 180,
              height: 180,
              fit: BoxFit.contain,
            )
          else
            // Soft gradient orb behind the icon for warmth
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(24),
              ),
              child: Stack(
                alignment: Alignment.center,
                children: [
                  // Soft glow
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: RadialGradient(
                        colors: [
                          iconColor.withValues(alpha: 0.12),
                          iconColor.withValues(alpha: 0.0),
                        ],
                      ),
                    ),
                  ),
                  Icon(icon, size: 32, color: iconColor),
                ],
              ),
            ),
          const SizedBox(height: 18),
          Text(
            title,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontFamily: AppTheme.fontFamily,
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: AppInk.heading,
            ),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 8),
            Text(
              subtitle!,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontFamily: AppTheme.fontFamily,
                fontSize: 13.5,
                fontWeight: FontWeight.w500,
                color: AppInk.muted,
                height: 1.45,
              ),
            ),
          ],
          if (action != null && onAction != null) ...[
            const SizedBox(height: 22),
            _EmptyAction(label: action!, onTap: onAction!),
          ],
        ],
      ),
    );
  }
}

class _EmptyAction extends StatelessWidget {
  const _EmptyAction({required this.label, required this.onTap});
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        decoration: BoxDecoration(
          color: AppInk.accent,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontFamily: AppTheme.fontFamily,
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}
