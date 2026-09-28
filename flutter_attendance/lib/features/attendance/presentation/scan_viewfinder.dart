import 'package:flutter/material.dart';

import '../../../core/design/tokens/app_tokens.dart';

/// The scanner's target: corner brackets, plus a soft line sweeping inside
/// while the camera is live.
class ScanViewfinder extends StatelessWidget {
  const ScanViewfinder({super.key, this.scanning = true});

  final bool scanning;

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: _ViewfinderPainter(),
      child: scanning ? const _ScanLine() : null,
    );
  }
}

/// Corner brackets marking where to hold the QR code.
class _ViewfinderPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    const len = 34.0;
    const r = 18.0;
    final paint = Paint()
      ..color = Colors.white
      ..strokeWidth = 4
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;
    final w = size.width, h = size.height;
    final path = Path()
      // top-left
      ..moveTo(0, len)
      ..lineTo(0, r)
      ..arcToPoint(const Offset(r, 0), radius: const Radius.circular(r))
      ..lineTo(len, 0)
      // top-right
      ..moveTo(w - len, 0)
      ..lineTo(w - r, 0)
      ..arcToPoint(Offset(w, r), radius: const Radius.circular(r))
      ..lineTo(w, len)
      // bottom-right
      ..moveTo(w, h - len)
      ..lineTo(w, h - r)
      ..arcToPoint(Offset(w - r, h), radius: const Radius.circular(r))
      ..lineTo(w - len, h)
      // bottom-left
      ..moveTo(len, h)
      ..lineTo(r, h)
      ..arcToPoint(Offset(0, h - r), radius: const Radius.circular(r))
      ..lineTo(0, h - len);
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// A soft line sweeping the viewfinder: shows the camera is live.
class _ScanLine extends StatefulWidget {
  const _ScanLine();

  @override
  State<_ScanLine> createState() => _ScanLineState();
}

class _ScanLineState extends State<_ScanLine>
    with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1800),
  )..repeat(reverse: true);

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (context, _) => Align(
        alignment: Alignment(
            0, Curves.easeInOut.transform(_c.value) * 1.6 - 0.8),
        child: Container(
          height: 2,
          margin: const EdgeInsets.symmetric(horizontal: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(2),
            gradient: LinearGradient(
              colors: [
                AppInk.accent.withValues(alpha: 0),
                const Color(0xFF7FA0FF),
                AppInk.accent.withValues(alpha: 0),
              ],
            ),
            boxShadow: const [
              BoxShadow(color: Color(0x662F5BEA), blurRadius: 8),
            ],
          ),
        ),
      ),
    );
  }
}

