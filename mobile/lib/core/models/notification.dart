import 'common.dart';
import 'parse.dart';

class NotificationTarget {
  const NotificationTarget({this.type, this.id});

  final String? type;
  final String? id;
}

class AppNotification {
  const AppNotification({
    required this.rawId,
    required this.icon,
    required this.title,
    required this.read,
    required this.target,
    this.body,
    this.createdAt,
  });

  factory AppNotification.fromJson(Json json) {
    final target = obj(json['target']);

    return AppNotification(
      rawId: (json['id'] as Object?) ?? '',
      icon: str(json['icon']),
      title: str(json['title']),
      body: strOrNull(json['body']),
      createdAt: dateTimeOrNull(json['created_at']),
      read: toBool(json['read']),
      target: NotificationTarget(type: strOrNull(target['type']), id: strOrNull(target['id'])),
    );
  }

  /// Kept as sent (a UUID string or a number) so it goes back unchanged.
  final Object rawId;
  final String icon;
  final String title;
  final String? body;
  final DateTime? createdAt;
  final bool read;
  final NotificationTarget target;

  AppNotification markedRead() =>
      AppNotification(rawId: rawId, icon: icon, title: title, body: body, createdAt: createdAt, read: true, target: target);
}

class NotificationPage {
  const NotificationPage({required this.page, required this.unread});

  factory NotificationPage.fromJson(Json json) =>
      NotificationPage(page: Paginated.fromJson(json, AppNotification.fromJson), unread: toInt(json['unread']));

  final Paginated<AppNotification> page;
  final int unread;
}
