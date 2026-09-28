
import 'package:flutter/material.dart';

import '../../theme/app_theme.dart';

/// Design tokens.
///
/// The rule this file exists to enforce: screens pick from these scales and
/// nothing else. Hierarchy comes from type and space, separation from
/// hairlines and a light shadow, and colour is kept for actions and status.
/// Everything here maps back to [AppTheme] so older call sites that still use
/// its constants stay in step.
class AppSpace {
  const AppSpace._();

  /// Hairline gap: icon to label.
  static const double xs = 4;

  /// Inside a row: label to value.
  static const double sm = 8;

  /// Row internal vertical padding.
  static const double md = 12;

  /// Screen horizontal gutter, row leading gap.
  static const double lg = 16;

  /// Between a section header and its first row.
  static const double xl = 20;

  /// Between two sections: whitespace does the job a border used to do.
  static const double xxl = 28;
}

class AppRadius {
  const AppRadius._();

  /// Small chips, icon boxes, inline badges.
  static const double sm = AppTheme.radiusSm;

  /// Inputs, grouped rows, buttons that are not pills.
  static const double md = AppTheme.radiusMd;

  /// Cards.
  static const double lg = AppTheme.radiusLg;

  /// Sheets and dialogs.
  static const double xl = AppTheme.radiusXl;

  /// Fully rounded (buttons, pills, avatars).
  static const double pill = 999;
}

class AppInk {
  const AppInk._();

  /// Page canvas. White surfaces sit on it.
  static const Color page = AppTheme.canvas;

  /// A white surface (cards, sheets, inputs).
  static const Color surface = Colors.white;

  /// Quiet fill for grouped or disabled areas and skeletons.
  static const Color subtle = AppTheme.ink100;

  /// The hairline between rows and around cards.
  static const Color rule = AppTheme.ink200;

  /// A stronger hairline: input and outline-button borders.
  static const Color ruleStrong = AppTheme.ink300;

  static const Color heading = AppTheme.ink900;
  static const Color body = AppTheme.ink800;

  /// Secondary text that still needs to be read (descriptions, values).
  static const Color secondary = AppTheme.ink600;
  static const Color muted = AppTheme.ink500;

  /// Placeholders, disabled icons, chevrons.
  static const Color faint = AppTheme.ink400;

  static const Color accent = AppTheme.accent;
  static const Color accentSoft = AppTheme.accentSoft;
  static const Color accentInk = AppTheme.accentInk;

  static const Color positive = AppTheme.success;
  static const Color caution = AppTheme.warning;
  static const Color critical = AppTheme.error;

  /// Informational (scheduled states, sync notices).
  static const Color info = AppTheme.info;

  /// A readable text colour for [tone] shown on its own 10% tint.
  static Color onTint(Color tone) => Color.lerp(tone, heading, 0.22)!;
}

/// Elevation scale. Surfaces use a hairline plus [xs]; only floating chrome
/// gets [lg].
class AppShadow {
  const AppShadow._();

  static const List<BoxShadow> xs = AppTheme.shadowXs;
  static const List<BoxShadow> sm = AppTheme.shadowSm;
  static const List<BoxShadow> lg = AppTheme.shadowLg;
}

/// Ordered palette for charts and breakdown bars: pick by index, never a
/// raw hex in a screen.
class AppChart {
  const AppChart._();

  static const List<Color> slices = [
    Color(0xFF2F5BEA),
    Color(0xFF7A5AF8),
    Color(0xFFEE46BC),
    Color(0xFFF79009),
    Color(0xFF17B26A),
    Color(0xFF06AED4),
    Color(0xFFFAC515),
    Color(0xFFF04438),
    Color(0xFF667085),
  ];

  static Color at(int i) => slices[i % slices.length];
}

/// Typography roles. The steps are deliberately far apart so a screen reads
/// by size and weight before colour.
class AppType {
  const AppType._();

  static const String _f = AppTheme.fontFamily;
  static const List<FontFeature> _tabular = [FontFeature.tabularFigures()];

  /// Big number: balance, head count, totals.
  static const TextStyle display = TextStyle(
    fontFamily: _f,
    fontSize: 32,
    fontWeight: FontWeight.w700,
    color: AppInk.heading,
    height: 1.1,
    letterSpacing: -0.8,
    fontFeatures: _tabular,
  );

  /// Screen title in the page body.
  static const TextStyle title = TextStyle(
    fontFamily: _f,
    fontSize: 26,
    fontWeight: FontWeight.w700,
    color: AppInk.heading,
    height: 1.15,
    letterSpacing: -0.6,
  );

  /// Card and sheet titles.
  static const TextStyle headline = TextStyle(
    fontFamily: _f,
    fontSize: 18,
    fontWeight: FontWeight.w700,
    color: AppInk.heading,
    height: 1.25,
    letterSpacing: -0.3,
  );

  /// Label above a group of rows (sentence case).
  static const TextStyle section = TextStyle(
    fontFamily: _f,
    fontSize: 13,
    fontWeight: FontWeight.w600,
    color: AppInk.muted,
    letterSpacing: 0.1,
  );

  /// Primary row text.
  static const TextStyle row = TextStyle(
    fontFamily: _f,
    fontSize: 15,
    fontWeight: FontWeight.w600,
    color: AppInk.heading,
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

  /// Running text.
  static const TextStyle body = TextStyle(
    fontFamily: _f,
    fontSize: 15,
    fontWeight: FontWeight.w400,
    color: AppInk.secondary,
    height: 1.5,
  );

  /// Small print under a control or a figure.
  static const TextStyle caption = TextStyle(
    fontFamily: _f,
    fontSize: 12.5,
    fontWeight: FontWeight.w500,
    color: AppInk.muted,
    height: 1.35,
  );

  /// Form field label.
  static const TextStyle label = TextStyle(
    fontFamily: _f,
    fontSize: 13.5,
    fontWeight: FontWeight.w600,
    color: AppTheme.ink700,
  );

  /// Right-aligned value on a row.
  static const TextStyle value = TextStyle(
    fontFamily: _f,
    fontSize: 15,
    fontWeight: FontWeight.w600,
    color: AppInk.heading,
    fontFeatures: _tabular,
  );

  /// Chips and status pills.
  static const TextStyle chip = TextStyle(
    fontFamily: _f,
    fontSize: 12,
    fontWeight: FontWeight.w600,
    letterSpacing: 0.1,
  );
}

/// Motion: short and eased, used for press feedback and state changes.
class AppMotion {
  const AppMotion._();

  static const Duration fast = Duration(milliseconds: 120);
  static const Duration base = Duration(milliseconds: 200);
  static const Duration slow = Duration(milliseconds: 320);
  static const Curve ease = Curves.easeOutCubic;
}
