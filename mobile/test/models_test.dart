import 'package:flutter_test/flutter_test.dart';
import 'package:neekah_pro/core/models/booking.dart';
import 'package:neekah_pro/core/models/common.dart';
import 'package:neekah_pro/core/models/dashboard.dart';
import 'package:neekah_pro/core/models/notification.dart';
import 'package:neekah_pro/core/models/points.dart';

import 'support/fixtures.dart';

void main() {
  test('dashboard parses stats, 28 days of views and the online state', () {
    final dashboard = Dashboard.fromJson(dashboardJson());

    expect(dashboard.vendor.name, 'Studio Seri Kasih');
    expect(dashboard.vendor.proUntil, DateTime.utc(2026, 11, 26, 18, 12, 37));
    expect(dashboard.stats.paidTotal, 7050.0);
    expect(dashboard.reach.days, 28);
    expect(dashboard.dailyViews, hasLength(28));
    expect(dashboard.dailyViews.first.label, '1 Sep');
    expect(dashboard.upcoming.single.eventDate, DateTime(2026, 10, 3));
    expect(dashboard.online.enabled, isTrue);
    expect(dashboard.online.canEnable, isNull);
    expect(dashboard.elite, isNull);
  });

  test('booking detail tolerates numeric strings and reads payment permissions', () {
    final booking = BookingDetail.fromJson(bookingDetailJson());

    expect(booking.summary.status.value, 'pending_payment');
    expect(booking.depositAmount, 1000.0);
    expect(booking.outstanding, 2700.0);
    expect(booking.summary.paidRatio, closeTo(1500 / 4200, 0.0001));
    expect(booking.customer.whatsappUrl, 'https://wa.me/60126354002');
    final payment = booking.payments.single;
    expect(payment.canVerify, isTrue);
    expect(payment.canMarkRefunded, isFalse);
    expect(payment.paidOn, DateTime(2026, 9, 27));
    expect(payment.paidAt, isNull);
    expect(booking.timeline.single.id, '1');
    expect(booking.review?.rating, 5);
    expect(booking.canCancel, isTrue);
    expect(booking.canComplete, isFalse);
  });

  test('paginated lists keep counts even when a status filter is applied', () {
    final page = Paginated.fromJson({
      'data': [bookingSummaryJson()],
      'meta': {'current_page': 1, 'last_page': 3, 'per_page': 20, 'total': 47},
      'links': {'first': 'ignored'},
      'counts': {'pending_payment': 2, 'confirmed': '5'},
    }, BookingSummary.fromJson);

    expect(page.items, hasLength(1));
    expect(page.meta.hasMore, isTrue);
    expect(page.counts, {'pending_payment': 2, 'confirmed': 5});
  });

  test('points read tier requirements sent as strings', () {
    final points = PointsData.fromJson({
      'points_total': 150,
      'penalty_points': 0,
      'score': 13.65,
      'completion_rate': 50,
      'response_rate_label': 'Belum diukur',
      'completed_bookings': 1,
      'tier': {'value': 'verified', 'label': 'Verified'},
      'next_tier': null,
      'tier_locked': false,
      'requirements': [
        {'label': 'Booking selesai', 'current': '1', 'target': '5', 'met': false},
      ],
      'breakdown': [],
      'recent': [],
    });

    expect(points.nextTier, isNull);
    expect(points.completionRate, 50.0);
    expect(points.requirements.single.progress, closeTo(0.2, 0.0001));
  });

  test('notifications keep their id unchanged and their target', () {
    final page = NotificationPage.fromJson({
      'data': [
        {
          'id': 'dda188c5-64cb-403b-aaed-34b1347a16e6',
          'icon': '✅',
          'title': 'Booking NK-4MAPYV disahkan',
          'body': null,
          'created_at': '2026-09-27T02:52:37+08:00',
          'read': false,
          'target': {'type': 'booking', 'id': 'NK-4MAPYV'},
        },
      ],
      'meta': {'current_page': 1, 'last_page': 1, 'per_page': 20, 'total': 1},
      'unread': 1,
    });

    final notification = page.page.items.single;
    expect(notification.rawId, 'dda188c5-64cb-403b-aaed-34b1347a16e6');
    expect(notification.target.type, 'booking');
    expect(notification.target.id, 'NK-4MAPYV');
    expect(notification.markedRead().read, isTrue);
    expect(page.unread, 1);
  });
}
