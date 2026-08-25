import 'package:flutter/material.dart';

import '../../theme/app_theme.dart';

/// Design tokens for the flat ("organized list") presentation style.
///
/// The rule this file exists to enforce: screens pick from these scales and
/// nothing else. The card-heavy style grew 136 distinct hex colours and 11
/// distinct corner radii across the feature screens because every widget was
/// free to invent its own. Everything here maps back to [AppTheme] so the two
/// styles stay colour-compatible while they run side by side.
class AppSpace {
  const AppSpace._();

  /// Hairline gap — icon to label.
  static const double xs = 4;

  /// Inside a row: label to value.
  static const double sm = 8;

  /// Row internal vertical padding.
  static const double md = 12;

  /// Screen horizontal gutter, row leading gap.
  static const double lg = 16;

  /// Between a section header and its first row.
  static const double xl = 20;

  /// Between two sections. This is the separator in a flat layout —
  /// whitespace does the job a border used to do.
  static const double xxl = 32;
}

class AppRadius {
  const AppRadius._();

  /// Small chips, avatars, status pills.
  static const double sm = AppTheme.radiusSm;

  /// The only radius a grouped container should use.
  static const double md = AppTheme.radiusMd;

  /// Bottom sheets and the single hero block.
  static const double lg = AppTheme.radiusLg;
}

class AppInk {
  const AppInk._();

  /// Page background. Flat layouts sit on white — a tinted page only reads
  /// correctly when white cards float on top of it.
  static const Color page = Colors.white;

  /// Grouped-row background when a group needs to be set apart.
  static const Color subtle = AppTheme.surface;

  /// The hairline. This replaces `Border.all` + `boxShadow` as the way one
  /// row is separated from the next.
  static const Color rule = Color(0xFFEDF0F5);

  static const Color heading = AppTheme.textDark;
  static const Color body = AppTheme.textDark;
  static const Color muted = AppTheme.textMuted;

  static const Color accent = AppTheme.midBlue;
  static const Color positive = AppTheme.success;
  static const Color caution = AppTheme.warning;
  static const Color critical = AppTheme.error;
}

/// Typography roles. Hierarchy in a flat layout is carried by type and space,
/// not by boxes — so these steps are deliberately far apart.
class AppType {
  const AppType._();

  static const String _f = AppTheme.fontFamily;

  /// Big number: balance, unit count, GWA.
  static const TextStyle display = TextStyle(
    fontFamily: _f,
    fontSize: 32,
    fontWeight: FontWeight.w800,
    color: AppInk.heading,
    height: 1.1,
  );

  /// Screen title.
  static const TextStyle title = TextStyle(
    fontFamily: _f,
    fontSize: 22,
    fontWeight: FontWeight.w800,
    color: AppInk.heading,
    height: 1.2,
  );

  /// Section header above a group of rows.
  static const TextStyle section = TextStyle(
    fontFamily: _f,
    fontSize: 13,
    fontWeight: FontWeight.w700,
    color: AppInk.muted,
    letterSpacing: 0.6,
  );

  /// Primary row text.
  static const TextStyle row = TextStyle(
    fontFamily: _f,
    fontSize: 15,
    fontWeight: FontWeight.w600,
    color: AppInk.body,
    height: 1.3,
  );

  /// Secondary row text, sitting under [row].
  static const TextStyle rowSub = TextStyle(
    fontFamily: _f,
    fontSize: 13,
    fontWeight: FontWeight.w500,
    color: AppInk.muted,
    height: 1.35,
  );

  /// Right-aligned value on a row.
  static const TextStyle value = TextStyle(
    fontFamily: _f,
    fontSize: 15,
    fontWeight: FontWeight.w700,
    color: AppInk.heading,
  );

  /// Chips and status pills.
  static const TextStyle chip = TextStyle(
    fontFamily: _f,
    fontSize: 11,
    fontWeight: FontWeight.w700,
    letterSpacing: 0.3,
  );
}
