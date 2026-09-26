import 'account.dart';
import 'booking.dart';
import 'parse.dart';

/// `{ enabled, open, label }` (+ `can_enable` on the calendar).
class OnlineBooking {
  const OnlineBooking({required this.enabled, required this.open, required this.label, this.canEnable});

  factory OnlineBooking.fromJson(Object? raw) {
    final json = obj(raw);

    return OnlineBooking(
      enabled: toBool(json['enabled']),
      open: toBool(json['open']),
      label: str(json['label']),
      canEnable: json.containsKey('can_enable') ? toBool(json['can_enable']) : null,
    );
  }

  final bool enabled;
  final bool open;
  final String label;
  final bool? canEnable;
}

class DashboardStats {
  const DashboardStats({
    required this.upcomingEvents,
    required this.awaitingDeposit,
    required this.openEnquiries,
    required this.paymentsToVerify,
    required this.paidTotal,
  });

  factory DashboardStats.fromJson(Object? raw) {
    final json = obj(raw);

    return DashboardStats(
      upcomingEvents: toInt(json['upcoming_events']),
      awaitingDeposit: toInt(json['awaiting_deposit']),
      openEnquiries: toInt(json['open_enquiries']),
      paymentsToVerify: toInt(json['payments_to_verify']),
      paidTotal: toDouble(json['paid_total']),
    );
  }

  final int upcomingEvents;
  final int awaitingDeposit;
  final int openEnquiries;
  final int paymentsToVerify;
  final double paidTotal;
}

class Reach {
  const Reach({required this.days, required this.profileViews, required this.whatsappClicks, required this.phoneClicks});

  factory Reach.fromJson(Object? raw) {
    final json = obj(raw);

    return Reach(
      days: toInt(json['days'], 28),
      profileViews: toInt(json['profile_views']),
      whatsappClicks: toInt(json['whatsapp_clicks']),
      phoneClicks: toInt(json['phone_clicks']),
    );
  }

  final int days;
  final int profileViews;
  final int whatsappClicks;
  final int phoneClicks;
}

class DailyViews {
  const DailyViews({required this.label, required this.views, this.date});

  factory DailyViews.fromJson(Json json) => DailyViews(date: dateOrNull(json['date']), label: str(json['label']), views: toInt(json['views']));

  final DateTime? date;

  /// Short localised day, e.g. "12 Okt".
  final String label;
  final int views;
}

class Dashboard {
  const Dashboard({
    required this.vendor,
    required this.stats,
    required this.reach,
    required this.dailyViews,
    required this.upcoming,
    required this.online,
    this.elite,
  });

  factory Dashboard.fromJson(Json json) => Dashboard(
    vendor: Vendor.fromJson(json['vendor']),
    stats: DashboardStats.fromJson(json['stats']),
    reach: Reach.fromJson(json['reach']),
    dailyViews: listOf(json['daily_views'], DailyViews.fromJson),
    upcoming: listOf(json['upcoming'], BookingSummary.fromJson),
    online: OnlineBooking.fromJson(json['online']),
    elite: strOrNull(json['elite']),
  );

  final Vendor vendor;
  final DashboardStats stats;
  final Reach reach;
  final List<DailyViews> dailyViews;
  final List<BookingSummary> upcoming;
  final OnlineBooking online;

  /// `elite`, `need_tier` or null.
  final String? elite;
}
