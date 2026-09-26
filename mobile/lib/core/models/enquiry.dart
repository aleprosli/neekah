import 'booking.dart';
import 'common.dart';
import 'parse.dart';

class EnquirySummary {
  const EnquirySummary({
    required this.id,
    required this.customerName,
    required this.preview,
    required this.status,
    this.eventDate,
    this.packageName,
    this.createdAt,
    this.repliedAt,
  });

  factory EnquirySummary.fromJson(Json json) => EnquirySummary(
    id: str(json['id']),
    customerName: str(json['customer_name']),
    preview: str(json['preview']),
    eventDate: dateOrNull(json['event_date']),
    packageName: strOrNull(json['package_name']),
    status: Status.fromJson(json['status']),
    createdAt: dateTimeOrNull(json['created_at']),
    repliedAt: dateTimeOrNull(json['replied_at']),
  );

  final String id;
  final String customerName;
  final String preview;
  final DateTime? eventDate;
  final String? packageName;
  final Status status;
  final DateTime? createdAt;
  final DateTime? repliedAt;
}

class WeddingInfo {
  const WeddingInfo({required this.title, this.city, this.state, this.budget});

  final String title;
  final String? city;
  final String? state;
  final double? budget;
}

class EnquiryDetail {
  const EnquiryDetail({required this.summary, required this.message, required this.customer, this.reply, this.wedding});

  factory EnquiryDetail.fromJson(Json json) {
    final wedding = objOrNull(json['wedding']);

    return EnquiryDetail(
      summary: EnquirySummary.fromJson(json),
      message: str(json['message']),
      reply: strOrNull(json['reply']),
      customer: Contact.fromJson(json['customer']),
      wedding: wedding == null
          ? null
          : WeddingInfo(
              title: str(wedding['title']),
              city: strOrNull(wedding['city']),
              state: strOrNull(wedding['state']),
              budget: toDoubleOrNull(wedding['budget']),
            ),
    );
  }

  final EnquirySummary summary;
  final String message;
  final String? reply;
  final Contact customer;
  final WeddingInfo? wedding;
}
