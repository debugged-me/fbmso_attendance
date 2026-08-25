import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class AppSectionScaffold extends StatelessWidget {
  const AppSectionScaffold({
    super.key,
    required this.title,
    required this.subtitle,
    required this.children,
    this.badges = const [],
    this.showHeader = true,
    this.padding = const EdgeInsets.fromLTRB(16, 12, 16, 32),
  });

  final String title;
  final String subtitle;
  final List<String> badges;
  final List<Widget> children;
  final bool showHeader;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    final extraBottomInset = MediaQuery.paddingOf(context).bottom + 24;

    return ListView(
      padding: EdgeInsets.fromLTRB(
        padding.left,
        padding.top,
        padding.right,
        padding.bottom + extraBottomInset,
      ),
      children: [
        if (showHeader) ...[
          _MinimalHeader(title: title, subtitle: subtitle, badges: badges),
          const SizedBox(height: 14),
        ],
        ...children,
      ],
    );
  }
}

// ── Minimal header: title + supporting badges, matching the web dashboard ───
class _MinimalHeader extends StatelessWidget {
  const _MinimalHeader({
    required this.title,
    required this.subtitle,
    required this.badges,
  });

  final String title;
  final String subtitle;
  final List<String> badges;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (title.isNotEmpty)
          Text(
            title,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: AppTheme.textDark,
              fontFamily: AppTheme.fontFamily,
            ),
          ),
        if (subtitle.isNotEmpty)
          Padding(
            padding: EdgeInsets.only(top: title.isNotEmpty ? 4 : 0),
            child: Text(
              subtitle,
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.textMuted,
                height: 1.4,
                fontFamily: AppTheme.fontFamily,
              ),
            ),
          ),
        if (badges.isNotEmpty) ...[
          const SizedBox(height: 10),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: badges
                .map((b) => _HeaderBadge(label: b))
                .toList(growable: false),
          ),
        ],
        const SizedBox(height: 10),
        Container(
          height: 2,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            color: AppTheme.midBlue.withValues(alpha: 0.15),
          ),
        ),
      ],
    );
  }
}

class _HeaderBadge extends StatelessWidget {
  const _HeaderBadge({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: AppTheme.midBlue.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppTheme.midBlue.withValues(alpha: 0.2)),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: AppTheme.midBlue,
          fontFamily: AppTheme.fontFamily,
        ),
      ),
    );
  }
}
