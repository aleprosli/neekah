import 'common.dart';
import 'parse.dart';

class BookingSummary {
  const BookingSummary({
    required this.reference,
    required this.customerName,
    required this.packageName,
    required this.total,
    required this.paid,
    required this.status,
    required this.isOnline,
    this.eventDate,
  });

  factory BookingSummary.fromJson(Json json) => BookingSummary(
    reference: str(json['reference']),
    eventDate: dateOrNull(json['event_date']),
    customerName: str(json['customer_name']),
    packageName: str(json['package_name']),
    total: toDouble(json['total']),
    paid: toDouble(json['paid']),
    status: Status.fromJson(json['status']),
    isOnline: toBool(json['is_online']),
  );

  final String reference;
  final DateTime? eventDate;
  final String customerName;
  final String packageName;
  final double total;
  final double paid;
  final Status status;
  final bool isOnline;

  double get paidRatio => total <= 0 ? 0 : (paid / total).clamp(0, 1).toDouble();
}

class Contact {
  const Contact({required this.name, required this.email, this.phone, this.whatsappUrl});

  factory Contact.fromJson(Object? raw) {
    final json = obj(raw);

    return Contact(name: str(json['name']), email: str(json['email']), phone: strOrNull(json['phone']), whatsappUrl: strOrNull(json['whatsapp_url']));
  }

  final String name;
  final String email;
  final String? phone;
  final String? whatsappUrl;
}

class Payment {
  const Payment({
    required this.reference,
    required this.amount,
    required this.status,
    required this.methodLabel,
    required this.canVerify,
    required this.canReject,
    required this.canMarkRefunded,
    this.paidOn,
    this.paidAt,
    this.note,
    this.recordedBy,
    this.slipUrl,
    this.receiptUrl,
  });

  factory Payment.fromJson(Json json) {
    final can = obj(json['can']);

    return Payment(
      reference: str(json['reference']),
      amount: toDouble(json['amount']),
      status: Status.fromJson(json['status']),
      paidOn: dateOrNull(json['paid_on']),
      paidAt: dateTimeOrNull(json['paid_at']),
      methodLabel: str(json['method_label']),
      note: strOrNull(json['note']),
      recordedBy: strOrNull(json['recorded_by']),
      slipUrl: strOrNull(json['slip_url']),
      receiptUrl: strOrNull(json['receipt_url']),
      canVerify: toBool(can['verify']),
      canReject: toBool(can['reject']),
      canMarkRefunded: toBool(can['mark_refunded']),
    );
  }

  final String reference;
  final double amount;
  final Status status;
  final DateTime? paidOn;
  final DateTime? paidAt;
  final String methodLabel;
  final String? note;
  final String? recordedBy;
  final String? slipUrl;
  final String? receiptUrl;
  final bool canVerify;
  final bool canReject;
  final bool canMarkRefunded;

  bool get hasActions => canVerify || canReject || canMarkRefunded;
}

class TimelineItem {
  const TimelineItem({required this.id, required this.title, this.time, this.location, this.notes});

  factory TimelineItem.fromJson(Json json) => TimelineItem(
    id: str(json['id']),
    time: strOrNull(json['time']),
    title: str(json['title']),
    location: strOrNull(json['location']),
    notes: strOrNull(json['notes']),
  );

  final String id;
  final String? time;
  final String title;
  final String? location;
  final String? notes;
}

class Review {
  const Review({required this.rating, this.comment});

  final int rating;
  final String? comment;
}

class BookingDetail {
  const BookingDetail({
    required this.summary,
    required this.outstanding,
    required this.customer,
    required this.payments,
    required this.timeline,
    required this.canComplete,
    required this.canCancel,
    this.createdAt,
    this.notes,
    this.depositAmount,
    this.holdExpiresAt,
    this.review,
  });

  factory BookingDetail.fromJson(Json json) {
    final can = obj(json['can']);
    final review = objOrNull(json['review']);

    return BookingDetail(
      summary: BookingSummary.fromJson(json),
      createdAt: dateTimeOrNull(json['created_at']),
      notes: strOrNull(json['notes']),
      depositAmount: toDoubleOrNull(json['deposit_amount']),
      outstanding: toDouble(json['outstanding']),
      holdExpiresAt: dateTimeOrNull(json['hold_expires_at']),
      customer: Contact.fromJson(json['customer']),
      payments: listOf(json['payments'], Payment.fromJson),
      timeline: listOf(json['timeline'], TimelineItem.fromJson),
      review: review == null ? null : Review(rating: toInt(review['rating']), comment: strOrNull(review['comment'])),
      canComplete: toBool(can['complete']),
      canCancel: toBool(can['cancel']),
    );
  }

  final BookingSummary summary;
  final DateTime? createdAt;
  final String? notes;
  final double? depositAmount;
  final double outstanding;
  final DateTime? holdExpiresAt;
  final Contact customer;
  final List<Payment> payments;
  final List<TimelineItem> timeline;
  final Review? review;
  final bool canComplete;
  final bool canCancel;
}

/// The booking statuses the list can filter by, in display order.
const bookingStatuses = ['pending_payment', 'confirmed', 'completed', 'cancelled'];
