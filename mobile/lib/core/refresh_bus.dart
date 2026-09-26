import 'package:flutter/foundation.dart';

enum Topic { dashboard, bookings, calendar, enquiries, boost, notifications }

/// Tells screens that data they show has changed elsewhere, so the list
/// behind a detail screen reloads after an action.
class RefreshBus extends ChangeNotifier {
  final Map<Topic, int> _versions = {for (final topic in Topic.values) topic: 0};

  int version(Topic topic) => _versions[topic]!;

  void touch(Set<Topic> topics) {
    for (final topic in topics) {
      _versions[topic] = _versions[topic]! + 1;
    }
    notifyListeners();
  }
}
