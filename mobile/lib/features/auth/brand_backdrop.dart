import 'package:flutter/material.dart';

import '../../ui/theme.dart';

/// Deep wine gradient with a faint gold arch, for the splash and login hero.
class BrandBackdrop extends StatelessWidget {
  const BrandBackdrop({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: const BoxDecoration(
      gradient: LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF8E2236), NColors.wineDark, Color(0xFF5A1B2A)],
      ),
    ),
    child: CustomPaint(painter: _ArchPainter(), child: child),
  );
}

class _ArchPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.2
      ..color = NColors.goldLight.withValues(alpha: 0.18);
    final center = Offset(size.width * 0.85, size.height * 0.08);
    for (var i = 1; i <= 4; i++) {
      canvas.drawCircle(center, size.width * 0.22 * i, paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// The Neekah mark on a rounded tile.
class NeekahMark extends StatelessWidget {
  const NeekahMark({this.size = 72, super.key});

  final double size;

  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(size * 0.28),
      border: Border.all(color: NColors.goldLight.withValues(alpha: 0.7), width: 1.5),
      boxShadow: const [BoxShadow(color: Color(0x40000000), blurRadius: 24, offset: Offset(0, 10))],
    ),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(size * 0.28 - 1.5),
      child: Image.asset('assets/images/neekah-mark-512.png', fit: BoxFit.cover),
    ),
  );
}
