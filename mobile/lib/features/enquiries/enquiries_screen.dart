import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api/neekah_api.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/enquiry.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/paged_list.dart';

class EnquiriesScreen extends StatefulWidget {
  const EnquiriesScreen({super.key});

  @override
  State<EnquiriesScreen> createState() => _EnquiriesScreenState();
}

class _EnquiriesScreenState extends State<EnquiriesScreen> {
  late final _open = PagedController<EnquirySummary>((page) => context.read<NeekahApi>().enquiries(status: 'open', page: page));
  late final _replied = PagedController<EnquirySummary>((page) => context.read<NeekahApi>().enquiries(status: 'replied', page: page));

  @override
  void dispose() {
    _open.dispose();
    _replied.dispose();
    super.dispose();
  }

  int? _count(String status) => _open.counts[status] ?? _replied.counts[status];

  @override
  Widget build(BuildContext context) => DefaultTabController(
    length: 2,
    child: Scaffold(
      appBar: AppBar(
        title: Text(context.t('enquiries.title')),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: ListenableBuilder(
            listenable: Listenable.merge([_open, _replied]),
            builder: (context, _) => TabBar(
              tabs: [
                Tab(
                  child: _TabLabel(label: context.t('enquiries.open'), count: _count('open')),
                ),
                Tab(
                  child: _TabLabel(label: context.t('enquiries.replied'), count: _count('replied')),
                ),
              ],
            ),
          ),
        ),
      ),
      body: TabBarView(
        children: [
          _EnquiryList(controller: _open, emptyKey: 'enquiries.empty_open'),
          _EnquiryList(controller: _replied, emptyKey: 'enquiries.empty_replied'),
        ],
      ),
    ),
  );
}

class _TabLabel extends StatelessWidget {
  const _TabLabel({required this.label, this.count});

  final String label;
  final int? count;

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Flexible(child: Text(label, overflow: TextOverflow.ellipsis)),
      if (count != null) ...[
        const SizedBox(width: 6),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1),
          decoration: BoxDecoration(color: NColors.wineLight, borderRadius: BorderRadius.circular(NRadius.pill)),
          child: Text(
            '$count',
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: NColors.wine),
          ),
        ),
      ],
    ],
  );
}

class _EnquiryList extends StatefulWidget {
  const _EnquiryList({required this.controller, required this.emptyKey});

  final PagedController<EnquirySummary> controller;
  final String emptyKey;

  @override
  State<_EnquiryList> createState() => _EnquiryListState();
}

class _EnquiryListState extends State<_EnquiryList> with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  @override
  Widget build(BuildContext context) {
    super.build(context);

    return PagedListView<EnquirySummary>(
      controller: widget.controller,
      topics: const {Topic.enquiries},
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
      itemBuilder: (context, enquiry) => EnquiryTile(enquiry: enquiry),
      empty: EmptyState(icon: Icons.forum_outlined, title: context.t('${widget.emptyKey}_title'), message: context.t('${widget.emptyKey}_body')),
    );
  }
}

class EnquiryTile extends StatelessWidget {
  const EnquiryTile({required this.enquiry, super.key});

  final EnquirySummary enquiry;

  @override
  Widget build(BuildContext context) {
    final unanswered = enquiry.repliedAt == null;
    final initial = enquiry.customerName.isEmpty ? '?' : enquiry.customerName.substring(0, 1).toUpperCase();

    return NCard(
      padding: const EdgeInsets.all(14),
      onTap: () => context.push('/enquiry/${Uri.encodeComponent(enquiry.id)}'),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: unanswered ? NColors.wineLight : NColors.lineSoft,
            child: Text(
              initial,
              style: TextStyle(
                fontFamily: headingFont,
                fontFeatures: liningFigures,
                fontWeight: FontWeight.w700,
                fontSize: 18,
                color: unanswered ? NColors.wine : NColors.inkMuted,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        enquiry.customerName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(fontWeight: unanswered ? FontWeight.w800 : FontWeight.w600, fontSize: 15),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(context.ago(enquiry.createdAt), style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 11.5)),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  enquiry.preview,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: NColors.inkMuted, fontSize: 13.5, height: 1.35),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    TonePill.status(enquiry.status, dense: true),
                    if (enquiry.eventDate != null)
                      TonePill(label: context.fmt.date(enquiry.eventDate), tone: 'muted', icon: Icons.event_rounded, dense: true),
                    if (enquiry.packageName != null)
                      TonePill(label: enquiry.packageName!, tone: 'muted', icon: Icons.inventory_2_outlined, dense: true),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
