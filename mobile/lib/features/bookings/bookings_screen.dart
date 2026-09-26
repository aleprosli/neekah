import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api/neekah_api.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/booking.dart';
import '../../core/models/common.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/paged_list.dart';
import 'booking_tile.dart';

class BookingsScreen extends StatefulWidget {
  const BookingsScreen({super.key});

  @override
  State<BookingsScreen> createState() => _BookingsScreenState();
}

class _BookingsScreenState extends State<BookingsScreen> {
  late final PagedController<BookingSummary> _controller = PagedController(_fetch);
  final _search = TextEditingController();
  Timer? _debounce;
  String? _status;

  Future<Paginated<BookingSummary>> _fetch(int page) =>
      context.read<NeekahApi>().bookings(statuses: _status == null ? const [] : [_status!], search: _search.text.trim(), page: page);

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    _controller.dispose();
    super.dispose();
  }

  void _setStatus(String? status) {
    HapticFeedback.selectionClick();
    setState(() => _status = status);
    _controller.updateFetch(_fetch);
  }

  void _onSearch(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _controller.updateFetch(_fetch));
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final counts = _controller.counts;
    final total = counts.values.fold<int>(0, (sum, count) => sum + count);

    return Scaffold(
      appBar: AppBar(title: Text(context.t('bookings.title'))),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: TextField(
              controller: _search,
              onChanged: _onSearch,
              textInputAction: TextInputAction.search,
              decoration: InputDecoration(
                hintText: context.t('bookings.search'),
                prefixIcon: const Icon(Icons.search_rounded),
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
                suffixIcon: _search.text.isEmpty
                    ? null
                    : IconButton(
                        tooltip: context.t('common.clear'),
                        icon: const Icon(Icons.close_rounded),
                        onPressed: () {
                          _search.clear();
                          _onSearch('');
                        },
                      ),
              ),
            ),
          ),
          ListenableBuilder(
            listenable: _controller,
            builder: (context, _) => SizedBox(
              height: 48,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                children: [
                  _FilterChip(
                    label: context.t('bookings.all'),
                    count: counts.isEmpty ? null : total,
                    selected: _status == null,
                    onTap: () => _setStatus(null),
                  ),
                  for (final status in bookingStatuses)
                    _FilterChip(
                      label: context.t('status.$status'),
                      count: _controller.counts[status],
                      selected: _status == status,
                      onTap: () => _setStatus(status),
                    ),
                ],
              ),
            ),
          ),
          Expanded(
            child: PagedListView<BookingSummary>(
              controller: _controller,
              topics: const {Topic.bookings},
              itemBuilder: (context, booking) => BookingTile(booking: booking),
              empty: EmptyState(
                icon: Icons.event_note_rounded,
                title: context.t(_search.text.isEmpty ? 'bookings.empty_title' : 'bookings.no_results'),
                message: context.t(_search.text.isEmpty ? 'bookings.empty_body' : 'bookings.no_results_body'),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({required this.label, required this.selected, required this.onTap, this.count});

  final String label;
  final int? count;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(right: 8),
    child: ChoiceChip(
      selected: selected,
      showCheckmark: false,
      onSelected: (_) => onTap(),
      label: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(label),
          if (count != null) ...[
            const SizedBox(width: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
              decoration: BoxDecoration(
                color: selected ? Colors.white.withValues(alpha: 0.22) : NColors.lineSoft,
                borderRadius: BorderRadius.circular(NRadius.pill),
              ),
              child: Text(
                '$count',
                style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: selected ? Colors.white : NColors.inkMuted),
              ),
            ),
          ],
        ],
      ),
      labelStyle: TextStyle(color: selected ? Colors.white : NColors.ink, fontWeight: FontWeight.w600),
    ),
  );
}
