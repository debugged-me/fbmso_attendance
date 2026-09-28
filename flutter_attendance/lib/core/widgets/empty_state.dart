import 'package:flutter/material.dart';

import '../design/components/components.dart';

/// Older empty-state API, drawn with the design system's [AppEmptyState].
class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    required this.subtitle,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: AppEmptyState(
        icon: icon,
        title: title,
        subtitle: subtitle,
        action: actionLabel,
        onAction: onAction,
      ),
    );
  }
}

/// The same empty state inside a card, for use within a scrolling page.
class EmptyStateCard extends StatelessWidget {
  const EmptyStateCard({
    super.key,
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: EdgeInsets.zero,
      child: AppEmptyState(icon: icon, title: title, subtitle: subtitle),
    );
  }
}
