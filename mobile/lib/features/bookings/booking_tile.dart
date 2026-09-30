import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/format.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/booking.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';

/// Calendar-leaf date block: day number over the short month.
class DateBlock extends StatelessWidget {
  const DateBlock({required this.date, this.tone = 'brand', this.size = 54, super.key});

  final DateTime? date;
  final String tone;
  final double size;

  @override
  Widget build(BuildContext context) {
    final colors = ToneColors.of(tone);

    return Container(
      width: size,
      height: size + 4,
      decoration: BoxDecoration(color: colors.background, borderRadius: BorderRadius.circular(16)),
      child: date == null
          ? Icon(Icons.event_rounded, color: colors.foreground)
          : Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  context.fmt.dayNumber(date!),
                  style: TextStyle(
                    fontFamily: headingFont,
                    fontFeatures: liningFigures,
                    fontSize: size * 0.4,
                    fontWeight: FontWeight.w700,
                    color: colors.foreground,
                    height: 1,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  context.fmt.monthShort(date!),
                  style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, letterSpacing: 0.8, color: colors.foreground),
                ),
              ],
            ),
    );
  }
}

class BookingTile extends StatelessWidget {
  const BookingTile({required this.booking, super.key});

  final BookingSummary booking;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return NCard(
      padding: const EdgeInsets.all(14),
      onTap: () => context.push('/booking/${Uri.encodeComponent(booking.reference)}'),
      child: Row(
        children: [
          DateBlock(date: booking.eventDate, tone: booking.status.tone),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        booking.customerName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.titleMedium?.copyWith(fontSize: 15.5),
                      ),
                    ),
                    if (booking.isOnline) ...[
                      const SizedBox(width: 6),
                      Tooltip(
                        message: context.t('booking.online'),
                        child: const Icon(Icons.bolt_rounded, size: 18, color: NColors.gold),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(booking.packageName, maxLines: 1, overflow: TextOverflow.ellipsis, style: theme.textTheme.bodySmall),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: Align(alignment: Alignment.centerLeft, child: TonePill.status(booking.status, dense: true)),
                    ),
                    const SizedBox(width: 8),
                    Text(Fmt.money(booking.total), maxLines: 1, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                  ],
                ),
                if (booking.total > 0 && booking.status.value != 'cancelled') ...[
                  const SizedBox(height: 8),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: booking.paidRatio,
                      minHeight: 4,
                      color: booking.paidRatio >= 1 ? const Color(0xFF047857) : NColors.gold,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
