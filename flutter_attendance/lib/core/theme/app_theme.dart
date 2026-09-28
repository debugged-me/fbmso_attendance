import 'package:flutter/material.dart';

/// Palette and Material theme.
///
/// Light, neutral and quiet: a pale grey canvas, white surfaces separated by
/// hairlines and soft shadows, one blue accent (taken from the app mark's
/// gradient) used for actions and active states only, and semantic colours
/// reserved for status. Screens should reach for [AppInk] / [AppType] in
/// `design/tokens/app_tokens.dart`; the constants here back those tokens and
/// keep older call sites compiling.
class AppTheme {
  static const String fontFamily = 'InstrumentSans';

  // ─── Neutrals (cool grey scale) ───────────────────────────────────────────
  static const Color ink900 = Color(0xFF101828);
  static const Color ink800 = Color(0xFF1D2939);
  static const Color ink700 = Color(0xFF344054);
  static const Color ink600 = Color(0xFF475467);
  static const Color ink500 = Color(0xFF667085);
  static const Color ink400 = Color(0xFF98A2B3);
  static const Color ink300 = Color(0xFFD0D5DD);
  static const Color ink200 = Color(0xFFEAECF0);
  static const Color ink100 = Color(0xFFF2F4F7);
  static const Color ink50 = Color(0xFFF9FAFB);

  /// Page canvas behind white surfaces.
  static const Color canvas = Color(0xFFF5F6F8);

  // ─── Brand ────────────────────────────────────────────────────────────────
  /// Accent blue: buttons, links, focus rings, active navigation.
  static const Color accent = Color(0xFF2F5BEA);

  /// Accent tint for selected states and tonal buttons.
  static const Color accentSoft = Color(0xFFEEF2FE);

  /// Accent for text sitting on [accentSoft].
  static const Color accentInk = Color(0xFF1F45C4);

  /// The app mark's gradient ends: used only for small brand details.
  static const Color brandCyan = Color(0xFF3FC8F4);
  static const Color brandViolet = Color(0xFF6552EE);

  /// Kept for older call sites: the brand blue is now [accent].
  static const Color midBlue = accent;

  /// Warm notice tint.
  static const Color buttermilk = Color(0xFFFEF7DC);

  // ─── Supporting shades (older names, mapped onto the scale) ───────────────
  static const Color textDark = ink900;
  static const Color textMuted = ink500;
  static const Color surface = canvas;
  static const Color cardBorder = ink200;
  static const Color navIndicator = accentSoft;

  // ─── Semantic colours ─────────────────────────────────────────────────────
  static const Color success = Color(0xFF079455);
  static const Color warning = Color(0xFFDC6803);
  static const Color error = Color(0xFFD92D20);
  static const Color info = Color(0xFF1570EF);

  // ─── Module accents ───────────────────────────────────────────────────────
  static const Color teal = Color(0xFF0E9384);
  static const Color purple = Color(0xFF7A5AF8);
  static const Color orange = Color(0xFFEF6820);

  // ─── Radius scale ─────────────────────────────────────────────────────────
  static const double radiusSm = 10;
  static const double radiusMd = 14;
  static const double radiusLg = 20;
  static const double radiusXl = 28;

  // ─── Shadows ──────────────────────────────────────────────────────────────
  /// Resting surface: barely-there lift under a hairline border.
  static const List<BoxShadow> shadowXs = [
    BoxShadow(color: Color(0x0D101828), blurRadius: 2, offset: Offset(0, 1)),
  ];

  /// Cards that should read as a layer above the canvas.
  static const List<BoxShadow> shadowSm = [
    BoxShadow(color: Color(0x0F101828), blurRadius: 3, offset: Offset(0, 1)),
    BoxShadow(color: Color(0x08101828), blurRadius: 12, offset: Offset(0, 4)),
  ];

  /// Floating chrome: bottom bar, popovers, the elevated card.
  static const List<BoxShadow> shadowLg = [
    BoxShadow(
        color: Color(0x14101828),
        blurRadius: 24,
        spreadRadius: -4,
        offset: Offset(0, 12)),
    BoxShadow(
        color: Color(0x08101828),
        blurRadius: 8,
        spreadRadius: -2,
        offset: Offset(0, 4)),
  ];

  static List<BoxShadow> get cardShadow => shadowSm;
  static List<BoxShadow> get subtleShadow => shadowXs;

  static const TextStyle _t = TextStyle(fontFamily: fontFamily);

  static ThemeData build() {
    final scheme = ColorScheme.fromSeed(
      seedColor: accent,
      brightness: Brightness.light,
    ).copyWith(
      primary: accent,
      onPrimary: Colors.white,
      primaryContainer: accentSoft,
      onPrimaryContainer: accentInk,
      secondary: ink700,
      onSecondary: Colors.white,
      secondaryContainer: ink100,
      onSecondaryContainer: ink900,
      surface: Colors.white,
      onSurface: ink900,
      onSurfaceVariant: ink600,
      surfaceContainerLowest: Colors.white,
      surfaceContainerLow: ink50,
      surfaceContainer: ink100,
      surfaceContainerHigh: ink100,
      surfaceContainerHighest: ink200,
      surfaceTint: Colors.transparent,
      outline: ink300,
      outlineVariant: ink200,
      error: error,
      onError: Colors.white,
      errorContainer: const Color(0xFFFEF3F2),
      onErrorContainer: const Color(0xFFB42318),
      shadow: ink900,
      scrim: const Color(0x66101828),
    );

    final inputBorder = OutlineInputBorder(
      borderRadius: BorderRadius.circular(radiusMd),
      borderSide: const BorderSide(color: ink300),
    );

    final pill = WidgetStateProperty.all<OutlinedBorder>(const StadiumBorder());
    final buttonText = WidgetStateProperty.all(
      _t.copyWith(fontSize: 15, fontWeight: FontWeight.w600, letterSpacing: 0),
    );
    final buttonSize = WidgetStateProperty.all(const Size(64, 46));
    final buttonPadding = WidgetStateProperty.all(
      const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
    );

    return ThemeData(
      useMaterial3: true,
      fontFamily: fontFamily,
      colorScheme: scheme,
      scaffoldBackgroundColor: canvas,
      canvasColor: canvas,
      highlightColor: const Color(0x0A101828),
      splashColor: const Color(0x0F101828),
      dividerColor: ink200,
      iconTheme: const IconThemeData(color: ink700, size: 22),
      appBarTheme: AppBarTheme(
        backgroundColor: canvas,
        foregroundColor: ink900,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleSpacing: 4,
        iconTheme: const IconThemeData(color: ink900, size: 22),
        actionsIconTheme: const IconThemeData(color: ink700, size: 22),
        titleTextStyle: _t.copyWith(
          fontSize: 17,
          fontWeight: FontWeight.w600,
          letterSpacing: -0.2,
          color: ink900,
        ),
      ),
      iconButtonTheme: IconButtonThemeData(
        style: IconButton.styleFrom(
          foregroundColor: ink700,
          highlightColor: const Color(0x0F101828),
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        margin: EdgeInsets.zero,
        shadowColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          side: const BorderSide(color: ink200),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        indicatorColor: accentSoft,
        indicatorShape: const StadiumBorder(),
        height: 68,
        elevation: 0,
        iconTheme: WidgetStateProperty.resolveWith((states) => IconThemeData(
              size: 24,
              color: states.contains(WidgetState.selected) ? accent : ink500,
            )),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final on = states.contains(WidgetState.selected);
          return _t.copyWith(
            fontSize: 12,
            color: on ? ink900 : ink500,
            fontWeight: on ? FontWeight.w600 : FontWeight.w500,
          );
        }),
      ),
      // Every Material button is a pill with the same type and height, so a
      // stray FilledButton in a dialog matches AppButton on the page.
      filledButtonTheme: FilledButtonThemeData(
        style: ButtonStyle(
          shape: pill,
          textStyle: buttonText,
          minimumSize: buttonSize,
          padding: buttonPadding,
          elevation: WidgetStateProperty.all(0),
          backgroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.disabled) ? ink100 : accent),
          foregroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.disabled) ? ink400 : Colors.white),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ButtonStyle(
          shape: pill,
          textStyle: buttonText,
          minimumSize: buttonSize,
          padding: buttonPadding,
          elevation: WidgetStateProperty.all(0),
          shadowColor: WidgetStateProperty.all(Colors.transparent),
          backgroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.disabled) ? ink100 : accent),
          foregroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.disabled) ? ink400 : Colors.white),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: ButtonStyle(
          shape: pill,
          textStyle: buttonText,
          minimumSize: buttonSize,
          padding: buttonPadding,
          backgroundColor: WidgetStateProperty.all(Colors.white),
          foregroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.disabled) ? ink400 : ink700),
          side: WidgetStateProperty.all(const BorderSide(color: ink300)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: ButtonStyle(
          shape: pill,
          textStyle: buttonText,
          padding: WidgetStateProperty.all(
            const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          ),
          foregroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.disabled) ? ink400 : accent),
        ),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: ButtonStyle(
          shape: pill,
          side: WidgetStateProperty.all(const BorderSide(color: ink300)),
          backgroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.selected) ? accentSoft : Colors.white),
          foregroundColor: WidgetStateProperty.resolveWith((s) =>
              s.contains(WidgetState.selected) ? accentInk : ink700),
          textStyle: WidgetStateProperty.all(
            _t.copyWith(fontSize: 13.5, fontWeight: FontWeight.w600),
          ),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: Colors.white,
        selectedColor: accentSoft,
        disabledColor: ink100,
        side: const BorderSide(color: ink200),
        shape: const StadiumBorder(),
        labelStyle: _t.copyWith(
            fontSize: 13, fontWeight: FontWeight.w600, color: ink700),
        secondaryLabelStyle: _t.copyWith(
            fontSize: 13, fontWeight: FontWeight.w600, color: accentInk),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        checkmarkColor: accentInk,
        iconTheme: const IconThemeData(color: ink600, size: 16),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        isDense: false,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
        hintStyle: _t.copyWith(
            fontSize: 15, fontWeight: FontWeight.w400, color: ink400),
        labelStyle: _t.copyWith(
            fontSize: 15, fontWeight: FontWeight.w500, color: ink500),
        floatingLabelStyle: _t.copyWith(
            fontSize: 14, fontWeight: FontWeight.w600, color: accent),
        helperStyle: _t.copyWith(fontSize: 12.5, color: ink500),
        errorStyle: _t.copyWith(
            fontSize: 12.5, fontWeight: FontWeight.w500, color: error),
        prefixIconColor: ink500,
        suffixIconColor: ink500,
        border: inputBorder,
        enabledBorder: inputBorder,
        disabledBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: ink200),
        ),
        focusedBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: accent, width: 1.5),
        ),
        errorBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: error),
        ),
        focusedErrorBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: error, width: 1.5),
        ),
      ),
      listTileTheme: ListTileThemeData(
        iconColor: ink600,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16),
        titleTextStyle: _t.copyWith(
            fontSize: 15, fontWeight: FontWeight.w600, color: ink900),
        subtitleTextStyle: _t.copyWith(
            fontSize: 13, fontWeight: FontWeight.w500, color: ink500),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: ink900,
        contentTextStyle: _t.copyWith(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: Colors.white,
        ),
        actionTextColor: const Color(0xFF9DB5FF),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
        ),
        behavior: SnackBarBehavior.floating,
        elevation: 0,
        insetPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: accent,
        foregroundColor: Colors.white,
        elevation: 2,
        focusElevation: 2,
        hoverElevation: 3,
        highlightElevation: 1,
        shape: StadiumBorder(),
        extendedTextStyle: TextStyle(
          fontFamily: fontFamily,
          fontSize: 15,
          fontWeight: FontWeight.w600,
        ),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(radiusXl)),
        ),
        titleTextStyle: _t.copyWith(
          fontSize: 19,
          fontWeight: FontWeight.w700,
          letterSpacing: -0.3,
          color: ink900,
        ),
        contentTextStyle: _t.copyWith(
          fontSize: 15,
          height: 1.5,
          color: ink600,
        ),
        barrierColor: const Color(0x66101828),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        modalBackgroundColor: Colors.white,
        elevation: 0,
        modalElevation: 0,
        dragHandleColor: ink300,
        dragHandleSize: Size(36, 4),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radiusXl)),
        ),
        clipBehavior: Clip.antiAlias,
      ),
      popupMenuTheme: PopupMenuThemeData(
        color: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 8,
        shadowColor: const Color(0x33101828),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          side: const BorderSide(color: ink200),
        ),
        textStyle: _t.copyWith(
            fontSize: 14.5, fontWeight: FontWeight.w500, color: ink800),
      ),
      menuTheme: MenuThemeData(
        style: MenuStyle(
          backgroundColor: WidgetStateProperty.all(Colors.white),
          surfaceTintColor: WidgetStateProperty.all(Colors.transparent),
          shape: WidgetStateProperty.all(RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMd),
            side: const BorderSide(color: ink200),
          )),
        ),
      ),
      tabBarTheme: TabBarThemeData(
        labelColor: ink900,
        unselectedLabelColor: ink500,
        indicatorColor: accent,
        indicatorSize: TabBarIndicatorSize.label,
        dividerColor: ink200,
        labelStyle: _t.copyWith(fontSize: 14, fontWeight: FontWeight.w600),
        unselectedLabelStyle:
            _t.copyWith(fontSize: 14, fontWeight: FontWeight.w500),
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.all(Colors.white),
        trackColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected) ? accent : ink200),
        trackOutlineColor: WidgetStateProperty.all(Colors.transparent),
      ),
      checkboxTheme: CheckboxThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
        side: const BorderSide(color: ink300, width: 1.5),
        fillColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected) ? accent : Colors.white),
      ),
      radioTheme: RadioThemeData(
        fillColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected) ? accent : ink300),
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: accent,
        linearTrackColor: ink200,
        circularTrackColor: Colors.transparent,
      ),
      tooltipTheme: TooltipThemeData(
        decoration: BoxDecoration(
          color: ink900,
          borderRadius: BorderRadius.circular(8),
        ),
        textStyle: _t.copyWith(
            fontSize: 12.5, fontWeight: FontWeight.w500, color: Colors.white),
      ),
      datePickerTheme: DatePickerThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusXl),
        ),
        headerForegroundColor: ink900,
        dividerColor: ink200,
      ),
      timePickerTheme: TimePickerThemeData(
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusXl),
        ),
      ),
      dividerTheme: const DividerThemeData(
        color: ink200,
        thickness: 1,
        space: 1,
      ),
      textTheme: TextTheme(
        displaySmall: _t.copyWith(
            fontSize: 34, fontWeight: FontWeight.w700, letterSpacing: -0.8,
            color: ink900),
        headlineMedium: _t.copyWith(
            fontSize: 28, fontWeight: FontWeight.w700, letterSpacing: -0.6,
            color: ink900),
        headlineSmall: _t.copyWith(
            fontSize: 24, fontWeight: FontWeight.w700, letterSpacing: -0.4,
            color: ink900),
        titleLarge: _t.copyWith(
            fontSize: 20, fontWeight: FontWeight.w700, letterSpacing: -0.3,
            color: ink900),
        titleMedium: _t.copyWith(
            fontSize: 16, fontWeight: FontWeight.w600, color: ink900),
        titleSmall: _t.copyWith(
            fontSize: 14, fontWeight: FontWeight.w600, color: ink900),
        bodyLarge: _t.copyWith(fontSize: 16, height: 1.5, color: ink700),
        bodyMedium: _t.copyWith(fontSize: 14, height: 1.5, color: ink600),
        bodySmall: _t.copyWith(fontSize: 12.5, height: 1.4, color: ink500),
        labelLarge: _t.copyWith(fontSize: 14, fontWeight: FontWeight.w600),
        labelMedium: _t.copyWith(
            fontSize: 12.5, fontWeight: FontWeight.w600, color: ink600),
        labelSmall: _t.copyWith(
            fontSize: 11.5, fontWeight: FontWeight.w600, color: ink500),
      ),
    );
  }
}
