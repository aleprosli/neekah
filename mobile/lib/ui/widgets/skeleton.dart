import 'package:flutter/material.dart';

import '../theme.dart';

/// A gentle shimmer shared by every placeholder below it.
class Shimmer extends StatefulWidget {
  const Shimmer({required this.child, super.key});

  final Widget child;

  @override
  State<Shimmer> createState() => _ShimmerState();
}

class _ShimmerState extends State<Shimmer> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(vsync: this, duration: const Duration(milliseconds: 1400))..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: _controller,
    child: widget.child,
    builder: (context, child) => ShaderMask(
      blendMode: BlendMode.srcATop,
      shaderCallback: (bounds) {
        final dx = bounds.width * (_controller.value * 2 - 0.5);

        return LinearGradient(
          colors: const [Color(0xFFEFE8E0), Color(0xFFFAF6F1), Color(0xFFEFE8E0)],
          stops: const [0.25, 0.5, 0.75],
          transform: _SlideGradient(dx),
        ).createShader(bounds);
      },
      child: child,
    ),
  );
}

class _SlideGradient extends GradientTransform {
  const _SlideGradient(this.dx);

  final double dx;

  @override
  Matrix4 transform(Rect bounds, {TextDirection? textDirection}) => Matrix4.translationValues(dx - bounds.width / 2, 0, 0);
}

class SkeletonBox extends StatelessWidget {
  const SkeletonBox({this.width, this.height = 14, this.radius = 8, super.key});

  final double? width;
  final double height;
  final double radius;

  @override
  Widget build(BuildContext context) => Container(
    width: width,
    height: height,
    decoration: BoxDecoration(color: const Color(0xFFEFE8E0), borderRadius: BorderRadius.circular(radius)),
  );
}

/// Placeholder cards while a screen loads.
class SkeletonList extends StatelessWidget {
  const SkeletonList({this.count = 6, this.header = false, super.key});

  final int count;
  final bool header;

  @override
  Widget build(BuildContext context) => Shimmer(
    child: ListView(
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        if (header) ...[
          const SkeletonBox(height: 150, radius: NRadius.card),
          const SizedBox(height: 16),
          const Row(
            children: [
              Expanded(child: SkeletonBox(height: 96, radius: NRadius.card)),
              SizedBox(width: 12),
              Expanded(child: SkeletonBox(height: 96, radius: NRadius.card)),
            ],
          ),
          const SizedBox(height: 16),
        ],
        for (var i = 0; i < count; i++)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(NRadius.card)),
              child: const Row(
                children: [
                  SkeletonBox(width: 48, height: 48, radius: 14),
                  SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SkeletonBox(width: 150, height: 14),
                        SizedBox(height: 8),
                        SkeletonBox(height: 12),
                        SizedBox(height: 6),
                        SkeletonBox(width: 90, height: 12),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
      ],
    ),
  );
}
