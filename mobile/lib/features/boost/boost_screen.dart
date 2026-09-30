import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/api/neekah_api.dart';
import '../../core/format.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/boost.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';
import '../../ui/widgets/remote_view.dart';

class BoostScreen extends StatelessWidget {
  const BoostScreen({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(context.t('boost.title'))),
    body: RemoteView<BoostData>(
      topics: const {Topic.boost},
      load: () => context.read<NeekahApi>().boost(),
      builder: (context, data, refresh) => BrandRefresh(
        onRefresh: refresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
          children: [
            _BalanceCard(data: data),
            if (data.running.isNotEmpty) ...[
              SectionTitle(context.t('boost.running')),
              for (final boost in data.running)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _RunningCard(boost: boost),
                ),
            ],
            SectionTitle(context.t('boost.new')),
            _BoostForm(data: data, onDone: refresh),
            if (data.history.isNotEmpty) ...[
              SectionTitle(context.t('boost.history')),
              NCard(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Column(
                  children: [
                    for (var i = 0; i < data.history.length; i++) ...[
                      if (i > 0) const Divider(indent: 16, endIndent: 16),
                      _HistoryRow(change: data.history[i]),
                    ],
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    ),
  );
}

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({required this.data});

  final BoostData data;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(24),
      gradient: const LinearGradient(colors: [Color(0xFF2A1E1F), Color(0xFF4A2F31)], begin: Alignment.topLeft, end: Alignment.bottomRight),
      boxShadow: softShadow,
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.rocket_launch_rounded, color: NColors.goldLight),
            const SizedBox(width: 8),
            Text(
              context.t('boost.balance'),
              style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontWeight: FontWeight.w600),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          crossAxisAlignment: CrossAxisAlignment.baseline,
          textBaseline: TextBaseline.alphabetic,
          children: [
            Text(
              '${data.balance}',
              style: const TextStyle(
                fontFamily: headingFont,
                fontFeatures: liningFigures,
                fontSize: 48,
                fontWeight: FontWeight.w700,
                color: NColors.goldLight,
                height: 1,
              ),
            ),
            const SizedBox(width: 8),
            Text(context.t('boost.tokens'), style: const TextStyle(color: Colors.white, fontSize: 16)),
          ],
        ),
        const SizedBox(height: 10),
        Text(context.t('boost.monthly', {'n': data.proMonthlyTokens}), style: TextStyle(color: Colors.white.withValues(alpha: 0.72), fontSize: 13)),
        Text(context.t('boost.rule', {'max': data.maxDays}), style: TextStyle(color: Colors.white.withValues(alpha: 0.72), fontSize: 13)),
        if (data.buyUrl != null) ...[
          const SizedBox(height: 14),
          OutlinedButton.icon(
            style: OutlinedButton.styleFrom(
              foregroundColor: NColors.goldLight,
              side: BorderSide(color: NColors.goldLight.withValues(alpha: 0.6)),
              minimumSize: const Size(0, 42),
            ),
            onPressed: () => openExternal(context, data.buyUrl),
            icon: const Icon(Icons.add_shopping_cart_rounded, size: 18),
            label: Text(context.t('boost.buy')),
          ),
        ],
      ],
    ),
  );
}

class _RunningCard extends StatefulWidget {
  const _RunningCard({required this.boost});

  final RunningBoost boost;

  @override
  State<_RunningCard> createState() => _RunningCardState();
}

class _RunningCardState extends State<_RunningCard> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) {
        setState(() {});
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final endsAt = widget.boost.endsAt;
    final left = endsAt == null ? Duration.zero : endsAt.difference(DateTime.now());
    final safe = left.isNegative ? Duration.zero : left;
    final parts = [
      (safe.inDays, context.t('boost.unit_days')),
      (safe.inHours % 24, context.t('boost.unit_hours')),
      (safe.inMinutes % 60, context.t('boost.unit_minutes')),
      (safe.inSeconds % 60, context.t('boost.unit_seconds')),
    ];

    return NCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const IconTile(Icons.local_fire_department_rounded, tone: 'amber', size: 40),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(widget.boost.category, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    Text(context.t('boost.ends', {'time': context.fmt.dateTime(endsAt)}), style: Theme.of(context).textTheme.bodySmall),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              for (var i = 0; i < parts.length; i++) ...[
                if (i > 0) const SizedBox(width: 8),
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    decoration: BoxDecoration(color: NColors.ivory, borderRadius: BorderRadius.circular(12)),
                    child: Column(
                      children: [
                        Text(
                          parts[i].$1.toString().padLeft(2, '0'),
                          style: const TextStyle(
                            fontFamily: headingFont,
                            fontFeatures: liningFigures,
                            fontSize: 22,
                            fontWeight: FontWeight.w700,
                            color: NColors.wine,
                          ),
                        ),
                        Text(parts[i].$2, style: const TextStyle(fontSize: 11, color: NColors.inkMuted)),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _BoostForm extends StatefulWidget {
  const _BoostForm({required this.data, required this.onDone});

  final BoostData data;
  final Future<void> Function() onDone;

  @override
  State<_BoostForm> createState() => _BoostFormState();
}

class _BoostFormState extends State<_BoostForm> {
  int? _categoryId;
  int _days = 1;
  bool _saving = false;
  String? _error;

  int get _maxDays => math.max(1, math.min(widget.data.maxDays, widget.data.balance));

  @override
  void initState() {
    super.initState();
    if (widget.data.categories.length == 1) {
      _categoryId = widget.data.categories.first.id;
    }
  }

  @override
  void didUpdateWidget(covariant _BoostForm oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_days > _maxDays) {
      _days = _maxDays;
    }
    if (_categoryId != null && !widget.data.categories.any((category) => category.id == _categoryId)) {
      _categoryId = null;
    }
  }

  Future<void> _submit() async {
    final category = widget.data.categories.firstWhere((item) => item.id == _categoryId);
    final api = context.read<NeekahApi>();
    final bus = context.read<RefreshBus>();
    final confirmed = await confirmAction(
      context,
      title: context.t('boost.confirm_title'),
      message: context.t('boost.confirm_body', {'category': category.name, 'days': _days, 'tokens': _days}),
      confirmLabel: context.t('boost.submit'),
    );
    if (!confirmed || !mounted) {
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final result = await api.startBoost(categoryId: category.id, days: _days);
      if (!mounted) {
        return;
      }
      HapticFeedback.mediumImpact();
      showMessage(context, result.message);
      bus.touch({Topic.dashboard});
      await widget.onDone();
    } on ValidationException catch (error) {
      setState(() => _error = error.errorFor('days') ?? error.errorFor('category_id') ?? describeError(context, error));
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
    final data = widget.data;
    final noTokens = data.balance <= 0;

    if (data.categories.isEmpty) {
      return NCard(
        child: Text(context.t('boost.no_categories'), style: const TextStyle(color: NColors.inkMuted)),
      );
    }

    return NCard(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          DropdownButtonFormField<int>(
            initialValue: _categoryId,
            isExpanded: true,
            decoration: InputDecoration(labelText: context.t('boost.category')),
            borderRadius: BorderRadius.circular(NRadius.small),
            items: [
              for (final category in data.categories)
                DropdownMenuItem(
                  value: category.id,
                  child: Text(
                    category.boostedUntil == null
                        ? category.name
                        : '${category.name} · ${context.t('boost.active_until', {'date': context.fmt.date(Fmt.klWallClock(category.boostedUntil!))})}',
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
            ],
            onChanged: (value) => setState(() => _categoryId = value),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(context.t('boost.days'), style: const TextStyle(fontWeight: FontWeight.w700)),
                    Text(context.t('boost.days_help', {'max': _maxDays}), style: Theme.of(context).textTheme.bodySmall),
                  ],
                ),
              ),
              IconButton.outlined(
                tooltip: '-1',
                onPressed: _days > 1 && !noTokens ? () => setState(() => _days--) : null,
                icon: const Icon(Icons.remove_rounded),
              ),
              SizedBox(
                width: 48,
                child: Text(
                  '$_days',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontSize: 26, fontWeight: FontWeight.w700),
                ),
              ),
              IconButton.outlined(
                tooltip: '+1',
                onPressed: _days < _maxDays && !noTokens
                    ? () {
                        HapticFeedback.selectionClick();
                        setState(() => _days++);
                      }
                    : null,
                icon: const Icon(Icons.add_rounded),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: NColors.goldSoft, borderRadius: BorderRadius.circular(NRadius.small)),
            child: Row(
              children: [
                const Icon(Icons.toll_rounded, color: NColors.gold, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    noTokens ? context.t('boost.no_tokens') : context.t('boost.cost', {'n': _days, 'left': data.balance - _days}),
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5),
                  ),
                ),
              ],
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 10),
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error, fontWeight: FontWeight.w600),
            ),
          ],
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _saving || noTokens || _categoryId == null ? null : _submit,
            icon: _saving
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.rocket_launch_rounded),
            label: Text(context.t('boost.submit')),
          ),
        ],
      ),
    );
  }
}

class _HistoryRow extends StatelessWidget {
  const _HistoryRow({required this.change});

  final TokenChange change;

  @override
  Widget build(BuildContext context) {
    final positive = change.change >= 0;
    final tone = ToneColors.of(positive ? 'emerald' : 'red');

    return ListTile(
      leading: IconTile(positive ? Icons.add_rounded : Icons.remove_rounded, tone: positive ? 'emerald' : 'red', size: 38),
      title: Text(change.reason, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
      subtitle: Text(
        [if (change.note != null) change.note!, if (change.date != null) context.fmt.date(Fmt.klWallClock(change.date!))].join(' · '),
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      ),
      trailing: Text(
        '${positive ? '+' : ''}${change.change}',
        style: TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontWeight: FontWeight.w700, fontSize: 18, color: tone.foreground),
      ),
    );
  }
}
