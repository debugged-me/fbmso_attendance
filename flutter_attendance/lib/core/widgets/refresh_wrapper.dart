import 'package:flutter/material.dart';

class RefreshWrapper extends StatelessWidget {
  const RefreshWrapper({
    super.key,
    required this.child,
    required this.onRefresh,
    this.backgroundColor,
  });

  final Widget child;
  final Future<void> Function() onRefresh;
  final Color? backgroundColor;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      backgroundColor: backgroundColor ?? Colors.white,
      color: const Color(0xFF0F766E),
      strokeWidth: 2.5,
      displacement: 40,
      child: child,
    );
  }
}
