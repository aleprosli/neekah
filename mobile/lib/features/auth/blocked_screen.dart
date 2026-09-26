import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth/auth_controller.dart';
import '../../core/l10n/l10n.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';

/// Not a vendor account, not approved yet, or deactivated.
class BlockedScreen extends StatelessWidget {
  const BlockedScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthController>();
    final code = auth.blockCode ?? 'not_vendor';
    final (icon, key) = switch (code) {
      'not_approved' => (Icons.hourglass_top_rounded, 'blocked.not_approved'),
      'account_inactive' => (Icons.person_off_rounded, 'blocked.inactive'),
      _ => (Icons.storefront_rounded, 'blocked.not_vendor'),
    };
    final registerUrl = auth.config?.registerUrl;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            child: EmptyState(
              icon: icon,
              title: context.t('$key.title'),
              message: auth.reasonMessage ?? context.t('$key.body'),
              action: Column(
                children: [
                  if (code == 'not_vendor' && registerUrl != null) ...[
                    FilledButton(onPressed: () => openExternal(context, registerUrl), child: Text(context.t('blocked.register'))),
                    const SizedBox(height: 10),
                  ],
                  OutlinedButton(onPressed: auth.backToLogin, child: Text(context.t('common.back_to_login'))),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
