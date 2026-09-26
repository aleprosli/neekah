import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api/neekah_api.dart';
import '../../core/format.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/points.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/remote_view.dart';

class PointsScreen extends StatelessWidget {
  const PointsScreen({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(context.t('points.title'))),
    body: RemoteView<PointsData>(
      load: () => context.read<NeekahApi>().points(),
      builder: (context, data, refresh) => BrandRefresh(
        onRefresh: refresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
          children: [
            _TierCard(data: data),
            const SizedBox(height: 16),
            _Metrics(data: data),
            if (data.requirements.isNotEmpty) ...[
              SectionTitle(
                data.nextTier == null ? context.t('points.requirements') : context.t('points.requirements_for', {'tier': data.nextTier!.label}),
              ),
              NCard(
                child: Column(
                  children: [
                    for (var i = 0; i < data.requirements.length; i++) ...[
                      if (i > 0) const SizedBox(height: 14),
                      _RequirementRow(requirement: data.requirements[i]),
                    ],
                  ],
                ),
              ),
            ],
            if (data.breakdown.isNotEmpty) ...[
              SectionTitle(context.t('points.breakdown')),
              NCard(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Column(
                  children: [
                    for (var i = 0; i < data.breakdown.length; i++) ...[
                      if (i > 0) const Divider(indent: 16, endIndent: 16),
                      _BreakdownRow(item: data.breakdown[i]),
                    ],
                  ],
                ),
              ),
            ],
            if (data.recent.isNotEmpty) ...[
              SectionTitle(context.t('points.recent')),
              NCard(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Column(
                  children: [
                    for (var i = 0; i < data.recent.length; i++) ...[
                      if (i > 0) const Divider(indent: 16, endIndent: 16),
                      _RecentRow(entry: data.recent[i]),
                    ],
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    ),
  );
}

class _TierCard extends StatelessWidget {
  const _TierCard({required this.data});

  final PointsData data;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.fromLTRB(20, 22, 20, 20),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(24),
      gradient: const LinearGradient(colors: [Color(0xFF8E2236), NColors.wineDark], begin: Alignment.topLeft, end: Alignment.bottomRight),
      boxShadow: softShadow,
    ),
    child: Column(
      children: [
        SizedBox(
          width: 200,
          height: 118,
          child: TweenAnimationBuilder<double>(
            tween: Tween(begin: 0, end: data.score.clamp(0, 100).toDouble()),
            duration: const Duration(milliseconds: 900),
            curve: Curves.easeOutCubic,
            builder: (context, value, _) => CustomPaint(
              painter: _GaugePainter(value / 100),
              child: Align(
                alignment: Alignment.bottomCenter,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      value.toStringAsFixed(value >= 10 ? 0 : 1),
                      style: const TextStyle(
                        fontFamily: headingFont,
                        fontFeatures: liningFigures,
                        fontSize: 40,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        height: 1,
                      ),
                    ),
                    Text(context.t('points.score_of'), style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12)),
                  ],
                ),
              ),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.military_tech_rounded, color: NColors.goldLight),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                data.tier.label,
                style: const TextStyle(
                  fontFamily: headingFont,
                  fontFeatures: liningFigures,
                  fontSize: 24,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          data.nextTier == null ? context.t('points.top_tier') : context.t('points.next_tier', {'tier': data.nextTier!.label}),
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.white.withValues(alpha: 0.78)),
        ),
        if (data.tierLocked) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(NRadius.small)),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.lock_rounded, size: 16, color: NColors.goldLight),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(context.t('points.tier_locked'), style: const TextStyle(color: Colors.white, fontSize: 12.5)),
                ),
              ],
            ),
          ),
        ],
      ],
    ),
  );
}

class _GaugePainter extends CustomPainter {
  _GaugePainter(this.progress);

  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    const stroke = 14.0;
    final radius = math.min(size.width / 2, size.height) - stroke / 2;
    final center = Offset(size.width / 2, size.height - 2);
    final rect = Rect.fromCircle(center: center, radius: radius);
    final track = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = stroke
      ..strokeCap = StrokeCap.round
      ..color = Colors.white.withValues(alpha: 0.14);
    canvas.drawArc(rect, math.pi, math.pi, false, track);
    if (progress > 0) {
      final fill = Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = stroke
        ..strokeCap = StrokeCap.round
        ..shader = const SweepGradient(startAngle: math.pi, endAngle: math.pi * 2, colors: [NColors.gold, NColors.goldLight]).createShader(rect);
      canvas.drawArc(rect, math.pi, math.pi * progress.clamp(0, 1), false, fill);
    }
  }

  @override
  bool shouldRepaint(covariant _GaugePainter oldDelegate) => oldDelegate.progress != progress;
}

class _Metrics extends StatelessWidget {
  const _Metrics({required this.data});

  final PointsData data;

  @override
  Widget build(BuildContext context) {
    final tiles = [
      (Icons.stars_rounded, 'amber', Fmt.number(data.pointsTotal), context.t('points.total')),
      (Icons.gavel_rounded, data.penaltyPoints > 0 ? 'red' : 'muted', Fmt.number(data.penaltyPoints), context.t('points.penalty')),
      (Icons.task_alt_rounded, 'emerald', Fmt.number(data.completedBookings), context.t('points.completed')),
      (
        Icons.pie_chart_rounded,
        'sky',
        data.completionRate == null ? '—' : '${data.completionRate!.toStringAsFixed(0)}%',
        context.t('points.completion_rate'),
      ),
    ];

    return Column(
      children: [
        for (var row = 0; row < tiles.length; row += 2) ...[
          if (row > 0) const SizedBox(height: 12),
          IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (var i = row; i < row + 2; i++) ...[
                  if (i > row) const SizedBox(width: 12),
                  Expanded(
                    child: NCard(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          IconTile(tiles[i].$1, tone: tiles[i].$2, size: 38),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                FittedBox(
                                  fit: BoxFit.scaleDown,
                                  alignment: Alignment.centerLeft,
                                  child: Text(
                                    tiles[i].$3,
                                    style: const TextStyle(
                                      fontFamily: headingFont,
                                      fontFeatures: liningFigures,
                                      fontSize: 20,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                Text(
                                  tiles[i].$4,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(fontSize: 11.5, color: NColors.inkMuted),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
        const SizedBox(height: 12),
        NCard(
          child: Row(
            children: [
              const IconTile(Icons.speed_rounded, tone: 'brand', size: 38),
              const SizedBox(width: 12),
              Expanded(
                child: Text(context.t('points.response_rate'), style: const TextStyle(fontWeight: FontWeight.w600)),
              ),
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 150),
                child: Text(
                  data.responseRateLabel.isEmpty ? '—' : data.responseRateLabel,
                  textAlign: TextAlign.end,
                  style: const TextStyle(fontWeight: FontWeight.w800, color: NColors.wine),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _RequirementRow extends StatelessWidget {
  const _RequirementRow({required this.requirement});

  final TierRequirement requirement;

  String _number(double value) => value == value.roundToDouble() ? value.toStringAsFixed(0) : value.toStringAsFixed(1);

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Row(
        children: [
          Icon(
            requirement.met ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
            size: 20,
            color: requirement.met ? const Color(0xFF047857) : NColors.line,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(requirement.label, style: const TextStyle(fontWeight: FontWeight.w600)),
          ),
          Text('${_number(requirement.current)} / ${_number(requirement.target)}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
        ],
      ),
      const SizedBox(height: 8),
      ClipRRect(
        borderRadius: BorderRadius.circular(4),
        child: LinearProgressIndicator(value: requirement.progress, minHeight: 6, color: requirement.met ? const Color(0xFF047857) : NColors.gold),
      ),
    ],
  );
}

class _BreakdownRow extends StatelessWidget {
  const _BreakdownRow({required this.item});

  final PointsBreakdown item;

  @override
  Widget build(BuildContext context) => ListTile(
    dense: true,
    title: Text(item.label, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
    subtitle: Text(context.t('points.awards', {'n': item.awards})),
    trailing: Text(
      Fmt.number(item.total),
      style: TextStyle(
        fontFamily: headingFont,
        fontFeatures: liningFigures,
        fontWeight: FontWeight.w700,
        fontSize: 17,
        color: item.total > 0 ? NColors.ink : NColors.line,
      ),
    ),
  );
}

class _RecentRow extends StatelessWidget {
  const _RecentRow({required this.entry});

  final PointsEntry entry;

  @override
  Widget build(BuildContext context) {
    final positive = entry.points >= 0;

    return ListTile(
      leading: IconTile(positive ? Icons.trending_up_rounded : Icons.trending_down_rounded, tone: positive ? 'emerald' : 'red', size: 38),
      title: Text(entry.label, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
      subtitle: Text(context.ago(entry.createdAt)),
      trailing: Text(
        '${positive ? '+' : ''}${entry.points}',
        style: TextStyle(
          fontFamily: headingFont,
          fontFeatures: liningFigures,
          fontWeight: FontWeight.w700,
          fontSize: 18,
          color: ToneColors.of(positive ? 'emerald' : 'red').foreground,
        ),
      ),
    );
  }
}
