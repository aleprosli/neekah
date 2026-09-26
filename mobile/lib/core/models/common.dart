import 'parse.dart';

/// `{ value, label, tone }` — tone is one of emerald, amber, sky, brand, red, muted.
class Status {
  const Status({required this.value, required this.label, required this.tone});

  factory Status.fromJson(Object? raw) {
    final json = obj(raw);

    return Status(value: str(json['value']), label: str(json['label'], str(json['value'])), tone: str(json['tone'], 'muted'));
  }

  final String value;
  final String label;
  final String tone;
}

/// `{ value, label }` — a ranking tier.
class Tier {
  const Tier({required this.value, required this.label});

  factory Tier.fromJson(Object? raw) {
    final json = obj(raw);

    return Tier(value: str(json['value']), label: str(json['label'], str(json['value'])));
  }

  static Tier? maybe(Object? raw) => raw is Map ? Tier.fromJson(raw) : null;

  final String value;
  final String label;
}

class PageMeta {
  const PageMeta({required this.currentPage, required this.lastPage, required this.perPage, required this.total});

  factory PageMeta.fromJson(Object? raw) {
    final json = obj(raw);

    return PageMeta(
      currentPage: toInt(json['current_page'], 1),
      lastPage: toInt(json['last_page'], 1),
      perPage: toInt(json['per_page'], 20),
      total: toInt(json['total']),
    );
  }

  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  bool get hasMore => currentPage < lastPage;
}

/// A page of `data` plus `meta`, and optionally the `counts` a list returns.
class Paginated<T> {
  const Paginated({required this.items, required this.meta, this.counts = const {}});

  factory Paginated.fromJson(Json json, T Function(Json json) parse) => Paginated(
    items: listOf(json['data'], parse),
    meta: PageMeta.fromJson(json['meta']),
    counts: {for (final entry in obj(json['counts']).entries) entry.key: toInt(entry.value)},
  );

  final List<T> items;
  final PageMeta meta;
  final Map<String, int> counts;
}

/// The `{ message, data }` a changing call returns.
class ActionResult<T> {
  const ActionResult(this.message, this.data);

  final String message;
  final T data;
}
