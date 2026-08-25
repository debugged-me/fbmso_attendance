import 'package:flutter/material.dart';
import '../theme/app_icons.dart';
import '../theme/app_theme.dart';

class QuickAction {
  const QuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
    this.color,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;
}

class QuickActionsFAB extends StatefulWidget {
  const QuickActionsFAB({
    super.key,
    required this.actions,
    this.mainIcon = AppIcons.add,
    this.mainColor,
  });

  final List<QuickAction> actions;
  final IconData mainIcon;
  final Color? mainColor;

  @override
  State<QuickActionsFAB> createState() => _QuickActionsFABState();
}

class _QuickActionsFABState extends State<QuickActionsFAB>
    with SingleTickerProviderStateMixin {
  bool _isExpanded = false;
  late AnimationController _controller;
  late Animation<double> _rotation;
  late Animation<double> _scale;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 250),
    );
    _rotation = Tween<double>(begin: 0, end: 0.75).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOut),
    );
    _scale = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutBack),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _toggleExpanded() {
    setState(() {
      _isExpanded = !_isExpanded;
      if (_isExpanded) {
        _controller.forward();
      } else {
        _controller.reverse();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final mainColor = widget.mainColor ?? AppTheme.teal;

    return SizedBox(
      width: 56,
      height: 56,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          for (int i = widget.actions.length - 1; i >= 0; i--)
            AnimatedBuilder(
              animation: _scale,
              builder: (context, child) {
                final offset = (widget.actions.length - i) * 60.0;
                return Transform.translate(
                  offset: Offset(0, -offset * _scale.value),
                  child: Opacity(
                    opacity: _scale.value,
                    child: child,
                  ),
                );
              },
              child: FloatingActionButton(
                heroTag: 'action_$i',
                mini: true,
                onPressed: () {
                  widget.actions[i].onTap();
                  _toggleExpanded();
                },
                backgroundColor: widget.actions[i].color ?? mainColor,
                child: Icon(widget.actions[i].icon, size: 20),
              ),
            ),
          FloatingActionButton(
            heroTag: 'main_fab',
            onPressed: _toggleExpanded,
            backgroundColor: mainColor,
            child: AnimatedBuilder(
              animation: _rotation,
              builder: (context, child) {
                return Transform.rotate(
                  angle: _rotation.value * 3.14159,
                  child: Icon(_isExpanded ? AppIcons.close : widget.mainIcon),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
