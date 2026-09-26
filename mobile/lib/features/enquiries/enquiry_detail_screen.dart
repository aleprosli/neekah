import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/api/neekah_api.dart';
import '../../core/format.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/enquiry.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';
import '../../ui/widgets/remote_view.dart';

class EnquiryDetailScreen extends StatefulWidget {
  const EnquiryDetailScreen({required this.id, super.key});

  final String id;

  @override
  State<EnquiryDetailScreen> createState() => _EnquiryDetailScreenState();
}

class _EnquiryDetailScreenState extends State<EnquiryDetailScreen> {
  final _view = GlobalKey<RemoteViewState<EnquiryDetail>>();
  final _reply = TextEditingController();
  bool _sending = false;
  bool _composing = false;
  String? _replyError;

  @override
  void dispose() {
    _reply.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final text = _reply.text.trim();
    if (text.isEmpty) {
      setState(() => _replyError = context.t('enquiry.reply_required'));

      return;
    }
    final api = context.read<NeekahApi>();
    final bus = context.read<RefreshBus>();
    FocusScope.of(context).unfocus();
    setState(() {
      _sending = true;
      _replyError = null;
    });
    try {
      final result = await api.replyToEnquiry(widget.id, text);
      if (!mounted) {
        return;
      }
      HapticFeedback.mediumImpact();
      _reply.clear();
      _view.currentState?.replace(result.data);
      setState(() => _composing = false);
      showMessage(context, result.message);
      bus.touch({Topic.enquiries, Topic.dashboard});
    } on ValidationException catch (error) {
      setState(() => _replyError = error.errorFor('reply') ?? describeError(context, error));
    } on ApiException catch (error) {
      if (mounted) {
        showError(context, error);
      }
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(context.t('enquiry.title'))),
    body: RemoteView<EnquiryDetail>(
      key: _view,
      load: () => context.read<NeekahApi>().enquiry(widget.id),
      builder: (context, enquiry, refresh) {
        final showComposer = enquiry.reply == null || _composing;

        return Column(
          children: [
            Expanded(
              child: BrandRefresh(
                onRefresh: refresh,
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                  children: [
                    _CustomerCard(enquiry: enquiry),
                    if (enquiry.wedding != null) ...[const SizedBox(height: 16), _WeddingCard(wedding: enquiry.wedding!)],
                    SectionTitle(context.t('enquiry.conversation')),
                    _Bubble(text: enquiry.message, time: context.ago(enquiry.summary.createdAt), fromCouple: true, name: enquiry.customer.name),
                    if (enquiry.reply != null) ...[
                      const SizedBox(height: 12),
                      _Bubble(text: enquiry.reply!, time: context.ago(enquiry.summary.repliedAt), fromCouple: false, name: context.t('enquiry.you')),
                      if (!_composing)
                        Align(
                          alignment: Alignment.centerRight,
                          child: TextButton.icon(
                            onPressed: () => setState(() => _composing = true),
                            icon: const Icon(Icons.reply_rounded, size: 18),
                            label: Text(context.t('enquiry.reply_again')),
                          ),
                        ),
                    ],
                  ],
                ),
              ),
            ),
            if (showComposer) _Composer(controller: _reply, sending: _sending, error: _replyError, onSend: _send),
          ],
        );
      },
    ),
  );
}

class _CustomerCard extends StatelessWidget {
  const _CustomerCard({required this.enquiry});

  final EnquiryDetail enquiry;

  @override
  Widget build(BuildContext context) {
    final summary = enquiry.summary;
    final name = enquiry.customer.name.isEmpty ? summary.customerName : enquiry.customer.name;

    return NCard(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 26,
                backgroundColor: NColors.wineLight,
                child: Text(
                  name.isEmpty ? '?' : name.substring(0, 1).toUpperCase(),
                  style: const TextStyle(
                    fontFamily: headingFont,
                    fontFeatures: liningFigures,
                    fontSize: 22,
                    fontWeight: FontWeight.w700,
                    color: NColors.wine,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: 4),
                    TonePill.status(summary.status, dense: true),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (summary.eventDate != null)
            InfoRow(icon: Icons.event_rounded, label: context.t('enquiry.event_date'), value: context.fmt.longDate(summary.eventDate!)),
          if (summary.packageName != null)
            InfoRow(icon: Icons.inventory_2_outlined, label: context.t('enquiry.package'), value: summary.packageName!),
          InfoRow(icon: Icons.schedule_rounded, label: context.t('enquiry.received'), value: context.fmt.dateTime(summary.createdAt)),
          if (enquiry.customer.email.isNotEmpty)
            InfoRow(icon: Icons.mail_outline_rounded, label: context.t('booking.email'), value: enquiry.customer.email),
          if (enquiry.customer.phone != null) InfoRow(icon: Icons.phone_outlined, label: context.t('booking.phone'), value: enquiry.customer.phone!),
          const SizedBox(height: 12),
          ContactActions(phone: enquiry.customer.phone, whatsappUrl: enquiry.customer.whatsappUrl, email: enquiry.customer.email),
        ],
      ),
    );
  }
}

class _WeddingCard extends StatelessWidget {
  const _WeddingCard({required this.wedding});

  final WeddingInfo wedding;

  @override
  Widget build(BuildContext context) {
    final place = [wedding.city, wedding.state].whereType<String>().join(', ');

    return NCard(
      color: NColors.goldSoft,
      border: Border.all(color: NColors.goldLight.withValues(alpha: 0.6)),
      child: Row(
        children: [
          const Icon(Icons.favorite_rounded, color: NColors.gold),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  wedding.title,
                  style: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontSize: 17, fontWeight: FontWeight.w700),
                ),
                if (place.isNotEmpty) Text(place, style: Theme.of(context).textTheme.bodySmall),
                if (wedding.budget != null)
                  Text(
                    context.t('enquiry.budget', {'amount': Fmt.money(wedding.budget!)}),
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.text, required this.time, required this.fromCouple, required this.name});

  final String text;
  final String time;
  final bool fromCouple;
  final String name;

  @override
  Widget build(BuildContext context) => Align(
    alignment: fromCouple ? Alignment.centerLeft : Alignment.centerRight,
    child: ConstrainedBox(
      constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.82),
      child: Container(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 10),
        decoration: BoxDecoration(
          color: fromCouple ? Colors.white : NColors.wine,
          boxShadow: softShadow,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(20),
            topRight: const Radius.circular(20),
            bottomLeft: Radius.circular(fromCouple ? 6 : 20),
            bottomRight: Radius.circular(fromCouple ? 20 : 6),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              name,
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: fromCouple ? NColors.wine : NColors.goldLight),
            ),
            const SizedBox(height: 4),
            SelectableText(text, style: TextStyle(color: fromCouple ? NColors.ink : Colors.white, height: 1.45)),
            const SizedBox(height: 6),
            Text(time, style: TextStyle(fontSize: 11, color: fromCouple ? NColors.inkMuted : Colors.white70)),
          ],
        ),
      ),
    ),
  );
}

class _Composer extends StatelessWidget {
  const _Composer({required this.controller, required this.sending, required this.onSend, this.error});

  final TextEditingController controller;
  final bool sending;
  final String? error;
  final VoidCallback onSend;

  @override
  Widget build(BuildContext context) => Container(
    decoration: const BoxDecoration(
      color: Colors.white,
      border: Border(top: BorderSide(color: NColors.lineSoft)),
    ),
    child: SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              child: TextField(
                key: const Key('enquiry.reply'),
                controller: controller,
                minLines: 1,
                maxLines: 5,
                textCapitalization: TextCapitalization.sentences,
                decoration: InputDecoration(
                  hintText: context.t('enquiry.reply_hint'),
                  errorText: error,
                  errorMaxLines: 3,
                  fillColor: NColors.ivory,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide.none),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide.none),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: const BorderSide(color: NColors.wine),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            IconButton.filled(
              tooltip: context.t('enquiry.send'),
              style: IconButton.styleFrom(backgroundColor: NColors.wine, minimumSize: const Size(48, 48)),
              onPressed: sending ? null : onSend,
              icon: sending
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.send_rounded, color: Colors.white),
            ),
          ],
        ),
      ),
    ),
  );
}
