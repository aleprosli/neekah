import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/auth/auth_controller.dart';
import '../../core/l10n/l10n.dart';
import '../../core/notification_badge.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';

class MoreScreen extends StatelessWidget {
  const MoreScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final vendor = auth.vendor;
    final unread = context.watch<NotificationBadge>().unread;

    return Scaffold(
      appBar: AppBar(title: Text(context.t('more.title'))),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
        children: [
          if (vendor != null)
            NCard(
              padding: const EdgeInsets.all(18),
              child: Row(
                children: [
                  VendorAvatar(initial: vendor.initial, logoUrl: vendor.logoUrl, size: 58),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(vendor.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.titleLarge),
                        if (vendor.category != null || vendor.location != null)
                          Text(
                            [vendor.category, vendor.location].whereType<String>().join(' · '),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: [
                            const ProBadge(),
                            if (vendor.isElite) const ProBadge(elite: true),
                            if (vendor.ratingAvg != null)
                              TonePill(
                                label: '${vendor.ratingAvg!.toStringAsFixed(1)} (${vendor.reviewsCount})',
                                tone: 'amber',
                                icon: Icons.star_rounded,
                                dense: true,
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 20),
          _MenuGroup(
            children: [
              _MenuTile(
                icon: Icons.rocket_launch_rounded,
                tone: 'amber',
                title: context.t('more.boost'),
                subtitle: vendor == null ? null : context.t('more.boost_sub', {'n': vendor.boostTokens}),
                onTap: () => context.push('/boost'),
              ),
              _MenuTile(
                icon: Icons.military_tech_rounded,
                tone: 'brand',
                title: context.t('more.points'),
                subtitle: vendor == null ? null : context.t('more.points_sub', {'tier': vendor.tier.label, 'points': vendor.pointsTotal}),
                onTap: () => context.push('/points'),
              ),
              _MenuTile(
                icon: Icons.notifications_rounded,
                tone: 'sky',
                title: context.t('notifications.title'),
                subtitle: unread > 0 ? context.t('more.unread', {'n': unread}) : context.t('more.all_read'),
                trailing: unread > 0 ? CountBadge(count: unread, child: const SizedBox(width: 8, height: 8)) : null,
                onTap: () => context.push('/notifications'),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _MenuGroup(
            children: [
              if (vendor != null && vendor.publicUrl.isNotEmpty)
                _MenuTile(
                  icon: Icons.storefront_rounded,
                  tone: 'emerald',
                  title: context.t('more.public_profile'),
                  subtitle: context.t('more.public_profile_sub'),
                  external: true,
                  onTap: () => openExternal(context, vendor.publicUrl),
                ),
              _MenuTile(
                icon: Icons.settings_rounded,
                tone: 'muted',
                title: context.t('settings.title'),
                subtitle: context.t('more.settings_sub'),
                onTap: () => context.push('/settings'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MenuGroup extends StatelessWidget {
  const _MenuGroup({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) => NCard(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: Column(
      children: [
        for (var i = 0; i < children.length; i++) ...[if (i > 0) const Divider(indent: 72, endIndent: 16), children[i]],
      ],
    ),
  );
}

class _MenuTile extends StatelessWidget {
  const _MenuTile({
    required this.icon,
    required this.tone,
    required this.title,
    required this.onTap,
    this.subtitle,
    this.trailing,
    this.external = false,
  });

  final IconData icon;
  final String tone;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final bool external;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => ListTile(
    onTap: onTap,
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
    leading: IconTile(icon, tone: tone, size: 42),
    title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
    subtitle: subtitle == null ? null : Text(subtitle!, maxLines: 1, overflow: TextOverflow.ellipsis),
    trailing: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        ?trailing,
        const SizedBox(width: 6),
        Icon(external ? Icons.open_in_new_rounded : Icons.chevron_right_rounded, color: NColors.inkMuted, size: external ? 18 : 22),
      ],
    ),
  );
}
