import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:table_calendar/table_calendar.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/api/neekah_api.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/calendar.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';
import '../../ui/widgets/remote_view.dart';
import '../../ui/widgets/skeleton.dart';
import 'close_dates_sheet.dart';
import 'online_booking_card.dart';

DateTime _day(DateTime date) => DateTime(date.year, date.month, date.day);

class CalendarScreen extends StatefulWidget {
  const CalendarScreen({super.key});

  @override
  State<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends State<CalendarScreen> {
  DateTime _focused = _day(DateTime.now());
  DateTime? _selected = _day(DateTime.now());
  CalendarMonth? _month;
  Object? _error;
  bool _loading = true;
  int _request = 0;
  RefreshBus? _bus;
  int _seenVersion = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final bus = Provider.of<RefreshBus>(context, listen: false);
    if (bus != _bus) {
      _bus?.removeListener(_onBus);
      _bus = bus..addListener(_onBus);
      _seenVersion = bus.version(Topic.calendar);
    }
  }

  @override
  void dispose() {
    _bus?.removeListener(_onBus);
    super.dispose();
  }

  void _onBus() {
    final version = _bus!.version(Topic.calendar);
    if (version != _seenVersion) {
      _seenVersion = version;
      _load();
    }
  }

  Future<void> _load() async {
    final request = ++_request;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final month = await context.read<NeekahApi>().calendar(_focused);
      if (mounted && request == _request) {
        setState(() {
          _month = month;
          _loading = false;
        });
      }
    } on ApiException catch (error) {
      if (mounted && request == _request) {
        setState(() {
          _error = error;
          _loading = false;
        });
        if (_month != null) {
          showError(context, error);
        }
      }
    }
  }

  Map<DateTime, List<BookedDay>> get _bookedByDay {
    final map = <DateTime, List<BookedDay>>{};
    for (final booked in _month?.booked ?? const <BookedDay>[]) {
      map.putIfAbsent(_day(booked.date), () => []).add(booked);
    }

    return map;
  }

  Map<DateTime, ClosedDay> get _closedByDay => {for (final closed in _month?.closed ?? const <ClosedDay>[]) _day(closed.date): closed};

  Future<void> _openCloseSheet() async {
    final month = _month;
    final changed = await showModalBottomSheet<bool>(
      context: context,
      useRootNavigator: true,
      isScrollControlled: true,
      builder: (context) => CloseDatesSheet(initialDate: _selected ?? _day(DateTime.now()), capacity: month?.capacity ?? 1),
    );
    if (changed == true) {
      _load();
    }
  }

  Future<void> _reopen(ClosedDay closed) async {
    final api = context.read<NeekahApi>();
    final confirmed = await confirmAction(
      context,
      title: context.t('calendar.reopen_title'),
      message: context.t('calendar.reopen_body', {'date': context.fmt.longDate(closed.date)}),
      confirmLabel: context.t('calendar.reopen'),
    );
    if (!confirmed || !mounted) {
      return;
    }
    try {
      final message = await api.reopenDate(closed.id);
      if (mounted) {
        showMessage(context, message);
        _load();
      }
    } on ApiException catch (error) {
      if (mounted) {
        showError(context, error);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final month = _month;

    return Scaffold(
      appBar: AppBar(
        title: Text(context.t('calendar.title')),
        actions: [
          IconButton(
            tooltip: context.t('calendar.today'),
            onPressed: () {
              final today = _day(DateTime.now());
              final monthChanged = today.month != _focused.month || today.year != _focused.year;
              setState(() {
                _focused = today;
                _selected = today;
              });
              if (monthChanged) {
                _load();
              }
            },
            icon: const Icon(Icons.today_rounded),
          ),
          const SizedBox(width: 4),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCloseSheet,
        backgroundColor: NColors.wine,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.event_busy_rounded),
        label: Text(context.t('calendar.close_dates')),
      ),
      body: month == null && _error != null
          ? ErrorState(error: _error!, onRetry: _load)
          : month == null
          ? const SkeletonList(header: true, count: 2)
          : BrandRefresh(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
                children: [
                  _buildCalendar(context),
                  const SizedBox(height: 12),
                  const _Legend(),
                  const SizedBox(height: 16),
                  if (_selected != null)
                    _DayPanel(
                      day: _selected!,
                      bookings: _bookedByDay[_selected!] ?? const [],
                      closed: _closedByDay[_selected!],
                      capacity: month.capacity,
                      onReopen: _reopen,
                    ),
                  const SizedBox(height: 16),
                  OnlineBookingCard(online: month.online),
                ],
              ),
            ),
    );
  }

  Widget _buildCalendar(BuildContext context) {
    final booked = _bookedByDay;
    final closed = _closedByDay;
    final now = DateTime.now();
    final code = L10n.of(context).code;

    Widget cell(DateTime day, {bool outside = false, bool selected = false, bool today = false}) {
      final key = _day(day);
      final bookings = booked[key] ?? const <BookedDay>[];
      final closedDay = closed[key];
      final tone = bookings.isNotEmpty ? ToneColors.of(bookings.first.status.tone) : null;

      Color background = Colors.transparent;
      Color foreground = outside ? NColors.line : NColors.ink;
      if (closedDay != null && !outside) {
        background = const Color(0xFFEDE7E1);
        foreground = NColors.inkMuted;
      }
      if (tone != null && !outside) {
        background = tone.background;
        foreground = tone.foreground;
      }
      if (selected) {
        background = NColors.wine;
        foreground = Colors.white;
      }

      return AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        constraints: const BoxConstraints.expand(),
        margin: const EdgeInsets.all(3),
        decoration: BoxDecoration(
          color: background,
          borderRadius: BorderRadius.circular(12),
          border: today && !selected ? Border.all(color: NColors.gold, width: 1.6) : null,
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            Text(
              '${day.day}',
              style: TextStyle(
                fontWeight: bookings.isNotEmpty || today ? FontWeight.w800 : FontWeight.w500,
                color: foreground,
                decoration: closedDay != null && !outside && bookings.isEmpty ? TextDecoration.lineThrough : null,
                decorationColor: foreground,
              ),
            ),
            if (!outside && (bookings.isNotEmpty || closedDay != null))
              Positioned(
                bottom: 5,
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    for (final booking in bookings.take(3))
                      Container(
                        width: 5,
                        height: 5,
                        margin: const EdgeInsets.symmetric(horizontal: 1),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: selected ? Colors.white : ToneColors.of(booking.status.tone).foreground,
                        ),
                      ),
                    if (bookings.isEmpty && closedDay != null) Icon(Icons.block_rounded, size: 8, color: selected ? Colors.white : NColors.inkMuted),
                  ],
                ),
              ),
          ],
        ),
      );
    }

    return NCard(
      padding: const EdgeInsets.fromLTRB(8, 4, 8, 10),
      child: Stack(
        children: [
          TableCalendar<BookedDay>(
            locale: code,
            firstDay: DateTime(now.year - 2, 1, 1),
            lastDay: DateTime(now.year + 3, 12, 31),
            focusedDay: _focused,
            startingDayOfWeek: StartingDayOfWeek.monday,
            availableCalendarFormats: const {CalendarFormat.month: ''},
            rowHeight: 48,
            daysOfWeekHeight: 26,
            selectedDayPredicate: (day) => _selected != null && isSameDay(day, _selected),
            eventLoader: (day) => booked[_day(day)] ?? const [],
            onDaySelected: (selected, focused) {
              HapticFeedback.selectionClick();
              setState(() {
                _selected = _day(selected);
                _focused = _day(focused);
              });
            },
            onPageChanged: (focused) {
              _focused = _day(focused);
              _selected = null;
              _load();
            },
            headerStyle: HeaderStyle(
              formatButtonVisible: false,
              titleCentered: true,
              titleTextFormatter: (date, _) => context.fmt.monthYear(date),
              titleTextStyle: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontSize: 19, fontWeight: FontWeight.w700),
              leftChevronIcon: const Icon(Icons.chevron_left_rounded, color: NColors.wine),
              rightChevronIcon: const Icon(Icons.chevron_right_rounded, color: NColors.wine),
            ),
            daysOfWeekStyle: const DaysOfWeekStyle(
              weekdayStyle: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: NColors.inkMuted),
              weekendStyle: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: NColors.wine),
            ),
            calendarBuilders: CalendarBuilders<BookedDay>(
              defaultBuilder: (context, day, _) => cell(day),
              outsideBuilder: (context, day, _) => cell(day, outside: true),
              todayBuilder: (context, day, _) => cell(day, today: true, selected: _selected != null && isSameDay(day, _selected)),
              selectedBuilder: (context, day, _) => cell(day, selected: true, today: isSameDay(day, now)),
              markerBuilder: (context, day, events) => const SizedBox.shrink(),
            ),
          ),
          if (_loading) const Positioned(top: 0, left: 24, right: 24, child: LinearProgressIndicator(minHeight: 2)),
        ],
      ),
    );
  }
}

class _Legend extends StatelessWidget {
  const _Legend();

  @override
  Widget build(BuildContext context) {
    Widget item(Color color, String label, {bool strike = false}) => Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(4)),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: NColors.inkMuted, fontWeight: FontWeight.w600),
        ),
      ],
    );

    return Wrap(
      spacing: 14,
      runSpacing: 8,
      alignment: WrapAlignment.center,
      children: [
        item(ToneColors.of('emerald').foreground, context.t('status.confirmed')),
        item(ToneColors.of('amber').foreground, context.t('status.pending_payment')),
        item(ToneColors.of('sky').foreground, context.t('status.completed')),
        item(const Color(0xFFCFC6BE), context.t('calendar.closed')),
      ],
    );
  }
}

class _DayPanel extends StatelessWidget {
  const _DayPanel({required this.day, required this.bookings, required this.closed, required this.capacity, required this.onReopen});

  final DateTime day;
  final List<BookedDay> bookings;
  final ClosedDay? closed;
  final int capacity;
  final ValueChanged<ClosedDay> onReopen;

  @override
  Widget build(BuildContext context) {
    final free = bookings.isEmpty && closed == null;

    return NCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text(context.fmt.longDate(day), style: Theme.of(context).textTheme.titleMedium)),
              if (free) TonePill(label: context.t('calendar.free'), tone: 'emerald', dense: true),
              if (capacity > 1 && bookings.isNotEmpty)
                TonePill(label: context.t('calendar.places', {'used': bookings.length, 'capacity': capacity}), tone: 'muted', dense: true),
            ],
          ),
          if (free) ...[const SizedBox(height: 6), Text(context.t('calendar.free_body'), style: Theme.of(context).textTheme.bodySmall)],
          for (final booking in bookings) ...[
            const SizedBox(height: 10),
            Material(
              color: ToneColors.of(booking.status.tone).background.withValues(alpha: 0.6),
              borderRadius: BorderRadius.circular(NRadius.small),
              child: InkWell(
                borderRadius: BorderRadius.circular(NRadius.small),
                onTap: () => context.push('/booking/${Uri.encodeComponent(booking.reference)}'),
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(booking.customerName, style: const TextStyle(fontWeight: FontWeight.w700)),
                            Text(booking.packageName, maxLines: 1, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.bodySmall),
                            const SizedBox(height: 6),
                            TonePill.status(booking.status, dense: true),
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right_rounded, color: NColors.inkMuted),
                    ],
                  ),
                ),
              ),
            ),
          ],
          if (closed != null) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: const Color(0xFFF3EEE9), borderRadius: BorderRadius.circular(NRadius.small)),
              child: Row(
                children: [
                  Icon(closed!.imported ? Icons.sync_rounded : Icons.event_busy_rounded, color: NColors.inkMuted),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          closed!.slots == null ? context.t('calendar.closed_all_day') : context.t('calendar.closed_slots', {'n': closed!.slots}),
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        if (closed!.reason != null) Text(closed!.reason!, style: Theme.of(context).textTheme.bodySmall),
                        if (closed!.imported) Text(context.t('calendar.imported'), style: Theme.of(context).textTheme.bodySmall),
                      ],
                    ),
                  ),
                  if (closed!.canReopen) TextButton(onPressed: () => onReopen(closed!), child: Text(context.t('calendar.reopen'))),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
