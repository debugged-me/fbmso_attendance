import 'package:flutter/material.dart';

import '../design/tokens/app_tokens.dart';

/// A grey placeholder block with a soft shimmer sweeping across it.
class SkeletonLoader extends StatefulWidget {
  const SkeletonLoader({super.key, this.width, this.height, this.borderRadius});

  final double? width;
  final double? height;
  final BorderRadius? borderRadius;

  @override
  State<SkeletonLoader> createState() => _SkeletonLoaderState();
}

class _SkeletonLoaderState extends State<SkeletonLoader>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1400),
  )..repeat();

  static const _base = Color(0xFFEEF0F3);
  static const _highlight = Color(0xFFF8F9FB);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        final t = Curves.easeInOut.transform(_controller.value);
        return Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            borderRadius: widget.borderRadius ?? BorderRadius.circular(8),
            gradient: LinearGradient(
              begin: Alignment(-3 + t * 4, 0),
              end: Alignment(-1 + t * 4, 0),
              colors: const [_base, _highlight, _base],
            ),
          ),
        );
      },
    );
  }
}

class DashboardTileSkeleton extends StatelessWidget {
  const DashboardTileSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppInk.rule),
        boxShadow: AppShadow.xs,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SkeletonLoader(
            width: 38,
            height: 38,
            borderRadius: BorderRadius.circular(12),
          ),
          const SizedBox(height: 12),
          const SkeletonLoader(height: 14, width: 110),
          const SizedBox(height: 8),
          const SkeletonLoader(height: 10, width: 70),
        ],
      ),
    );
  }
}

class ListSkeleton extends StatelessWidget {
  const ListSkeleton({super.key, required this.itemCount});

  final int itemCount;

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      itemCount: itemCount,
      itemBuilder: (context, index) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppRadius.lg),
              border: Border.all(color: AppInk.rule),
            ),
            child: Row(
              children: [
                SkeletonLoader(
                  width: 44,
                  height: 44,
                  borderRadius: BorderRadius.circular(12),
                ),
                const SizedBox(width: 14),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SkeletonLoader(height: 13, width: 160),
                      SizedBox(height: 8),
                      SkeletonLoader(height: 11, width: 100),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class CardSkeleton extends StatelessWidget {
  const CardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppInk.rule),
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SkeletonLoader(height: 22, width: 110),
          SizedBox(height: 16),
          SkeletonLoader(height: 14, width: double.infinity),
          SizedBox(height: 8),
          SkeletonLoader(height: 14, width: double.infinity),
          SizedBox(height: 8),
          SkeletonLoader(height: 14, width: 200),
        ],
      ),
    );
  }
}
