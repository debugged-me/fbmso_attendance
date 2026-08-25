import 'dart:math' as math;
import 'package:flutter/material.dart';

/// Squircle shape — a rounded rectangle with continuously smooth corners.
///
/// This is the iOS app-icon shape (a superellipse). It looks like a rounded
/// rectangle but the corners curve smoothly instead of meeting at a tangent,
/// which gives a softer, more premium feel than standard [RoundedRectangleBorder].
///
/// Pass [radius] to control corner size. For buttons we use ~12-14, for cards
/// ~16-20, for large sheets ~24.
class SquircleBorder extends ShapeBorder {
  const SquircleBorder({this.radius = 14, this.side = BorderSide.none});

  final double radius;
  final BorderSide side;

  @override
  EdgeInsetsGeometry get dimensions => EdgeInsets.all(side.width);

  @override
  Path getInnerPath(Rect rect, {TextDirection? textDirection}) {
    return _squirclePath(rect.deflate(side.width), radius);
  }

  @override
  Path getOuterPath(Rect rect, {TextDirection? textDirection}) {
    return _squirclePath(rect, radius);
  }

  @override
  void paint(Canvas canvas, Rect rect, {TextDirection? textDirection}) {
    if (side.style == BorderStyle.solid && side.width > 0) {
      canvas.drawPath(
        getOuterPath(rect),
        side.toPaint(),
      );
    }
  }

  @override
  ShapeBorder scale(double t) {
    return SquircleBorder(radius: radius * t, side: side.scale(t));
  }
}

/// Generates a smooth squircle path using a cubic-bezier approximation.
/// This produces corners that look smoother than a standard arc — the
/// visual difference is subtle but it's what makes iOS elements feel premium.
Path _squirclePath(Rect rect, double radius) {
  final w = rect.width;
  final h = rect.height;
  final r = math.min(radius, math.min(w, h) / 2);

  // For very small elements, fall back to a simple rounded rect.
  if (r < 4) {
    return Path()
      ..addRRect(RRect.fromRectAndRadius(rect, Radius.circular(r)));
  }

  // Smoothness factor — 0.55 gives a near-perfect superellipse look
  // without the performance cost of computing the actual superellipse.
  final s = r * 0.55;

  return Path()
    ..moveTo(rect.left + r, rect.top)
    // Top edge
    ..lineTo(rect.right - r, rect.top)
    // Top-right corner
    ..cubicTo(rect.right - s, rect.top, rect.right, rect.top + s, rect.right, rect.top + r)
    // Right edge
    ..lineTo(rect.right, rect.bottom - r)
    // Bottom-right corner
    ..cubicTo(rect.right, rect.bottom - s, rect.right - s, rect.bottom, rect.right - r, rect.bottom)
    // Bottom edge
    ..lineTo(rect.left + r, rect.bottom)
    // Bottom-left corner
    ..cubicTo(rect.left + s, rect.bottom, rect.left, rect.bottom - s, rect.left, rect.bottom - r)
    // Left edge
    ..lineTo(rect.left, rect.top + r)
    // Top-left corner
    ..cubicTo(rect.left, rect.top + s, rect.left + s, rect.top, rect.left + r, rect.top)
    ..close();
}
