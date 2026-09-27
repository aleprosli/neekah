import 'package:flutter/foundation.dart';

import 'api/api_exceptions.dart';
import 'api/neekah_api.dart';

/// The unread notification count behind the bell and the Lagi tab badge.
class NotificationBadge extends ChangeNotifier {
  NotificationBadge(this.api);

  final NeekahApi api;
  int _unread = 0;

  int get unread => _unread;

  set unread(int value) {
    if (value != _unread) {
      _unread = value < 0 ? 0 : value;
      notifyListeners();
    }
  }

  Future<void> refresh() async {
    try {
      unread = (await api.notifications()).unread;
    } on ApiException {
      // The badge is a nicety; keep the last count.
    }
  }
}
