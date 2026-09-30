import 'package:flutter/widgets.dart';

import '../format.dart';
import 'strings.dart';

/// Looks up UI strings from [appStrings] for the active language.
class L10n {
  const L10n(this.code);

  static L10n of(BuildContext context) => L10n(Localizations.maybeLocaleOf(context)?.languageCode ?? 'ms');

  final String code;

  /// `t('bookings.count', {'n': 3})` fills `{n}` placeholders.
  String t(String key, [Map<String, Object?> args = const {}]) {
    var text = appStrings[code]?[key] ?? appStrings['ms']?[key] ?? key;
    args.forEach((name, value) => text = text.replaceAll('{$name}', '${value ?? ''}'));

    return text;
  }
}

extension L10nContext on BuildContext {
  String t(String key, [Map<String, Object?> args = const {}]) => L10n.of(this).t(key, args);

  Fmt get fmt => Fmt(L10n.of(this).code);
}

extension RelativeTime on BuildContext {
  /// "5 minit lalu", "3 jam lalu", "2 hari lalu", then a plain date.
  String ago(DateTime? instant) {
    if (instant == null) {
      return '';
    }
    final elapsed = DateTime.now().difference(instant);
    if (elapsed.inMinutes < 1) {
      return t('time.just_now');
    }
    if (elapsed.inMinutes < 60) {
      return t('time.minutes', {'n': elapsed.inMinutes});
    }
    if (elapsed.inHours < 24) {
      return t('time.hours', {'n': elapsed.inHours});
    }
    if (elapsed.inDays < 7) {
      return t('time.days', {'n': elapsed.inDays});
    }

    return fmt.date(Fmt.klWallClock(instant));
  }
}
