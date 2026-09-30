import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Neekah brand palette, matching the website.
abstract final class NColors {
  static const wine = Color(0xFFA82133);
  static const wineDark = Color(0xFF7A263A);
  static const wineLight = Color(0xFFFBEDEF);
  static const gold = Color(0xFFC9A24A);
  static const goldLight = Color(0xFFE8C872);
  static const goldSoft = Color(0xFFFBF3DF);
  static const ivory = Color(0xFFFBF8F3);
  static const surface = Color(0xFFFFFFFF);
  static const ink = Color(0xFF2A1E1F);
  static const inkMuted = Color(0xFF6E5F5E);
  static const line = Color(0xFFE3DDD8);
  static const lineSoft = Color(0xFFF1ECE7);
}

/// Foreground/background pair for a status `tone`.
class ToneColors {
  const ToneColors(this.foreground, this.background);

  final Color foreground;
  final Color background;

  static ToneColors of(String tone) => switch (tone) {
    'emerald' => const ToneColors(Color(0xFF047857), Color(0xFFE7F6EF)),
    'amber' => const ToneColors(Color(0xFFB45309), Color(0xFFFDF3E1)),
    'sky' => const ToneColors(Color(0xFF0369A1), Color(0xFFE6F3FB)),
    'brand' => const ToneColors(NColors.wine, NColors.wineLight),
    'red' => const ToneColors(Color(0xFFB91C1C), Color(0xFFFDECEC)),
    _ => const ToneColors(NColors.inkMuted, Color(0xFFF1ECE7)),
  };
}

abstract final class NRadius {
  static const card = 20.0;
  static const small = 14.0;
  static const pill = 999.0;
}

/// Soft, warm elevation used by every card.
const List<BoxShadow> softShadow = [
  BoxShadow(color: Color(0x0F2A1E1F), blurRadius: 18, offset: Offset(0, 6)),
  BoxShadow(color: Color(0x082A1E1F), blurRadius: 3, offset: Offset(0, 1)),
];

const String headingFont = 'Playfair';

/// Playfair's default numerals are old-style; money and counts read better lined up.
const List<FontFeature> liningFigures = [FontFeature.liningFigures()];
const String bodyFont = 'InstrumentSans';

ThemeData buildNeekahTheme() {
  const scheme = ColorScheme(
    brightness: Brightness.light,
    primary: NColors.wine,
    onPrimary: Colors.white,
    primaryContainer: NColors.wineLight,
    onPrimaryContainer: NColors.wineDark,
    secondary: NColors.gold,
    onSecondary: NColors.ink,
    secondaryContainer: NColors.goldSoft,
    onSecondaryContainer: Color(0xFF6B5317),
    tertiary: NColors.wineDark,
    onTertiary: Colors.white,
    error: Color(0xFFB91C1C),
    onError: Colors.white,
    surface: NColors.surface,
    onSurface: NColors.ink,
    onSurfaceVariant: NColors.inkMuted,
    surfaceContainerLowest: Colors.white,
    surfaceContainerLow: NColors.ivory,
    surfaceContainer: Color(0xFFF6F1EA),
    surfaceContainerHigh: Color(0xFFF2ECE4),
    surfaceContainerHighest: Color(0xFFEDE6DD),
    outline: NColors.line,
    outlineVariant: NColors.lineSoft,
  );

  final base = ThemeData(useMaterial3: true, colorScheme: scheme, fontFamily: bodyFont);
  final text = base.textTheme.apply(bodyColor: NColors.ink, displayColor: NColors.ink);

  TextStyle heading(TextStyle? style, {FontWeight weight = FontWeight.w600}) =>
      (style ?? const TextStyle()).copyWith(fontFamily: headingFont, fontFeatures: liningFigures, fontWeight: weight, letterSpacing: -0.2);

  final textTheme = text.copyWith(
    displayLarge: heading(text.displayLarge),
    displayMedium: heading(text.displayMedium),
    displaySmall: heading(text.displaySmall),
    headlineLarge: heading(text.headlineLarge),
    headlineMedium: heading(text.headlineMedium),
    headlineSmall: heading(text.headlineSmall),
    titleLarge: heading(text.titleLarge),
    titleMedium: text.titleMedium?.copyWith(fontWeight: FontWeight.w600),
    titleSmall: text.titleSmall?.copyWith(fontWeight: FontWeight.w600),
    bodyMedium: text.bodyMedium?.copyWith(height: 1.4),
    bodySmall: text.bodySmall?.copyWith(color: NColors.inkMuted, height: 1.35),
    labelLarge: text.labelLarge?.copyWith(fontWeight: FontWeight.w600, letterSpacing: 0.1),
  );

  return base.copyWith(
    scaffoldBackgroundColor: NColors.ivory,
    textTheme: textTheme,
    splashFactory: InkSparkle.splashFactory,
    appBarTheme: AppBarTheme(
      backgroundColor: NColors.ivory,
      surfaceTintColor: Colors.transparent,
      foregroundColor: NColors.ink,
      elevation: 0,
      scrolledUnderElevation: 0.5,
      centerTitle: false,
      systemOverlayStyle: SystemUiOverlayStyle.dark.copyWith(statusBarColor: Colors.transparent),
      titleTextStyle: textTheme.titleLarge?.copyWith(fontSize: 22, color: NColors.ink),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      indicatorColor: NColors.wineLight,
      elevation: 0,
      height: 68,
      labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      iconTheme: WidgetStateProperty.resolveWith(
        (states) => IconThemeData(color: states.contains(WidgetState.selected) ? NColors.wine : NColors.inkMuted, size: 24),
      ),
      labelTextStyle: WidgetStateProperty.resolveWith(
        (states) => TextStyle(
          fontFamily: bodyFont,
          fontSize: 12,
          fontWeight: states.contains(WidgetState.selected) ? FontWeight.w700 : FontWeight.w500,
          color: states.contains(WidgetState.selected) ? NColors.wine : NColors.inkMuted,
        ),
      ),
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(NRadius.card)),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size(64, 52),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(NRadius.small)),
        textStyle: const TextStyle(fontFamily: bodyFont, fontWeight: FontWeight.w700, fontSize: 15),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size(64, 48),
        foregroundColor: NColors.wine,
        side: const BorderSide(color: NColors.line),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(NRadius.small)),
        textStyle: const TextStyle(fontFamily: bodyFont, fontWeight: FontWeight.w600, fontSize: 14),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: NColors.wine,
        textStyle: const TextStyle(fontFamily: bodyFont, fontWeight: FontWeight.w600, fontSize: 14),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(NRadius.small),
        borderSide: const BorderSide(color: NColors.line),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(NRadius.small),
        borderSide: const BorderSide(color: NColors.line),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(NRadius.small),
        borderSide: const BorderSide(color: NColors.wine, width: 1.6),
      ),
      labelStyle: const TextStyle(color: NColors.inkMuted),
      floatingLabelStyle: const TextStyle(color: NColors.wine, fontWeight: FontWeight.w600),
    ),
    chipTheme: base.chipTheme.copyWith(
      backgroundColor: Colors.white,
      selectedColor: NColors.wine,
      side: const BorderSide(color: NColors.line),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(NRadius.pill)),
      labelStyle: const TextStyle(fontFamily: bodyFont, fontWeight: FontWeight.w600, fontSize: 13, color: NColors.ink),
      secondaryLabelStyle: const TextStyle(fontFamily: bodyFont, fontWeight: FontWeight.w600, fontSize: 13, color: Colors.white),
      checkmarkColor: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
    ),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: NColors.ink,
      contentTextStyle: const TextStyle(fontFamily: bodyFont, color: Colors.white, fontSize: 14),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(NRadius.small)),
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      titleTextStyle: textTheme.titleLarge?.copyWith(fontSize: 21),
    ),
    bottomSheetTheme: const BottomSheetThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      showDragHandle: true,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
    ),
    switchTheme: SwitchThemeData(
      thumbColor: WidgetStateProperty.resolveWith((states) => states.contains(WidgetState.selected) ? Colors.white : NColors.inkMuted),
      trackColor: WidgetStateProperty.resolveWith((states) => states.contains(WidgetState.selected) ? NColors.wine : NColors.lineSoft),
      trackOutlineColor: WidgetStateProperty.resolveWith((states) => states.contains(WidgetState.selected) ? NColors.wine : NColors.line),
    ),
    tabBarTheme: const TabBarThemeData(
      labelColor: NColors.wine,
      unselectedLabelColor: NColors.inkMuted,
      indicatorColor: NColors.wine,
      dividerColor: NColors.line,
      labelStyle: TextStyle(fontFamily: bodyFont, fontWeight: FontWeight.w700, fontSize: 14),
      unselectedLabelStyle: TextStyle(fontFamily: bodyFont, fontWeight: FontWeight.w500, fontSize: 14),
    ),
    dividerTheme: const DividerThemeData(color: NColors.lineSoft, thickness: 1, space: 1),
    progressIndicatorTheme: const ProgressIndicatorThemeData(color: NColors.wine, linearTrackColor: NColors.lineSoft),
    pageTransitionsTheme: const PageTransitionsTheme(builders: {TargetPlatform.android: FadeForwardsPageTransitionsBuilder()}),
  );
}
