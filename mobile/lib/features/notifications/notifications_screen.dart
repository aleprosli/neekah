import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/api/neekah_api.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/notification.dart';
import '../../core/notification_badge.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';
import '../../ui/widgets/paged_list.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late final PagedController<AppNotification> _controller = PagedController((page) async {
    final result = await context.read<NeekahApi>().notifications(page: page);
    if (mounted) {
      context.read<NotificationBadge>().unread = result.unread;
    }

    return result.page;
  });

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _markAll() async {
    final api = context.read<NeekahApi>();
    final badge = context.read<NotificationBadge>();
    try {
      badge.unread = await api.markNotificationsRead();
      _controller.replaceWhere((item) => !item.read, (item) => item.markedRead());
      if (mounted) {
        showMessage(context, context.t('notifications.all_marked'));
      }
    } on ApiException catch (error) {
      if (mounted) {
        showError(context, error);
      }
    }
  }

  Future<void> _open(AppNotification notification) async {
    final api = context.read<NeekahApi>();
    final badge = context.read<NotificationBadge>();
    if (!notification.read) {
      _controller.replaceWhere((item) => item.rawId == notification.rawId, (item) => item.markedRead());
      badge.unread = badge.unread - 1;
      api.markNotificationsRead([notification.rawId]).then((unread) => badge.unread = unread, onError: (_) {});
    }
    final target = notification.target;
    final id = target.id;
    switch (target.type) {
      case 'booking' when id != null:
        context.push('/booking/${Uri.encodeComponent(id)}');
      case 'enquiry' when id != null:
        context.push('/enquiry/${Uri.encodeComponent(id)}');
      case 'boost':
        context.push('/boost');
      case 'pro':
        context.push('/settings');
      case 'payment':
        context.go('/bookings');
      default:
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    final unread = context.watch<NotificationBadge>().unread;

    return Scaffold(
      appBar: AppBar(
        title: Text(context.t('notifications.title')),
        actions: [
          if (unread > 0)
            TextButton.icon(
              onPressed: _markAll,
              icon: const Icon(Icons.done_all_rounded, size: 18),
              label: Text(context.t('notifications.mark_all')),
            ),
          const SizedBox(width: 8),
        ],
      ),
      body: PagedListView<AppNotification>(
        controller: _controller,
        separator: 10,
        itemBuilder: (context, notification) => _NotificationTile(notification: notification, onTap: () => _open(notification)),
        empty: EmptyState(
          icon: Icons.notifications_none_rounded,
          title: context.t('notifications.empty_title'),
          message: context.t('notifications.empty_body'),
        ),
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({required this.notification, required this.onTap});

  final AppNotification notification;
  final VoidCallback onTap;

  bool get _isEmoji => notification.icon.isNotEmpty && notification.icon.runes.first > 0x2000;

  @override
  Widget build(BuildContext context) {
    final unread = !notification.read;

    return NCard(
      padding: const EdgeInsets.all(14),
      color: unread ? Colors.white : const Color(0xFFFDFCFA),
      border: unread ? Border.all(color: NColors.wine.withValues(alpha: 0.18)) : null,
      onTap: onTap,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(color: unread ? NColors.wineLight : NColors.lineSoft, borderRadius: BorderRadius.circular(14)),
            alignment: Alignment.center,
            child: _isEmoji
                ? Text(notification.icon, style: const TextStyle(fontSize: 20))
                : Icon(Icons.notifications_rounded, color: unread ? NColors.wine : NColors.inkMuted, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        notification.title,
                        style: TextStyle(
                          fontWeight: unread ? FontWeight.w800 : FontWeight.w600,
                          fontSize: 14.5,
                          color: unread ? NColors.ink : NColors.inkMuted,
                        ),
                      ),
                    ),
                    if (unread)
                      Container(
                        width: 9,
                        height: 9,
                        margin: const EdgeInsets.only(left: 8, top: 5),
                        decoration: const BoxDecoration(shape: BoxShape.circle, color: NColors.wine),
                      ),
                  ],
                ),
                if (notification.body != null) ...[
                  const SizedBox(height: 3),
                  Text(notification.body!, style: const TextStyle(color: NColors.inkMuted, fontSize: 13.5, height: 1.35)),
                ],
                const SizedBox(height: 6),
                Text(context.ago(notification.createdAt), style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 11.5)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
