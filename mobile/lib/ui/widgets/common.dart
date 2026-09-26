import 'package:flutter/material.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/common.dart';
import '../theme.dart';
import 'feedback.dart';

/// The white, softly shadowed card used everywhere.
class NCard extends StatelessWidget {
  const NCard({required this.child, this.padding = const EdgeInsets.all(16), this.onTap, this.color, this.border, super.key});

  final Widget child;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;
  final Color? color;
  final BoxBorder? border;

  @override
  Widget build(BuildContext context) {
    final radius = BorderRadius.circular(NRadius.card);

    return DecoratedBox(
      decoration: BoxDecoration(color: color ?? Colors.white, borderRadius: radius, boxShadow: softShadow, border: border),
      child: Material(
        type: MaterialType.transparency,
        child: InkWell(
          borderRadius: radius,
          onTap: onTap,
          child: Padding(padding: padding, child: child),
        ),
      ),
    );
  }
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.title, {this.action, this.padding = const EdgeInsets.fromLTRB(4, 24, 4, 12), super.key});

  final String title;
  final Widget? action;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) => Padding(
    padding: padding,
    child: Row(
      children: [
        Expanded(child: Text(title, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontSize: 19))),
        ?action,
      ],
    ),
  );
}

/// A rounded coloured pill for a status or tone.
class TonePill extends StatelessWidget {
  const TonePill({required this.label, required this.tone, this.icon, this.dense = false, super.key});

  factory TonePill.status(Status status, {bool dense = false}) => TonePill(label: status.label, tone: status.tone, dense: dense);

  final String label;
  final String tone;
  final IconData? icon;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final colors = ToneColors.of(tone);

    return Container(
      padding: EdgeInsets.symmetric(horizontal: dense ? 8 : 10, vertical: dense ? 3 : 5),
      decoration: BoxDecoration(color: colors.background, borderRadius: BorderRadius.circular(NRadius.pill)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[Icon(icon, size: dense ? 12 : 14, color: colors.foreground), const SizedBox(width: 4)],
          Flexible(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(color: colors.foreground, fontWeight: FontWeight.w700, fontSize: dense ? 11 : 12, height: 1.2),
            ),
          ),
        ],
      ),
    );
  }
}

/// Gold "PRO" / wine "ELITE" badges.
class ProBadge extends StatelessWidget {
  const ProBadge({this.elite = false, super.key});

  final bool elite;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
    decoration: BoxDecoration(
      gradient: elite
          ? const LinearGradient(colors: [Color(0xFF2A1E1F), Color(0xFF4A2F31)])
          : const LinearGradient(colors: [NColors.goldLight, NColors.gold]),
      borderRadius: BorderRadius.circular(NRadius.pill),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(elite ? Icons.diamond_rounded : Icons.workspace_premium_rounded, size: 13, color: elite ? NColors.goldLight : NColors.ink),
        const SizedBox(width: 4),
        Text(
          elite ? 'ELITE' : 'PRO',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 11, letterSpacing: 1.1, color: elite ? NColors.goldLight : NColors.ink),
        ),
      ],
    ),
  );
}

/// Vendor logo, or their initial on a wine disc, ringed in gold.
class VendorAvatar extends StatelessWidget {
  const VendorAvatar({required this.initial, this.logoUrl, this.size = 52, super.key});

  final String initial;
  final String? logoUrl;
  final double size;

  @override
  Widget build(BuildContext context) {
    final fallback = Center(
      child: Text(
        initial,
        style: TextStyle(
          fontFamily: headingFont,
          fontFeatures: liningFigures,
          fontSize: size * 0.42,
          color: Colors.white,
          fontWeight: FontWeight.w700,
        ),
      ),
    );

    return Container(
      width: size,
      height: size,
      padding: const EdgeInsets.all(2),
      decoration: const BoxDecoration(
        shape: BoxShape.circle,
        gradient: LinearGradient(colors: [NColors.goldLight, NColors.gold]),
      ),
      child: ClipOval(
        child: ColoredBox(
          color: NColors.wineDark,
          child: logoUrl == null
              ? fallback
              : Image.network(logoUrl!, fit: BoxFit.cover, width: size, height: size, errorBuilder: (_, _, _) => fallback),
        ),
      ),
    );
  }
}

/// Icon in a soft tinted rounded square.
class IconTile extends StatelessWidget {
  const IconTile(this.icon, {this.tone = 'brand', this.size = 44, super.key});

  final IconData icon;
  final String tone;
  final double size;

  @override
  Widget build(BuildContext context) {
    final colors = ToneColors.of(tone);

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: colors.background, borderRadius: BorderRadius.circular(size * 0.32)),
      child: Icon(icon, color: colors.foreground, size: size * 0.5),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({required this.icon, required this.title, this.message, this.action, super.key});

  final IconData icon;
  final String title;
  final String? message;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 40),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 88,
            height: 88,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: NColors.wineLight,
              border: Border.all(color: NColors.goldLight.withValues(alpha: 0.6), width: 2),
            ),
            child: Icon(icon, size: 38, color: NColors.wine),
          ),
          const SizedBox(height: 20),
          Text(title, textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontSize: 20)),
          if (message != null) ...[
            const SizedBox(height: 8),
            Text(
              message!,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: NColors.inkMuted),
            ),
          ],
          if (action != null) ...[const SizedBox(height: 20), action!],
        ],
      ),
    ),
  );
}

class ErrorState extends StatelessWidget {
  const ErrorState({required this.error, required this.onRetry, super.key});

  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => EmptyState(
    icon: error is NetworkException ? Icons.wifi_off_rounded : Icons.cloud_off_rounded,
    title: context.t(error is NetworkException ? 'error.offline_title' : 'error.title'),
    message: describeError(context, error),
    action: FilledButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh_rounded), label: Text(context.t('common.retry'))),
  );
}

/// Label/value row inside a card.
class InfoRow extends StatelessWidget {
  const InfoRow({required this.label, required this.value, this.icon, this.emphasis = false, super.key});

  final String label;
  final String value;
  final IconData? icon;
  final bool emphasis;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 7),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (icon != null) ...[Icon(icon, size: 18, color: NColors.inkMuted), const SizedBox(width: 10)],
        Expanded(
          flex: 2,
          child: Text(label, style: const TextStyle(color: NColors.inkMuted)),
        ),
        const SizedBox(width: 12),
        Expanded(
          flex: 3,
          child: Text(
            value,
            textAlign: TextAlign.end,
            style: TextStyle(fontWeight: emphasis ? FontWeight.w800 : FontWeight.w600, fontSize: emphasis ? 16 : 14),
          ),
        ),
      ],
    ),
  );
}

/// A gold hairline flourish used under hero headings.
class GoldRule extends StatelessWidget {
  const GoldRule({this.width = 44, super.key});

  final double width;

  @override
  Widget build(BuildContext context) => Container(
    width: width,
    height: 3,
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(2),
      gradient: const LinearGradient(colors: [NColors.goldLight, NColors.gold]),
    ),
  );
}

/// Call / WhatsApp / email buttons for a customer.
class ContactActions extends StatelessWidget {
  const ContactActions({this.phone, this.whatsappUrl, this.email, super.key});

  final String? phone;
  final String? whatsappUrl;
  final String? email;

  @override
  Widget build(BuildContext context) {
    final buttons = <Widget>[
      if (whatsappUrl != null)
        FilledButton.icon(
          style: FilledButton.styleFrom(
            backgroundColor: const Color(0xFF128C4B),
            minimumSize: const Size(0, 46),
            padding: const EdgeInsets.symmetric(horizontal: 12),
          ),
          onPressed: () => openExternal(context, whatsappUrl),
          icon: const Icon(Icons.chat_rounded, size: 18),
          label: const FittedBox(fit: BoxFit.scaleDown, child: Text('WhatsApp', maxLines: 1)),
        ),
      if (phone != null)
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(minimumSize: const Size(0, 46), padding: const EdgeInsets.symmetric(horizontal: 12)),
          onPressed: () => callPhone(context, phone),
          icon: const Icon(Icons.call_rounded, size: 18),
          label: FittedBox(fit: BoxFit.scaleDown, child: Text(context.t('contact.call'), maxLines: 1)),
        ),
      if (email != null && email!.isNotEmpty && phone == null)
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(minimumSize: const Size(0, 46), padding: const EdgeInsets.symmetric(horizontal: 12)),
          onPressed: () => openExternal(context, 'mailto:$email'),
          icon: const Icon(Icons.mail_outline_rounded, size: 18),
          label: FittedBox(fit: BoxFit.scaleDown, child: Text(context.t('contact.email'), maxLines: 1)),
        ),
    ];
    if (buttons.isEmpty) {
      return const SizedBox.shrink();
    }

    return Row(
      children: [
        for (var i = 0; i < buttons.length; i++) ...[if (i > 0) const SizedBox(width: 10), Expanded(child: buttons[i])],
      ],
    );
  }
}

/// A red dot with a count, for tabs and the bell.
class CountBadge extends StatelessWidget {
  const CountBadge({required this.count, required this.child, super.key});

  final int count;
  final Widget child;

  @override
  Widget build(BuildContext context) => Badge(
    isLabelVisible: count > 0,
    backgroundColor: NColors.wine,
    label: Text(count > 99 ? '99+' : '$count', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700)),
    child: child,
  );
}
