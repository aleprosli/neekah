import 'common.dart';
import 'parse.dart';

class PointsBreakdown {
  const PointsBreakdown({required this.reason, required this.label, required this.total, required this.awards});

  factory PointsBreakdown.fromJson(Json json) =>
      PointsBreakdown(reason: str(json['reason']), label: str(json['label']), total: toInt(json['total']), awards: toInt(json['awards']));

  final String reason;
  final String label;
  final int total;
  final int awards;
}

class PointsEntry {
  const PointsEntry({required this.id, required this.points, required this.label, this.createdAt});

  factory PointsEntry.fromJson(Json json) =>
      PointsEntry(id: str(json['id']), points: toInt(json['points']), label: str(json['label']), createdAt: dateTimeOrNull(json['created_at']));

  final String id;
  final int points;
  final String label;
  final DateTime? createdAt;
}

/// One thing the next tier needs, e.g. "Tempahan selesai 3 / 5".
class TierRequirement {
  const TierRequirement({required this.label, required this.current, required this.target, required this.met});

  factory TierRequirement.fromJson(Json json) =>
      TierRequirement(label: str(json['label']), current: toDouble(json['current']), target: toDouble(json['target']), met: toBool(json['met']));

  final String label;
  final double current;
  final double target;
  final bool met;

  double get progress => met ? 1 : (target <= 0 ? 0 : (current / target).clamp(0, 1).toDouble());
}

class PointsData {
  const PointsData({
    required this.pointsTotal,
    required this.penaltyPoints,
    required this.score,
    required this.responseRateLabel,
    required this.completedBookings,
    required this.tier,
    required this.breakdown,
    required this.recent,
    this.completionRate,
    this.nextTier,
    this.tierLocked = false,
    this.requirements = const [],
  });

  factory PointsData.fromJson(Json json) => PointsData(
    pointsTotal: toInt(json['points_total']),
    penaltyPoints: toInt(json['penalty_points']),
    score: toDouble(json['score']),
    completionRate: toDoubleOrNull(json['completion_rate']),
    responseRateLabel: str(json['response_rate_label']),
    completedBookings: toInt(json['completed_bookings']),
    tier: Tier.fromJson(json['tier']),
    nextTier: Tier.maybe(json['next_tier']),
    breakdown: listOf(json['breakdown'], PointsBreakdown.fromJson),
    recent: listOf(json['recent'], PointsEntry.fromJson),
    tierLocked: toBool(json['tier_locked']),
    requirements: listOf(json['requirements'], TierRequirement.fromJson),
  );

  final int pointsTotal;
  final int penaltyPoints;
  final double score;

  /// A percentage (0–100) when the API has enough bookings to judge.
  final double? completionRate;
  final String responseRateLabel;
  final int completedBookings;
  final Tier tier;
  final Tier? nextTier;
  final List<PointsBreakdown> breakdown;
  final List<PointsEntry> recent;

  /// True when the tier is held back (e.g. by a penalty) whatever the score.
  final bool tierLocked;

  /// What the next tier still needs.
  final List<TierRequirement> requirements;
}
