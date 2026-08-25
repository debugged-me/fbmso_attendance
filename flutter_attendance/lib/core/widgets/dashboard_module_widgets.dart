import 'package:flutter/material.dart';
import '../theme/app_icons.dart';
import 'package:flutter/services.dart';

import '../theme/app_theme.dart';
import '../design/components/components.dart';
import '../design/tokens/app_tokens.dart';
import 'app_section_scaffold.dart';

const dashboardBorderLight = AppInk.rule;
const dashboardTextDark = AppInk.heading;
const dashboardTextMuted = AppInk.muted;
const dashboardSurface = AppInk.page;
const dashboardShadow = Color(0x0A0F172A);

class DashboardModuleItem {
  const DashboardModuleItem({
    this.id = '',
    required this.title,
    required this.icon,
    required this.color,
    this.subtitle = '',
    this.badgeCount,
    this.progressValue,
    this.quickAction,
    this.size = DashboardModuleSize.normal,
    this.previewText,
  });

  final String id;
  final String title;
  final IconData icon;
  final Color color;
  final String subtitle;
  final int? badgeCount;
  final double? progressValue;
  final String? quickAction;
  final DashboardModuleSize size;
  final String? previewText;
}

enum DashboardModuleSize { small, normal, large, wide }

class DashboardPanelCard extends StatelessWidget {
  const DashboardPanelCard({
    super.key,
    required this.title,
    required this.child,
    this.headerColor,
    this.onTap,
  });

  final String title;
  final Widget child;
  final Color? headerColor;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final hasColoredHeader = headerColor != null;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        customBorder: const SquircleBorder(radius: 18),
        child: Ink(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: dashboardBorderLight),
            boxShadow: AppTheme.subtleShadow,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: hasColoredHeader ? headerColor : Colors.white,
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(17),
                  ),
                  border: hasColoredHeader
                      ? null
                      : const Border(
                          bottom: BorderSide(color: dashboardBorderLight),
                        ),
                ),
                child: Text(
                  title,
                  style: TextStyle(
                    fontFamily: AppTheme.fontFamily,
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: hasColoredHeader ? Colors.white : dashboardTextDark,
                    letterSpacing: .3,
                  ),
                ),
              ),
              Padding(padding: const EdgeInsets.all(16), child: child),
            ],
          ),
        ),
      ),
    );
  }
}

class DashboardModuleGrid extends StatelessWidget {
  const DashboardModuleGrid({
    super.key,
    required this.items,
    required this.onTap,
  });

  final List<DashboardModuleItem> items;
  final ValueChanged<DashboardModuleItem> onTap;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        return GridView.builder(
          itemCount: items.length,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithMaxCrossAxisExtent(
            maxCrossAxisExtent: 280,
            mainAxisSpacing: 16,
            crossAxisSpacing: 16,
            childAspectRatio: 1.2,
          ),
          itemBuilder: (context, index) => _AnimatedDashboardTile(
            delay: index * 60,
            child: _DashboardModuleTile(
              item: items[index],
              onTap: () => onTap(items[index]),
            ),
          ),
        );
      },
    );
  }
}

class _AnimatedDashboardTile extends StatefulWidget {
  const _AnimatedDashboardTile({required this.child, required this.delay});

  final Widget child;
  final int delay;

  @override
  State<_AnimatedDashboardTile> createState() => _AnimatedDashboardTileState();
}

class _AnimatedDashboardTileState extends State<_AnimatedDashboardTile>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 380),
  );

  late final Animation<double> _fade = CurvedAnimation(
    parent: _controller,
    curve: Curves.easeOut,
  );

  late final Animation<Offset> _slide = Tween<Offset>(
    begin: const Offset(0, .08),
    end: Offset.zero,
  ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic));

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration(milliseconds: 60 + widget.delay), () {
      if (mounted) _controller.forward();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _fade,
      child: SlideTransition(position: _slide, child: widget.child),
    );
  }
}

class _DashboardModuleTile extends StatefulWidget {
  const _DashboardModuleTile({required this.item, required this.onTap});

  final DashboardModuleItem item;
  final VoidCallback onTap;

  @override
  State<_DashboardModuleTile> createState() => _DashboardModuleTileState();
}

class _DashboardModuleTileState extends State<_DashboardModuleTile>
    with SingleTickerProviderStateMixin {
  bool _isHovered = false;
  bool _isExpanded = false;
  late AnimationController _hoverController;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _hoverController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 200),
    );
    _scaleAnimation = Tween<double>(
      begin: 1.0,
      end: 1.05,
    ).animate(CurvedAnimation(parent: _hoverController, curve: Curves.easeOut));
  }

  @override
  void dispose() {
    _hoverController.dispose();
    super.dispose();
  }

  void _onHover(bool isHovered) {
    setState(() => _isHovered = isHovered);
    if (isHovered) {
      _hoverController.forward();
    } else {
      _hoverController.reverse();
    }
  }

  void _onTap() {
    HapticFeedback.selectionClick();
    widget.onTap();
  }

  @override
  Widget build(BuildContext context) {
    final hasProgress = widget.item.progressValue != null;
    final hasBadge =
        widget.item.badgeCount != null && widget.item.badgeCount! > 0;
    final hasQuickAction = widget.item.quickAction != null;
    final hasPreview = widget.item.previewText != null;

    return MouseRegion(
      onEnter: (_) => _onHover(true),
      onExit: (_) => _onHover(false),
      child: ScaleTransition(
        scale: _scaleAnimation,
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: _onTap,
            borderRadius: BorderRadius.circular(AppTheme.radiusLg),
            child: Ink(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(AppTheme.radiusLg),
                border: Border.all(
                  color: _isHovered
                      ? widget.item.color.withValues(alpha: 0.25)
                      : dashboardBorderLight,
                  width: 1,
                ),
                boxShadow: _isHovered
                    ? [
                        BoxShadow(
                          color: widget.item.color.withValues(alpha: 0.08),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ]
                    : AppTheme.subtleShadow,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: widget.item.color.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(AppTheme.radiusMd),
                        ),
                        child: Icon(
                          widget.item.icon,
                          color: widget.item.color,
                          size: 20,
                        ),
                      ),
                      const Spacer(),
                      if (hasBadge)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 7,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: widget.item.color,
                            borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                          ),
                          child: Text(
                            widget.item.badgeCount.toString(),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      const SizedBox(width: 6),
                      Icon(
                        AppIcons.arrow_forward_ios_rounded,
                        size: 12,
                        color: Colors.grey.shade400,
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.item.title,
                              maxLines: _isExpanded ? 10 : 3,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontFamily: AppTheme.fontFamily,
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: dashboardTextDark,
                                height: 1.2,
                              ),
                            ),
                            if (hasPreview) ...[
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 7,
                                  vertical: 3,
                                ),
                                decoration: BoxDecoration(
                                  color: widget.item.color.withValues(alpha: 0.08),
                                  borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                                ),
                                child: Text(
                                  widget.item.previewText!,
                                  style: TextStyle(
                                    fontFamily: AppTheme.fontFamily,
                                    fontSize: 10,
                                    color: widget.item.color,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                            if (hasProgress) ...[
                              const SizedBox(height: 6),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: LinearProgressIndicator(
                                  value: widget.item.progressValue,
                                  backgroundColor: widget.item.color
                                      .withValues(alpha: 0.1),
                                  valueColor: AlwaysStoppedAnimation(
                                    widget.item.color,
                                  ),
                                  minHeight: 3,
                                ),
                              ),
                            ],
                          ],
                        ),
                        if (hasQuickAction)
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            decoration: BoxDecoration(
                              color: widget.item.color.withValues(alpha: 0.08),
                              borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                            ),
                            child: Text(
                              widget.item.quickAction!,
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontFamily: AppTheme.fontFamily,
                                fontSize: 11,
                                color: widget.item.color,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        if (!hasQuickAction && widget.item.subtitle.length > 50)
                          Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: GestureDetector(
                              onTap: () =>
                                  setState(() => _isExpanded = !_isExpanded),
                              child: Text(
                                _isExpanded ? 'Show less' : 'Show more',
                                style: TextStyle(
                                  fontFamily: AppTheme.fontFamily,
                                  fontSize: 10,
                                  color: widget.item.color,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class ModulePlaceholderPage extends StatelessWidget {
  const ModulePlaceholderPage({
    super.key,
    required this.title,
    required this.icon,
    required this.accentColor,
    this.subtitle = '',
  });

  final String title;
  final IconData icon;
  final Color accentColor;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: AppSectionScaffold(
        title: title,
        subtitle: subtitle,
        badges: const ['SRMS Mobile'],
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: accentColor.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(AppTheme.radiusLg),
              border: Border.all(color: accentColor.withValues(alpha: 0.2)),
            ),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: accentColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(AppTheme.radiusMd),
                  ),
                  child: Icon(icon, color: accentColor, size: 24),
                ),
                const SizedBox(width: 14),
                const Expanded(
                  child: Text(
                    'Module shell ready for the next API integration.',
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      height: 1.35,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
