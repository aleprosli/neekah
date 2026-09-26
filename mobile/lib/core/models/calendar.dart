import 'common.dart';
import 'dashboard.dart';
import 'parse.dart';

class BookedDay {
  const BookedDay({required this.date, required this.reference, required this.customerName, required this.packageName, required this.status});

  factory BookedDay.fromJson(Json json) => BookedDay(
    date: dateOrNull(json['date']) ?? DateTime(1970),
    reference: str(json['reference']),
    customerName: str(json['customer_name']),
    packageName: str(json['package_name']),
    status: Status.fromJson(json['status']),
  );

  final DateTime date;
  final String reference;
  final String customerName;
  final String packageName;
  final Status status;
}

class ClosedDay {
  const ClosedDay({required this.id, required this.date, required this.imported, required this.canReopen, this.reason, this.slots});

  factory ClosedDay.fromJson(Json json) => ClosedDay(
    id: str(json['id']),
    date: dateOrNull(json['date']) ?? DateTime(1970),
    reason: strOrNull(json['reason']),
    slots: toIntOrNull(json['slots']),
    imported: toBool(json['imported']),
    canReopen: toBool(json['can_reopen']),
  );

  final String id;
  final DateTime date;
  final String? reason;

  /// Places closed that day; null means the whole day.
  final int? slots;
  final bool imported;
  final bool canReopen;
}

class CalendarMonth {
  const CalendarMonth({required this.month, required this.capacity, required this.booked, required this.closed, required this.online, this.today});

  factory CalendarMonth.fromJson(Json json) => CalendarMonth(
    month: str(json['month']),
    today: dateOrNull(json['today']),
    capacity: toInt(json['capacity'], 1),
    booked: listOf(json['booked'], BookedDay.fromJson),
    closed: listOf(json['closed'], ClosedDay.fromJson),
    online: OnlineBooking.fromJson(json['online']),
  );

  final String month;
  final DateTime? today;
  final int capacity;
  final List<BookedDay> booked;
  final List<ClosedDay> closed;
  final OnlineBooking online;
}
