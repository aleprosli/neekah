import 'dart:math' as math;

import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../../core/format.dart';
import '../../core/models/dashboard.dart';
import '../../ui/theme.dart';

/// Profile views per day as a soft wine area line.
class ViewsChart extends StatelessWidget {
  const ViewsChart({required this.days, super.key});

  final List<DailyViews> days;

  @override
  Widget build(BuildContext context) {
    if (days.isEmpty) {
      return const SizedBox(height: 150);
    }
    final peak = days.map((day) => day.views).reduce(math.max).toDouble();
    final maxY = peak <= 0 ? 4.0 : (peak * 1.25).ceilToDouble();
    final labelEvery = math.max(1, (days.length / 4).ceil());

    return SizedBox(
      height: 160,
      child: LineChart(
        LineChartData(
          minX: 0,
          maxX: (days.length - 1).toDouble(),
          minY: 0,
          maxY: maxY,
          gridData: FlGridData(
            drawVerticalLine: false,
            horizontalInterval: maxY / 3,
            getDrawingHorizontalLine: (_) => const FlLine(color: NColors.lineSoft, strokeWidth: 1, dashArray: [4, 4]),
          ),
          borderData: FlBorderData(show: false),
          titlesData: FlTitlesData(
            topTitles: const AxisTitles(),
            rightTitles: const AxisTitles(),
            leftTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 32,
                interval: maxY / 3,
                getTitlesWidget: (value, meta) => value == 0 || value >= maxY
                    ? const SizedBox.shrink()
                    : Text(Fmt.number(value.round()), style: const TextStyle(fontSize: 10, color: NColors.inkMuted)),
              ),
            ),
            bottomTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 24,
                interval: 1,
                getTitlesWidget: (value, meta) {
                  final index = value.round();
                  final show = index == days.length - 1 || (index % labelEvery == 0 && days.length - 1 - index >= labelEvery / 2);
                  if (!show || index < 0 || index >= days.length) {
                    return const SizedBox.shrink();
                  }

                  return SideTitleWidget(
                    meta: meta,
                    fitInside: SideTitleFitInsideData.fromTitleMeta(meta, distanceFromEdge: 0),
                    child: Text(days[index].label, style: const TextStyle(fontSize: 10, color: NColors.inkMuted)),
                  );
                },
              ),
            ),
          ),
          lineTouchData: LineTouchData(
            touchTooltipData: LineTouchTooltipData(
              getTooltipColor: (_) => NColors.ink,
              tooltipBorderRadius: BorderRadius.circular(10),
              getTooltipItems: (spots) => [
                for (final spot in spots)
                  LineTooltipItem(
                    '${days[spot.x.round()].label}\n',
                    const TextStyle(color: Colors.white70, fontSize: 11),
                    children: [
                      TextSpan(
                        text: Fmt.number(spot.y.round()),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
                      ),
                    ],
                  ),
              ],
            ),
          ),
          lineBarsData: [
            LineChartBarData(
              spots: [for (var i = 0; i < days.length; i++) FlSpot(i.toDouble(), days[i].views.toDouble())],
              isCurved: true,
              curveSmoothness: 0.3,
              preventCurveOverShooting: true,
              color: NColors.wine,
              barWidth: 2.6,
              isStrokeCapRound: true,
              dotData: FlDotData(
                checkToShowDot: (spot, _) => spot.x == days.length - 1,
                getDotPainter: (_, _, _, _) => FlDotCirclePainter(radius: 4, color: Colors.white, strokeWidth: 2.5, strokeColor: NColors.wine),
              ),
              belowBarData: BarAreaData(
                show: true,
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [NColors.wine.withValues(alpha: 0.22), NColors.wine.withValues(alpha: 0.0)],
                ),
              ),
            ),
          ],
        ),
        duration: const Duration(milliseconds: 400),
      ),
    );
  }
}
