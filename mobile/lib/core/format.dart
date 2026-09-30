import 'package:intl/intl.dart';

/// Money, dates and times the Malaysian way. Everything the API sends is
/// Asia/Kuala_Lumpur time, so instants are shown on the KL wall clock
/// whatever the phone's own zone is.
class Fmt {
  const Fmt(this.languageCode);

  final String languageCode;

  static final NumberFormat _money = NumberFormat('#,##0.00', 'en_US');
  static final NumberFormat _whole = NumberFormat('#,##0', 'en_US');
  static final NumberFormat _compact = NumberFormat.compact(locale: 'en_US');

  /// `1050.0` → `RM1,050.00`.
  static String money(double amount) => '${amount < 0 ? '-' : ''}RM${_money.format(amount.abs())}';

  /// `12500` → `RM12.5K`, for tight stat cards.
  static String moneyShort(double amount) => amount.abs() >= 100000 ? 'RM${_compact.format(amount)}' : money(amount);

  static String number(num value) => _whole.format(value);

  static DateTime klWallClock(DateTime instant) => instant.toUtc().add(const Duration(hours: 8));

  static DateTime nowKl() => klWallClock(DateTime.now());

  /// 12 Okt 2026
  String date(DateTime? value) => value == null ? '—' : DateFormat('d MMM y', languageCode).format(value);

  /// Sab, 12 Okt
  String weekdayDate(DateTime value) => DateFormat('EEE, d MMM', languageCode).format(value);

  /// Sabtu, 12 Oktober 2026
  String longDate(DateTime value) => DateFormat('EEEE, d MMMM y', languageCode).format(value);

  /// Oktober 2026
  String monthYear(DateTime value) => DateFormat('MMMM y', languageCode).format(value);

  String dayNumber(DateTime value) => DateFormat('d', languageCode).format(value);

  String monthShort(DateTime value) => DateFormat('MMM', languageCode).format(value).toUpperCase();

  /// 12 Okt 2026, 2:30 PTG (KL time)
  String dateTime(DateTime? instant) {
    if (instant == null) {
      return '—';
    }

    return DateFormat('d MMM y, h:mm a', languageCode).format(klWallClock(instant));
  }

  String time(DateTime instant) => DateFormat('h:mm a', languageCode).format(klWallClock(instant));
}
