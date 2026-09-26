import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/api/neekah_api.dart';
import '../../core/l10n/l10n.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/feedback.dart';

/// Close one date or a range: from, optional to, reason and places.
class CloseDatesSheet extends StatefulWidget {
  const CloseDatesSheet({required this.initialDate, required this.capacity, super.key});

  final DateTime initialDate;
  final int capacity;

  @override
  State<CloseDatesSheet> createState() => _CloseDatesSheetState();
}

class _CloseDatesSheetState extends State<CloseDatesSheet> {
  late DateTime _from = _notPast(widget.initialDate);
  DateTime? _to;
  final _reason = TextEditingController();
  bool _wholeDay = true;
  late int _slots = 1;
  bool _saving = false;
  Map<String, String?> _errors = const {};

  static DateTime _notPast(DateTime date) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);

    return date.isBefore(today) ? today : date;
  }

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _pick({required bool from}) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: from ? _from : (_to ?? _from),
      firstDate: from ? DateTime(now.year, now.month, now.day) : _from,
      lastDate: DateTime(now.year + 3, 12, 31),
    );
    if (picked == null) {
      return;
    }
    setState(() {
      if (from) {
        _from = picked;
        if (_to != null && _to!.isBefore(picked)) {
          _to = null;
        }
      } else {
        _to = picked.isAtSameMomentAs(_from) ? null : picked;
      }
    });
  }

  Future<void> _save() async {
    final api = context.read<NeekahApi>();
    final bus = context.read<RefreshBus>();
    setState(() {
      _saving = true;
      _errors = const {};
    });
    try {
      final result = await api.addClosedDates(
        from: _from,
        to: _to,
        reason: _reason.text.trim(),
        slots: _wholeDay || widget.capacity <= 1 ? null : _slots,
      );
      if (!mounted) {
        return;
      }
      HapticFeedback.mediumImpact();
      showMessage(context, result.message);
      bus.touch({Topic.dashboard});
      Navigator.pop(context, true);
    } on ValidationException catch (error) {
      setState(() {
        _errors = {'from': error.errorFor('from'), 'to': error.errorFor('to'), 'reason': error.errorFor('reason'), 'slots': error.errorFor('slots')};
      });
      if (_errors.values.every((message) => message == null) && mounted) {
        showError(context, error);
      }
    } on ApiException catch (error) {
      if (mounted) {
        showError(context, error);
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(context.t('calendar.close_title'), style: theme.textTheme.headlineSmall),
            const SizedBox(height: 4),
            Text(context.t('calendar.close_body'), style: theme.textTheme.bodyMedium?.copyWith(color: NColors.inkMuted)),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: _DateField(
                    label: context.t('calendar.from'),
                    value: context.fmt.date(_from),
                    error: _errors['from'],
                    onTap: () => _pick(from: true),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _DateField(
                    label: context.t('calendar.to'),
                    value: _to == null ? context.t('calendar.to_optional') : context.fmt.date(_to),
                    error: _errors['to'],
                    onTap: () => _pick(from: false),
                    onClear: _to == null ? null : () => setState(() => _to = null),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _reason,
              maxLength: 120,
              textCapitalization: TextCapitalization.sentences,
              decoration: InputDecoration(
                labelText: context.t('calendar.reason'),
                hintText: context.t('calendar.reason_hint'),
                errorText: _errors['reason'],
              ),
            ),
            if (widget.capacity > 1) ...[
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                value: _wholeDay,
                onChanged: (value) => setState(() => _wholeDay = value),
                title: Text(context.t('calendar.whole_day')),
                subtitle: Text(context.t('calendar.capacity', {'n': widget.capacity})),
              ),
              if (!_wholeDay)
                Row(
                  children: [
                    Expanded(
                      child: Text(context.t('calendar.slots_label'), style: const TextStyle(fontWeight: FontWeight.w600)),
                    ),
                    IconButton.outlined(onPressed: _slots > 1 ? () => setState(() => _slots--) : null, icon: const Icon(Icons.remove_rounded)),
                    SizedBox(
                      width: 44,
                      child: Text(
                        '$_slots',
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontSize: 22, fontWeight: FontWeight.w700),
                      ),
                    ),
                    IconButton.outlined(
                      onPressed: _slots < widget.capacity ? () => setState(() => _slots++) : null,
                      icon: const Icon(Icons.add_rounded),
                    ),
                  ],
                ),
              if (_errors['slots'] != null) Text(_errors['slots']!, style: TextStyle(color: theme.colorScheme.error)),
            ],
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: _saving ? null : _save,
              icon: _saving
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.event_busy_rounded),
              label: Text(context.t('calendar.close_save')),
            ),
          ],
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  const _DateField({required this.label, required this.value, required this.onTap, this.error, this.onClear});

  final String label;
  final String value;
  final String? error;
  final VoidCallback onTap;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(NRadius.small),
    child: InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        errorText: error,
        errorMaxLines: 3,
        suffixIcon: onClear == null
            ? const Icon(Icons.calendar_today_rounded, size: 18)
            : IconButton(onPressed: onClear, icon: const Icon(Icons.close_rounded, size: 18)),
      ),
      child: Text(
        value,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(fontWeight: FontWeight.w600),
      ),
    ),
  );
}
