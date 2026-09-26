import '../config.dart';
import '../models/account.dart';
import '../models/booking.dart';
import '../models/boost.dart';
import '../models/calendar.dart';
import '../models/common.dart';
import '../models/dashboard.dart';
import '../models/enquiry.dart';
import '../models/notification.dart';
import '../models/parse.dart';
import '../models/points.dart';
import 'api_client.dart';

/// Typed endpoints of the Neekah Pro vendor API (v1).
class NeekahApi {
  NeekahApi(this.client);

  final ApiClient client;

  // Auth ---------------------------------------------------------------------

  Future<AuthConfig> authConfig() async => AuthConfig.fromJson(await client.get('/auth/config'));

  Future<LoginResult> login({required String email, required String password, String? accessCode}) async {
    final json = await client.post('/auth/login', {
      'email': email,
      'password': password,
      'device_name': AppConfig.deviceName,
      if (accessCode != null && accessCode.isNotEmpty) 'access_code': accessCode,
    });

    return LoginResult.fromJson(json);
  }

  Future<void> logout() => client.post('/auth/logout');

  Future<Me> me() async => Me.fromJson(await client.get('/me'));

  // Dashboard ----------------------------------------------------------------

  Future<Dashboard> dashboard() async => Dashboard.fromJson(await client.get('/dashboard'));

  // Bookings -----------------------------------------------------------------

  Future<Paginated<BookingSummary>> bookings({List<String> statuses = const [], String? search, int page = 1}) async {
    final json = await client.get('/bookings', query: {'status': statuses.isEmpty ? null : statuses.join(','), 'search': search, 'page': '$page'});

    return Paginated.fromJson(json, BookingSummary.fromJson);
  }

  Future<BookingDetail> booking(String reference) async =>
      BookingDetail.fromJson(obj((await client.get('/bookings/${Uri.encodeComponent(reference)}'))['data']));

  Future<ActionResult<BookingDetail>> completeBooking(String reference) => _bookingAction('/bookings/${Uri.encodeComponent(reference)}/complete');

  Future<ActionResult<BookingDetail>> cancelBooking(String reference, String reason) =>
      _bookingAction('/bookings/${Uri.encodeComponent(reference)}/cancel', {'reason': reason});

  Future<ActionResult<BookingDetail>> verifyPayment(String reference, String payment) => _paymentAction(reference, payment, 'verify');

  Future<ActionResult<BookingDetail>> rejectPayment(String reference, String payment) => _paymentAction(reference, payment, 'reject');

  Future<ActionResult<BookingDetail>> markPaymentRefunded(String reference, String payment) => _paymentAction(reference, payment, 'refunded');

  Future<ActionResult<BookingDetail>> _paymentAction(String reference, String payment, String action) =>
      _bookingAction('/bookings/${Uri.encodeComponent(reference)}/payments/${Uri.encodeComponent(payment)}/$action');

  Future<ActionResult<BookingDetail>> _bookingAction(String path, [Map<String, dynamic>? body]) async {
    final json = await client.post(path, body);

    return ActionResult(str(json['message']), BookingDetail.fromJson(obj(json['data'])));
  }

  // Calendar -----------------------------------------------------------------

  Future<CalendarMonth> calendar(DateTime month) async {
    final key = '${month.year}-${month.month.toString().padLeft(2, '0')}';

    return CalendarMonth.fromJson(await client.get('/calendar', query: {'month': key}));
  }

  Future<ActionResult<int>> addClosedDates({required DateTime from, DateTime? to, String? reason, int? slots}) async {
    final json = await client.post('/calendar/closed', {
      'from': _day(from),
      'to': ?_nullableDay(to),
      if (reason != null && reason.isNotEmpty) 'reason': reason,
      'slots': ?slots,
    });

    return ActionResult(str(json['message']), toInt(json['added']));
  }

  Future<String> reopenDate(String id) async => str((await client.delete('/calendar/closed/${Uri.encodeComponent(id)}'))['message']);

  Future<ActionResult<OnlineBooking>> setOnlineBooking(bool enabled) async {
    final json = await client.put('/online-booking', {'enabled': enabled});

    return ActionResult(str(json['message']), OnlineBooking.fromJson(json['online']));
  }

  // Enquiries ----------------------------------------------------------------

  Future<Paginated<EnquirySummary>> enquiries({required String status, int page = 1}) async =>
      Paginated.fromJson(await client.get('/enquiries', query: {'status': status, 'page': '$page'}), EnquirySummary.fromJson);

  Future<EnquiryDetail> enquiry(String id) async => EnquiryDetail.fromJson(obj((await client.get('/enquiries/${Uri.encodeComponent(id)}'))['data']));

  Future<ActionResult<EnquiryDetail>> replyToEnquiry(String id, String reply) async {
    final json = await client.post('/enquiries/${Uri.encodeComponent(id)}/reply', {'reply': reply});

    return ActionResult(str(json['message']), EnquiryDetail.fromJson(obj(json['data'])));
  }

  // Boost --------------------------------------------------------------------

  Future<BoostData> boost() async => BoostData.fromJson(await client.get('/boost'));

  Future<ActionResult<int>> startBoost({required int categoryId, required int days}) async {
    final json = await client.post('/boost', {'category_id': categoryId, 'days': days});

    return ActionResult(str(json['message']), toInt(json['balance']));
  }

  // Points -------------------------------------------------------------------

  Future<PointsData> points() async => PointsData.fromJson(await client.get('/points'));

  // Notifications ------------------------------------------------------------

  Future<NotificationPage> notifications({int page = 1}) async =>
      NotificationPage.fromJson(await client.get('/notifications', query: {'page': '$page'}));

  /// Marks [ids] read, or everything when null. Returns the unread count left.
  Future<int> markNotificationsRead([List<Object>? ids]) async => toInt((await client.post('/notifications/read', {'ids': ?ids}))['unread']);

  static String? _nullableDay(DateTime? date) => date == null ? null : _day(date);

  static String _day(DateTime date) => '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
}
