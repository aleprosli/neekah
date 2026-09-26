import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth/auth_controller.dart';
import '../../core/l10n/l10n.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';
import 'brand_backdrop.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final error = auth.bootError;

    return Scaffold(
      backgroundColor: NColors.wineDark,
      body: BrandBackdrop(
        child: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(32),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const NeekahMark(size: 96),
                  const SizedBox(height: 24),
                  const Text(
                    'Neekah Pro',
                    style: TextStyle(
                      fontFamily: headingFont,
                      fontFeatures: liningFigures,
                      fontSize: 34,
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 10),
                  const GoldRule(width: 56),
                  const SizedBox(height: 14),
                  Text(
                    context.t('app.tagline'),
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 15),
                  ),
                  const SizedBox(height: 40),
                  if (error == null)
                    const SizedBox(width: 28, height: 28, child: CircularProgressIndicator(strokeWidth: 2.5, color: NColors.goldLight))
                  else ...[
                    Text(
                      describeError(context, error),
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.white),
                    ),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      style: FilledButton.styleFrom(backgroundColor: NColors.goldLight, foregroundColor: NColors.ink),
                      onPressed: auth.bootstrap,
                      icon: const Icon(Icons.refresh_rounded),
                      label: Text(context.t('common.retry')),
                    ),
                    TextButton(
                      onPressed: auth.logout,
                      child: Text(context.t('settings.logout'), style: const TextStyle(color: Colors.white70)),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
