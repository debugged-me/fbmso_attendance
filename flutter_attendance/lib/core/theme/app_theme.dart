import 'package:flutter/material.dart';

class AppTheme {
  static const String fontFamily = 'InstrumentSans';

  // ─── Brand Palette ────────────────────────────────────────────────────────
  /// Mid Blue — primary brand color, buttons, links, active states
  static const Color midBlue = Color(0xFF285CCC);

  /// Buttermilk — warm accent, highlights, badges, notice banners
  static const Color buttermilk = Color(0xFFFFF2BD);

  // ─── Supporting Shades ────────────────────────────────────────────────────
  /// Deep ink for headings and body text
  static const Color textDark = Color(0xFF0F172A);

  /// Muted slate for secondary text
  static const Color textMuted = Color(0xFF4B5A72);

  /// Subtle blue-tinted surface background
  static const Color surface = Color(0xFFF5F7FD);

  /// Card border — light cool gray
  static const Color cardBorder = Color(0xFFD6DFF5);

  /// Nav indicator — soft buttermilk tint
  static const Color navIndicator = Color(0xFFFFF6D0);

  // ─── Semantic Colors ──────────────────────────────────────────────────────
  /// Success / positive actions
  static const Color success = Color(0xFF16A34A);

  /// Warning / caution
  static const Color warning = Color(0xFFF59E0B);

  /// Error / destructive actions
  static const Color error = Color(0xFFDC2626);

  /// Info / announcements
  static const Color info = Color(0xFF2563EB);

  // ─── Accent Colors (module-level) ─────────────────────────────────────────
  /// Teal — academics, profile
  static const Color teal = Color(0xFF0F766E);

  /// Purple — QR, requirements
  static const Color purple = Color(0xFF7C3AED);

  /// Orange — payments, assessments
  static const Color orange = Color(0xFFEA580C);

  // ─── Radius Scale ─────────────────────────────────────────────────────────
  static const double radiusSm = 8;
  static const double radiusMd = 12;
  static const double radiusLg = 16;
  static const double radiusXl = 24;

  // ─── Shadow Tokens ────────────────────────────────────────────────────────
  static List<BoxShadow> get cardShadow => [
    BoxShadow(
      color: const Color(0x0D0F172A),
      blurRadius: 24,
      offset: const Offset(0, 10),
      spreadRadius: 2,
    ),
    BoxShadow(
      color: const Color(0x050F172A),
      blurRadius: 8,
      offset: const Offset(0, 2),
    ),
  ];

  static List<BoxShadow> get subtleShadow => [
    BoxShadow(
      color: const Color(0x0A0F172A),
      blurRadius: 16,
      offset: const Offset(0, 4),
    ),
  ];

  // Private aliases for internal use
  static const Color _textDark = textDark;
  static const Color _textMuted = textMuted;
  static const Color _surface = surface;
  static const Color _cardBorder = cardBorder;
  static const Color _navIndicator = navIndicator;

  static ThemeData build() {
    final scheme =
        ColorScheme.fromSeed(
          seedColor: midBlue,
          brightness: Brightness.light,
        ).copyWith(
          primary: midBlue,
          onPrimary: Colors.white,
          secondary: buttermilk,
          onSecondary: _textDark,
          surface: _surface,
          onSurface: _textDark,
        );

    return ThemeData(
      useMaterial3: true,
      fontFamily: fontFamily,
      colorScheme: scheme,
      scaffoldBackgroundColor: _surface,
      appBarTheme: AppBarTheme(
        backgroundColor: _surface,
        foregroundColor: _textDark,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: const TextStyle(
          fontFamily: fontFamily,
          fontSize: 20,
          fontWeight: FontWeight.w700,
          color: _textDark,
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(24),
          side: const BorderSide(color: _cardBorder),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        indicatorColor: _navIndicator,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          return TextStyle(
            fontFamily: fontFamily,
            fontSize: 11,
            color: states.contains(WidgetState.selected) ? midBlue : _textMuted,
            fontWeight: states.contains(WidgetState.selected)
                ? FontWeight.w700
                : FontWeight.w500,
          );
        }),
      ),
      // Material 3 defaults every button to a stadium (fully oval) shape. The
      // app uses soft-square corners everywhere else, so pin all four button
      // types to radiusMd here — that keeps shapes consistent across modules
      // without each screen having to pass its own `shape:`.
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: midBlue,
          foregroundColor: Colors.white,
          textStyle: const TextStyle(
            fontFamily: fontFamily,
            fontWeight: FontWeight.w700,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMd),
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          textStyle: const TextStyle(
            fontFamily: fontFamily,
            fontWeight: FontWeight.w700,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMd),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          textStyle: const TextStyle(
            fontFamily: fontFamily,
            fontWeight: FontWeight.w700,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMd),
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          textStyle: const TextStyle(
            fontFamily: fontFamily,
            fontWeight: FontWeight.w700,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMd),
          ),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: _textDark,
        contentTextStyle: const TextStyle(
          fontFamily: fontFamily,
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
        ),
        behavior: SnackBarBehavior.floating,
        elevation: 6,
      ),
      textTheme: const TextTheme(
        headlineMedium: TextStyle(
          fontFamily: fontFamily,
          fontSize: 30,
          fontWeight: FontWeight.w800,
          color: _textDark,
        ),
        headlineSmall: TextStyle(
          fontFamily: fontFamily,
          fontSize: 24,
          fontWeight: FontWeight.w700,
          color: _textDark,
        ),
        titleLarge: TextStyle(
          fontFamily: fontFamily,
          fontSize: 20,
          fontWeight: FontWeight.w700,
          color: _textDark,
        ),
        titleMedium: TextStyle(
          fontFamily: fontFamily,
          fontSize: 16,
          fontWeight: FontWeight.w700,
          color: _textDark,
        ),
        bodyMedium: TextStyle(
          fontFamily: fontFamily,
          fontSize: 14,
          height: 1.5,
          color: _textMuted,
        ),
        bodySmall: TextStyle(
          fontFamily: fontFamily,
          fontSize: 12,
          color: _textMuted,
        ),
      ),
    );
  }
}
