import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/l10n/l10n.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';

/// "Pro diperlukan" — shown whenever the API answers 403 pro_required.
class ProLockScreen extends StatefulWidget {
  const ProLockScreen({super.key});

  @override
  State<ProLockScreen> createState() => _ProLockScreenState();
}

class _ProLockScreenState extends State<ProLockScreen> {
  bool _checking = false;

  static const _benefits = [
    (Icons.event_available_rounded, 'lock.benefit_bookings'),
    (Icons.calendar_month_rounded, 'lock.benefit_calendar'),
    (Icons.forum_rounded, 'lock.benefit_enquiries'),
    (Icons.rocket_launch_rounded, 'lock.benefit_boost'),
    (Icons.insights_rounded, 'lock.benefit_reach'),
  ];

  Future<void> _checkAgain(AuthController auth) async {
    setState(() => _checking = true);
    try {
      await auth.refreshMe();
      if (mounted && auth.status == AuthStatus.proRequired) {
        showMessage(context, context.t('lock.still_locked'), error: true);
      }
    } on ApiException catch (error) {
      if (mounted) {
        showError(context, error);
      }
    } finally {
      if (mounted) {
        setState(() => _checking = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final theme = Theme.of(context);
    final vendorName = auth.vendor?.name;

    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 32, 20, 24),
          children: [
            Center(
              child: Container(
                width: 96,
                height: 96,
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: LinearGradient(colors: [NColors.goldLight, NColors.gold], begin: Alignment.topLeft, end: Alignment.bottomRight),
                  boxShadow: [BoxShadow(color: Color(0x33C9A24A), blurRadius: 24, offset: Offset(0, 10))],
                ),
                child: const Icon(Icons.lock_rounded, size: 44, color: Colors.white),
              ),
            ),
            const SizedBox(height: 24),
            Text(context.t('lock.title'), textAlign: TextAlign.center, style: theme.textTheme.headlineMedium),
            const SizedBox(height: 10),
            const Center(child: GoldRule()),
            const SizedBox(height: 14),
            Text(
              auth.reasonMessage ?? context.t(vendorName == null ? 'lock.body' : 'lock.body_named', {'name': vendorName}),
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyLarge?.copyWith(color: NColors.inkMuted),
            ),
            const SizedBox(height: 24),
            NCard(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Column(
                children: [
                  for (final (icon, key) in _benefits)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Row(
                        children: [
                          IconTile(icon, size: 40),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Text(context.t(key), style: const TextStyle(fontWeight: FontWeight.w600)),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            FilledButton.icon(
              key: const Key('lock.upgrade'),
              onPressed: () => openExternal(context, auth.proUrl ?? auth.config?.proUrl),
              icon: const Icon(Icons.workspace_premium_rounded),
              label: Text(context.t('lock.upgrade')),
            ),
            const SizedBox(height: 10),
            if (auth.hasToken) ...[
              OutlinedButton.icon(
                onPressed: _checking ? null : () => _checkAgain(auth),
                icon: _checking
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.refresh_rounded),
                label: Text(context.t('lock.check_again')),
              ),
              TextButton(onPressed: auth.logout, child: Text(context.t('settings.logout'))),
            ] else
              OutlinedButton(onPressed: auth.backToLogin, child: Text(context.t('common.back_to_login'))),
          ],
        ),
      ),
    );
  }
}
