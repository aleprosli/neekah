import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api/neekah_api.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/format.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/dashboard.dart';
import '../../core/notification_badge.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/remote_view.dart';
import '../../ui/widgets/skeleton.dart';
import '../bookings/booking_tile.dart';
import '../calendar/online_booking_card.dart';
import 'views_chart.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
    body: RemoteView<Dashboard>(
      topics: const {Topic.dashboard},
      load: () async {
        final dashboard = await context.read<NeekahApi>().dashboard();
        if (context.mounted) {
          context.read<AuthController>().updateVendor(dashboard.vendor);
        }

        return dashboard;
      },
      skeleton: const SafeArea(child: SkeletonList(header: true, count: 3)),
      builder: (context, data, refresh) => BrandRefresh(
        onRefresh: () async {
          await Future.wait([refresh(), context.read<NotificationBadge>().refresh()]);
        },
        child: _DashboardBody(data: data),
      ),
    ),
  );
}

class _DashboardBody extends StatelessWidget {
  const _DashboardBody({required this.data});

  final Dashboard data;

  @override
  Widget build(BuildContext context) {
    final stats = data.stats;

    return CustomScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: [
        SliverToBoxAdapter(child: _Header(data: data)),
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
          sliver: SliverList.list(
            children: [
              _StatGrid(
                cards: [
                  _StatCardData(Icons.event_available_rounded, 'emerald', '${stats.upcomingEvents}', context.t('dash.upcoming_events'), '/bookings'),
                  _StatCardData(Icons.hourglass_bottom_rounded, 'amber', '${stats.awaitingDeposit}', context.t('dash.awaiting_deposit'), '/bookings'),
                  _StatCardData(Icons.mark_chat_unread_rounded, 'brand', '${stats.openEnquiries}', context.t('dash.open_enquiries'), '/enquiries'),
                  _StatCardData(
                    Icons.fact_check_rounded,
                    stats.paymentsToVerify > 0 ? 'red' : 'sky',
                    '${stats.paymentsToVerify}',
                    context.t('dash.payments_to_verify'),
                    '/bookings',
                  ),
                ],
              ),
              if (data.elite == 'need_tier') ...[const SizedBox(height: 12), _EliteHint()],
              SectionTitle(context.t('dash.reach_title')),
              _ReachCard(data: data),
              const SizedBox(height: 16),
              OnlineBookingCard(online: data.online),
              SectionTitle(
                context.t('dash.upcoming_title'),
                action: TextButton(onPressed: () => context.go('/bookings'), child: Text(context.t('common.see_all'))),
                padding: const EdgeInsets.fromLTRB(4, 20, 0, 8),
              ),
              if (data.upcoming.isEmpty)
                NCard(
                  child: Row(
                    children: [
                      const IconTile(Icons.event_busy_rounded, tone: 'muted'),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Text(context.t('dash.no_upcoming'), style: const TextStyle(color: NColors.inkMuted)),
                      ),
                    ],
                  ),
                )
              else
                for (final booking in data.upcoming)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: BookingTile(booking: booking),
                  ),
              const SizedBox(height: 8),
              _BoostShortcut(tokens: data.vendor.boostTokens),
            ],
          ),
        ),
      ],
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.data});

  final Dashboard data;

  String _greetingKey() {
    final hour = Fmt.nowKl().hour;
    if (hour < 12) {
      return 'dash.greeting_morning';
    }
    if (hour < 15) {
      return 'dash.greeting_noon';
    }
    if (hour < 19) {
      return 'dash.greeting_evening';
    }

    return 'dash.greeting_night';
  }

  @override
  Widget build(BuildContext context) {
    final vendor = data.vendor;
    final unread = context.watch<NotificationBadge>().unread;

    return Container(
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(32)),
        gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFF8E2236), NColors.wineDark]),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 12, 22),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  VendorAvatar(initial: vendor.initial, logoUrl: vendor.logoUrl, size: 50),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(context.t(_greetingKey()), style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 13)),
                        const SizedBox(height: 2),
                        Text(
                          vendor.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontFamily: headingFont,
                            fontFeatures: liningFigures,
                            fontSize: 22,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    tooltip: context.t('notifications.title'),
                    onPressed: () => context.push('/notifications'),
                    icon: CountBadge(
                      count: unread,
                      child: const Icon(Icons.notifications_none_rounded, color: Colors.white),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  const ProBadge(),
                  if (vendor.isElite) const ProBadge(elite: true),
                  if (vendor.proUntil != null)
                    _GlassChip(
                      icon: Icons.schedule_rounded,
                      label: context.t('dash.pro_until', {'date': context.fmt.date(Fmt.klWallClock(vendor.proUntil!))}),
                    ),
                  if (vendor.tier.label.isNotEmpty) _GlassChip(icon: Icons.military_tech_rounded, label: vendor.tier.label),
                ],
              ),
              const SizedBox(height: 20),
              Text(context.t('dash.paid_total'), style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 13)),
              const SizedBox(height: 2),
              FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(
                  Fmt.money(data.stats.paidTotal),
                  style: const TextStyle(
                    fontFamily: headingFont,
                    fontFeatures: liningFigures,
                    fontSize: 34,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _GlassChip extends StatelessWidget {
  const _GlassChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
    decoration: BoxDecoration(
      color: Colors.white.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(NRadius.pill),
      border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 13, color: NColors.goldLight),
        const SizedBox(width: 5),
        Text(
          label,
          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
        ),
      ],
    ),
  );
}

class _StatCardData {
  const _StatCardData(this.icon, this.tone, this.value, this.label, this.route);

  final IconData icon;
  final String tone;
  final String value;
  final String label;
  final String route;
}

class _StatGrid extends StatelessWidget {
  const _StatGrid({required this.cards});

  final List<_StatCardData> cards;

  @override
  Widget build(BuildContext context) => Column(
    children: [
      for (var row = 0; row < cards.length; row += 2) ...[
        if (row > 0) const SizedBox(height: 12),
        IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(child: _StatCard(card: cards[row])),
              const SizedBox(width: 12),
              Expanded(child: row + 1 < cards.length ? _StatCard(card: cards[row + 1]) : const SizedBox()),
            ],
          ),
        ),
      ],
    ],
  );
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.card});

  final _StatCardData card;

  @override
  Widget build(BuildContext context) => NCard(
    padding: const EdgeInsets.all(14),
    onTap: () => context.go(card.route),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconTile(card.icon, tone: card.tone, size: 36),
            const Spacer(),
            const Icon(Icons.chevron_right_rounded, size: 18, color: NColors.line),
          ],
        ),
        const SizedBox(height: 12),
        Text(
          card.value,
          style: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontSize: 28, fontWeight: FontWeight.w700, height: 1),
        ),
        const SizedBox(height: 6),
        Text(
          card.label,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(color: NColors.inkMuted, fontSize: 12.5, height: 1.25),
        ),
      ],
    ),
  );
}

class _ReachCard extends StatelessWidget {
  const _ReachCard({required this.data});

  final Dashboard data;

  @override
  Widget build(BuildContext context) {
    final reach = data.reach;

    return NCard(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(context.t('dash.profile_views'), style: const TextStyle(color: NColors.inkMuted, fontSize: 13)),
                    Text(
                      Fmt.number(reach.profileViews),
                      style: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontSize: 30, fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
              ),
              TonePill(label: context.t('dash.last_days', {'n': reach.days}), tone: 'muted', dense: true),
            ],
          ),
          const SizedBox(height: 12),
          ViewsChart(days: data.dailyViews),
          const SizedBox(height: 8),
          const Divider(),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Row(
              children: [
                Expanded(
                  child: _ReachMetric(icon: Icons.visibility_rounded, tone: 'brand', value: reach.profileViews, label: context.t('dash.views')),
                ),
                Expanded(
                  child: _ReachMetric(icon: Icons.chat_rounded, tone: 'emerald', value: reach.whatsappClicks, label: 'WhatsApp'),
                ),
                Expanded(
                  child: _ReachMetric(icon: Icons.call_rounded, tone: 'sky', value: reach.phoneClicks, label: context.t('dash.phone_taps')),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ReachMetric extends StatelessWidget {
  const _ReachMetric({required this.icon, required this.tone, required this.value, required this.label});

  final IconData icon;
  final String tone;
  final int value;
  final String label;

  @override
  Widget build(BuildContext context) => Column(
    children: [
      IconTile(icon, tone: tone, size: 32),
      const SizedBox(height: 6),
      Text(
        Fmt.number(value),
        style: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontWeight: FontWeight.w700, fontSize: 18),
      ),
      Text(
        label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(color: NColors.inkMuted, fontSize: 11.5),
      ),
    ],
  );
}

class _BoostShortcut extends StatelessWidget {
  const _BoostShortcut({required this.tokens});

  final int tokens;

  @override
  Widget build(BuildContext context) => Material(
    borderRadius: BorderRadius.circular(NRadius.card),
    clipBehavior: Clip.antiAlias,
    child: InkWell(
      onTap: () => context.push('/boost'),
      child: Ink(
        padding: const EdgeInsets.all(16),
        decoration: const BoxDecoration(
          gradient: LinearGradient(colors: [Color(0xFF2A1E1F), Color(0xFF4A2F31)], begin: Alignment.topLeft, end: Alignment.bottomRight),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(color: NColors.goldLight.withValues(alpha: 0.16), borderRadius: BorderRadius.circular(14)),
              child: const Icon(Icons.rocket_launch_rounded, color: NColors.goldLight),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    context.t('dash.boost_title'),
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 15),
                  ),
                  const SizedBox(height: 2),
                  Text(context.t('dash.boost_body'), style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12.5)),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              children: [
                Text(
                  '$tokens',
                  style: const TextStyle(
                    fontFamily: headingFont,
                    fontFeatures: liningFigures,
                    color: NColors.goldLight,
                    fontSize: 26,
                    fontWeight: FontWeight.w700,
                    height: 1,
                  ),
                ),
                Text(context.t('boost.tokens'), style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 11)),
              ],
            ),
          ],
        ),
      ),
    ),
  );
}

class _EliteHint extends StatelessWidget {
  @override
  Widget build(BuildContext context) => NCard(
    color: NColors.goldSoft,
    onTap: () => context.push('/points'),
    child: Row(
      children: [
        const Icon(Icons.diamond_outlined, color: NColors.gold),
        const SizedBox(width: 12),
        Expanded(
          child: Text(context.t('dash.elite_need_tier'), style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600)),
        ),
        const Icon(Icons.chevron_right_rounded, color: NColors.gold),
      ],
    ),
  );
}
