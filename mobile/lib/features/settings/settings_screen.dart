import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/auth/auth_controller.dart';
import '../../core/config.dart';
import '../../core/format.dart';
import '../../core/l10n/l10n.dart';
import '../../core/settings/locale_controller.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  Future<void> _logout(BuildContext context) async {
    final auth = context.read<AuthController>();
    final confirmed = await confirmAction(
      context,
      title: context.t('settings.logout_title'),
      message: context.t('settings.logout_body'),
      confirmLabel: context.t('settings.logout'),
      destructive: true,
    );
    if (confirmed) {
      await auth.logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final locale = context.watch<LocaleController>();
    final auth = context.watch<AuthController>();
    final user = auth.user;
    final vendor = auth.vendor;
    final proUntil = auth.pro?.until ?? vendor?.proUntil;

    return Scaffold(
      appBar: AppBar(title: Text(context.t('settings.title'))),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
        children: [
          SectionTitle(context.t('settings.language'), padding: const EdgeInsets.fromLTRB(4, 8, 4, 12)),
          NCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(
                  width: double.infinity,
                  child: SegmentedButton<String>(
                    showSelectedIcon: false,
                    style: SegmentedButton.styleFrom(
                      selectedBackgroundColor: NColors.wine,
                      selectedForegroundColor: Colors.white,
                      side: const BorderSide(color: NColors.line),
                    ),
                    segments: const [
                      ButtonSegment(value: 'ms', label: Text('Bahasa Melayu')),
                      ButtonSegment(value: 'en', label: Text('English')),
                    ],
                    selected: {locale.code},
                    onSelectionChanged: (selection) {
                      HapticFeedback.selectionClick();
                      locale.setCode(selection.first);
                    },
                  ),
                ),
                const SizedBox(height: 10),
                Text(context.t('settings.language_help'), style: Theme.of(context).textTheme.bodySmall),
              ],
            ),
          ),
          SectionTitle(context.t('settings.account')),
          NCard(
            child: Column(
              children: [
                if (user != null) ...[
                  InfoRow(icon: Icons.person_outline_rounded, label: context.t('settings.name'), value: user.name),
                  InfoRow(icon: Icons.mail_outline_rounded, label: context.t('booking.email'), value: user.email),
                ],
                if (vendor != null) InfoRow(icon: Icons.storefront_outlined, label: context.t('settings.business'), value: vendor.name),
                InfoRow(
                  icon: Icons.workspace_premium_outlined,
                  label: context.t('settings.pro_until'),
                  value: proUntil == null ? '—' : context.fmt.date(Fmt.klWallClock(proUntil)),
                ),
                if (auth.pro?.daysLeft != null)
                  InfoRow(icon: Icons.hourglass_bottom_rounded, label: context.t('settings.days_left'), value: '${auth.pro!.daysLeft}'),
              ],
            ),
          ),
          const SizedBox(height: 16),
          NCard(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Column(
              children: [
                if (vendor != null && vendor.publicUrl.isNotEmpty)
                  ListTile(
                    leading: const Icon(Icons.storefront_rounded, color: NColors.wine),
                    title: Text(context.t('more.public_profile')),
                    trailing: const Icon(Icons.open_in_new_rounded, size: 18),
                    onTap: () => openExternal(context, vendor.publicUrl),
                  ),
                if ((auth.pro?.proUrl ?? auth.proUrl) != null)
                  ListTile(
                    leading: const Icon(Icons.workspace_premium_rounded, color: NColors.gold),
                    title: Text(context.t('settings.manage_pro')),
                    trailing: const Icon(Icons.open_in_new_rounded, size: 18),
                    onTap: () => openExternal(context, auth.pro?.proUrl ?? auth.proUrl),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          OutlinedButton.icon(
            style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFFB91C1C), minimumSize: const Size.fromHeight(52)),
            onPressed: () => _logout(context),
            icon: const Icon(Icons.logout_rounded),
            label: Text(context.t('settings.logout')),
          ),
          const SizedBox(height: 24),
          Center(child: Text('Neekah Pro ${AppConfig.appVersion}', style: Theme.of(context).textTheme.bodySmall)),
        ],
      ),
    );
  }
}
