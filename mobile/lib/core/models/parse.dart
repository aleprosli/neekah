/// Lenient readers for JSON values: the API sends numbers, strings and
/// nulls, and a model should never crash on a stray `"12"` or `12.0`.
library;

typedef Json = Map<String, dynamic>;

String str(Object? value, [String fallback = '']) => value == null ? fallback : value.toString();

String? strOrNull(Object? value) {
  if (value == null) {
    return null;
  }
  final text = value.toString();

  return text.isEmpty ? null : text;
}

int toInt(Object? value, [int fallback = 0]) {
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.round();
  }
  if (value is String) {
    return int.tryParse(value) ?? double.tryParse(value)?.round() ?? fallback;
  }

  return fallback;
}

int? toIntOrNull(Object? value) => value == null ? null : toInt(value);

double toDouble(Object? value, [double fallback = 0]) {
  if (value is num) {
    return value.toDouble();
  }
  if (value is String) {
    return double.tryParse(value) ?? fallback;
  }

  return fallback;
}

double? toDoubleOrNull(Object? value) => value == null ? null : toDouble(value);

bool toBool(Object? value) {
  if (value is bool) {
    return value;
  }
  if (value is num) {
    return value != 0;
  }
  if (value is String) {
    return value == '1' || value.toLowerCase() == 'true';
  }

  return false;
}

Json obj(Object? value) => value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

Json? objOrNull(Object? value) => value is Map ? Map<String, dynamic>.from(value) : null;

List<T> listOf<T>(Object? value, T Function(Json json) parse) => value is List
    ? [
        for (final item in value)
          if (item is Map) parse(Map<String, dynamic>.from(item)),
      ]
    : <T>[];

/// `"2026-10-12"` → local midnight of that calendar day.
DateTime? dateOrNull(Object? value) {
  final text = strOrNull(value);
  if (text == null) {
    return null;
  }
  final parsed = DateTime.tryParse(text.length >= 10 ? text.substring(0, 10) : text);

  return parsed == null ? null : DateTime(parsed.year, parsed.month, parsed.day);
}

/// An ISO 8601 date-time with offset → the exact instant.
DateTime? dateTimeOrNull(Object? value) {
  final text = strOrNull(value);

  return text == null ? null : DateTime.tryParse(text);
}
