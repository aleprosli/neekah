import 'parse.dart';

class BoostCategory {
  const BoostCategory({required this.id, required this.name, this.boostedUntil});

  factory BoostCategory.fromJson(Json json) =>
      BoostCategory(id: toInt(json['id']), name: str(json['name']), boostedUntil: dateTimeOrNull(json['boosted_until']));

  final int id;
  final String name;
  final DateTime? boostedUntil;
}

class RunningBoost {
  const RunningBoost({required this.id, required this.category, this.endsAt});

  factory RunningBoost.fromJson(Json json) =>
      RunningBoost(id: str(json['id']), category: str(json['category']), endsAt: dateTimeOrNull(json['ends_at']));

  final String id;
  final String category;
  final DateTime? endsAt;
}

class TokenChange {
  const TokenChange({required this.id, required this.change, required this.reason, this.note, this.date});

  factory TokenChange.fromJson(Json json) => TokenChange(
    id: str(json['id']),
    change: toInt(json['change']),
    reason: str(json['reason']),
    note: strOrNull(json['note']),
    date: dateTimeOrNull(json['date']) ?? dateOrNull(json['date']),
  );

  final String id;
  final int change;
  final String reason;
  final String? note;
  final DateTime? date;
}

class BoostData {
  const BoostData({
    required this.balance,
    required this.maxDays,
    required this.proMonthlyTokens,
    required this.categories,
    required this.running,
    required this.history,
    this.buyUrl,
  });

  factory BoostData.fromJson(Json json) => BoostData(
    balance: toInt(json['balance']),
    maxDays: toInt(json['max_days'], 7),
    proMonthlyTokens: toInt(json['pro_monthly_tokens']),
    categories: listOf(json['categories'], BoostCategory.fromJson),
    running: listOf(json['running'], RunningBoost.fromJson),
    history: listOf(json['history'], TokenChange.fromJson),
    buyUrl: strOrNull(json['buy_url']),
  );

  final int balance;
  final int maxDays;
  final int proMonthlyTokens;
  final List<BoostCategory> categories;
  final List<RunningBoost> running;
  final List<TokenChange> history;

  /// Web page to buy more token packs.
  final String? buyUrl;
}
