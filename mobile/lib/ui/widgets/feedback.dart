import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/l10n/l10n.dart';
import '../theme.dart';

/// A readable sentence for any failure.
String describeError(BuildContext context, Object error) {
  if (error is ValidationException) {
    return error.firstMessage.isNotEmpty ? error.firstMessage : context.t('error.generic');
  }
  if (error is ApiException && error.message.isNotEmpty && error is! ServerException) {
    return error.message;
  }

  return switch (error) {
    NetworkException() => context.t('error.network'),
    RateLimitedException() => context.t('error.rate_limited'),
    NotFoundException() => context.t('error.not_found'),
    ServerException() => context.t('error.server'),
    _ => context.t('error.generic'),
  };
}

void showMessage(BuildContext context, String message, {bool error = false}) {
  if (message.isEmpty) {
    return;
  }
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(
              error ? Icons.error_outline_rounded : Icons.check_circle_rounded,
              color: error ? const Color(0xFFFCA5A5) : NColors.goldLight,
              size: 20,
            ),
            const SizedBox(width: 12),
            Expanded(child: Text(message)),
          ],
        ),
      ),
    );
}

void showError(BuildContext context, Object error) => showMessage(context, describeError(context, error), error: true);

Future<bool> confirmAction(
  BuildContext context, {
  required String title,
  required String message,
  required String confirmLabel,
  bool destructive = false,
}) async {
  HapticFeedback.selectionClick();
  final result = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      title: Text(title),
      content: Text(message),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context, false), child: Text(context.t('common.cancel'))),
        FilledButton(
          style: destructive
              ? FilledButton.styleFrom(backgroundColor: const Color(0xFFB91C1C), minimumSize: const Size(64, 44))
              : FilledButton.styleFrom(minimumSize: const Size(64, 44)),
          onPressed: () => Navigator.pop(context, true),
          child: Text(confirmLabel),
        ),
      ],
    ),
  );

  return result ?? false;
}

/// Opens a web page, phone dialer or WhatsApp outside the app.
Future<void> openExternal(BuildContext context, String? url) async {
  if (url == null || url.isEmpty) {
    return;
  }
  HapticFeedback.lightImpact();
  final uri = Uri.tryParse(url);
  var opened = false;
  if (uri != null) {
    try {
      opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    } on PlatformException {
      opened = false;
    }
  }
  if (!opened && context.mounted) {
    showMessage(context, context.t('error.open_link'), error: true);
  }
}

Future<void> callPhone(BuildContext context, String? phone) =>
    openExternal(context, phone == null ? null : 'tel:${phone.replaceAll(RegExp(r'[^0-9+]'), '')}');
