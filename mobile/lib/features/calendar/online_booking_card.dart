import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/api/neekah_api.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/dashboard.dart';
import '../../core/refresh_bus.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';

/// Online booking on/off, used on the dashboard and the calendar.
class OnlineBookingCard extends StatefulWidget {
  const OnlineBookingCard({required this.online, super.key});

  final OnlineBooking online;

  @override
  State<OnlineBookingCard> createState() => _OnlineBookingCardState();
}

class _OnlineBookingCardState extends State<OnlineBookingCard> {
  late OnlineBooking _online = widget.online;
  bool _saving = false;

  @override
  void didUpdateWidget(covariant OnlineBookingCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!_saving) {
      _online = widget.online;
    }
  }

  Future<void> _toggle(bool enabled) async {
    HapticFeedback.lightImpact();
    final api = context.read<NeekahApi>();
    final bus = context.read<RefreshBus>();
    final previous = _online;
    setState(() {
      _saving = true;
      _online = OnlineBooking(enabled: enabled, open: previous.open, label: previous.label, canEnable: previous.canEnable);
    });
    try {
      final result = await api.setOnlineBooking(enabled);
      if (!mounted) {
        return;
      }
      setState(() => _online = result.data);
      showMessage(context, result.message);
      bus.touch({Topic.dashboard, Topic.calendar});
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _online = previous);
      final message = error is ValidationException ? (error.errorFor('enabled') ?? describeError(context, error)) : describeError(context, error);
      showMessage(context, message, error: true);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final tone = !_online.enabled ? 'muted' : (_online.open ? 'emerald' : 'amber');

    return NCard(
      child: Row(
        children: [
          IconTile(_online.enabled ? Icons.bolt_rounded : Icons.bolt_outlined, tone: tone),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(context.t('online.title'), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                const SizedBox(height: 4),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    TonePill(
                      label: context.t(!_online.enabled ? 'online.off' : (_online.open ? 'online.open' : 'online.closed')),
                      tone: tone,
                      dense: true,
                    ),
                    if (_online.label.isNotEmpty) Text(_online.label, style: Theme.of(context).textTheme.bodySmall),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Switch(value: _online.enabled, onChanged: _saving ? null : _toggle),
        ],
      ),
    );
  }
}
