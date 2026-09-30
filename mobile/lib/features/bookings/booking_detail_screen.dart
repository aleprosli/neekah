import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/api/neekah_api.dart';
import '../../core/format.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/booking.dart';
import '../../core/models/common.dart';
import '../../core/refresh_bus.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';
import '../../ui/widgets/remote_view.dart';
import 'booking_tile.dart';

class BookingDetailScreen extends StatefulWidget {
  const BookingDetailScreen({required this.reference, super.key});

  final String reference;

  @override
  State<BookingDetailScreen> createState() => _BookingDetailScreenState();
}

class _BookingDetailScreenState extends State<BookingDetailScreen> {
  final _view = GlobalKey<RemoteViewState<BookingDetail>>();
  bool _busy = false;

  Future<void> _run(Future<ActionResult<BookingDetail>> Function(NeekahApi api) action) async {
    final api = context.read<NeekahApi>();
    final bus = context.read<RefreshBus>();
    setState(() => _busy = true);
    try {
      final result = await action(api);
      if (!mounted) {
        return;
      }
      HapticFeedback.mediumImpact();
      _view.currentState?.replace(result.data);
      showMessage(context, result.message);
      bus.touch({Topic.bookings, Topic.dashboard, Topic.calendar});
    } on ValidationException catch (error) {
      if (mounted) {
        showMessage(context, error.errorFor('booking') ?? describeError(context, error), error: true);
      }
    } on ApiException catch (error) {
      if (mounted) {
        showError(context, error);
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Future<void> _paymentAction(BookingDetail booking, Payment payment, String action) async {
    final amount = Fmt.money(payment.amount);
    final confirmed = await confirmAction(
      context,
      title: context.t('payment.$action.title'),
      message: context.t('payment.$action.body', {'amount': amount}),
      confirmLabel: context.t('payment.$action.confirm'),
      destructive: action != 'verify',
    );
    if (!confirmed) {
      return;
    }
    final reference = booking.summary.reference;
    await _run(
      (api) => switch (action) {
        'verify' => api.verifyPayment(reference, payment.reference),
        'reject' => api.rejectPayment(reference, payment.reference),
        _ => api.markPaymentRefunded(reference, payment.reference),
      },
    );
  }

  Future<void> _complete(BookingDetail booking) async {
    final confirmed = await confirmAction(
      context,
      title: context.t('booking.complete_title'),
      message: context.t('booking.complete_body'),
      confirmLabel: context.t('booking.complete'),
    );
    if (confirmed) {
      await _run((api) => api.completeBooking(booking.summary.reference));
    }
  }

  Future<void> _cancel(BookingDetail booking) async {
    final reason = await showDialog<String>(context: context, builder: (context) => const _CancelDialog());
    if (reason != null) {
      await _run((api) => api.cancelBooking(booking.summary.reference, reason));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(widget.reference)),
    body: RemoteView<BookingDetail>(
      key: _view,
      load: () => context.read<NeekahApi>().booking(widget.reference),
      builder: (context, booking, refresh) => Stack(
        children: [
          BrandRefresh(
            onRefresh: refresh,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 120),
              children: [
                _SummaryCard(booking: booking),
                const SizedBox(height: 16),
                _MoneyCard(booking: booking),
                SectionTitle(context.t('booking.payments')),
                if (booking.payments.isEmpty)
                  NCard(
                    child: Text(context.t('booking.no_payments'), style: const TextStyle(color: NColors.inkMuted)),
                  )
                else
                  for (final payment in booking.payments)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _PaymentCard(payment: payment, busy: _busy, onAction: (action) => _paymentAction(booking, payment, action)),
                    ),
                if (booking.timeline.isNotEmpty) ...[SectionTitle(context.t('booking.timeline')), _TimelineCard(items: booking.timeline)],
                if (booking.notes != null) ...[SectionTitle(context.t('booking.notes')), NCard(child: Text(booking.notes!))],
                if (booking.review != null) ...[SectionTitle(context.t('booking.review')), _ReviewCard(review: booking.review!)],
              ],
            ),
          ),
          if (booking.canComplete || booking.canCancel)
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: _ActionBar(booking: booking, busy: _busy, onComplete: _complete, onCancel: _cancel),
            ),
          if (_busy) const Positioned(top: 0, left: 0, right: 0, child: LinearProgressIndicator(minHeight: 3)),
        ],
      ),
    ),
  );
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.booking});

  final BookingDetail booking;

  @override
  Widget build(BuildContext context) {
    final summary = booking.summary;
    final theme = Theme.of(context);

    return NCard(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              DateBlock(date: summary.eventDate, tone: summary.status.tone, size: 60),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(summary.customerName, style: theme.textTheme.titleLarge),
                    const SizedBox(height: 2),
                    Text(summary.packageName, style: theme.textTheme.bodyMedium?.copyWith(color: NColors.inkMuted)),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: [
                        TonePill.status(summary.status),
                        if (summary.isOnline) TonePill(label: context.t('booking.online'), tone: 'brand', icon: Icons.bolt_rounded),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          InfoRow(
            icon: Icons.event_rounded,
            label: context.t('booking.event_date'),
            value: summary.eventDate == null ? '—' : context.fmt.longDate(summary.eventDate!),
          ),
          if (booking.createdAt != null)
            InfoRow(icon: Icons.schedule_rounded, label: context.t('booking.created_at'), value: context.fmt.dateTime(booking.createdAt)),
          InfoRow(icon: Icons.mail_outline_rounded, label: context.t('booking.email'), value: booking.customer.email),
          if (booking.customer.phone != null) InfoRow(icon: Icons.phone_outlined, label: context.t('booking.phone'), value: booking.customer.phone!),
          const SizedBox(height: 12),
          ContactActions(phone: booking.customer.phone, whatsappUrl: booking.customer.whatsappUrl, email: booking.customer.email),
        ],
      ),
    );
  }
}

class _MoneyCard extends StatelessWidget {
  const _MoneyCard({required this.booking});

  final BookingDetail booking;

  @override
  Widget build(BuildContext context) {
    final summary = booking.summary;

    return NCard(
      padding: const EdgeInsets.all(18),
      child: Column(
        children: [
          InfoRow(label: context.t('booking.total'), value: Fmt.money(summary.total)),
          if (booking.depositAmount != null) InfoRow(label: context.t('booking.deposit'), value: Fmt.money(booking.depositAmount!)),
          InfoRow(label: context.t('booking.paid'), value: Fmt.money(summary.paid)),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: summary.paidRatio,
              minHeight: 8,
              color: summary.paidRatio >= 1 ? const Color(0xFF047857) : NColors.gold,
            ),
          ),
          const SizedBox(height: 10),
          InfoRow(label: context.t('booking.outstanding'), value: Fmt.money(booking.outstanding), emphasis: true),
          if (booking.holdExpiresAt != null) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: ToneColors.of('amber').background, borderRadius: BorderRadius.circular(NRadius.small)),
              child: Row(
                children: [
                  Icon(Icons.timer_outlined, color: ToneColors.of('amber').foreground, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      context.t('booking.hold_expires', {'time': context.fmt.dateTime(booking.holdExpiresAt)}),
                      style: TextStyle(color: ToneColors.of('amber').foreground, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _PaymentCard extends StatelessWidget {
  const _PaymentCard({required this.payment, required this.busy, required this.onAction});

  final Payment payment;
  final bool busy;
  final ValueChanged<String> onAction;

  @override
  Widget build(BuildContext context) {
    final details = [
      payment.methodLabel,
      if (payment.paidAt != null) context.fmt.dateTime(payment.paidAt) else if (payment.paidOn != null) context.fmt.date(payment.paidOn),
    ].where((part) => part.isNotEmpty).join(' · ');

    return NCard(
      border: payment.canVerify ? Border.all(color: ToneColors.of('amber').foreground.withValues(alpha: 0.35)) : null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  Fmt.money(payment.amount),
                  style: const TextStyle(fontFamily: headingFont, fontFeatures: liningFigures, fontSize: 21, fontWeight: FontWeight.w700),
                ),
              ),
              const SizedBox(width: 8),
              ConstrainedBox(constraints: const BoxConstraints(maxWidth: 170), child: TonePill.status(payment.status)),
            ],
          ),
          const SizedBox(height: 4),
          Text(details, style: Theme.of(context).textTheme.bodySmall),
          Text(payment.reference, style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 11.5, letterSpacing: 0.3)),
          if (payment.note != null) ...[
            const SizedBox(height: 8),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: NColors.ivory, borderRadius: BorderRadius.circular(12)),
              child: Text('“${payment.note!}”', style: const TextStyle(fontStyle: FontStyle.italic)),
            ),
          ],
          if (payment.recordedBy != null) ...[
            const SizedBox(height: 6),
            Text(context.t('payment.recorded_by', {'name': payment.recordedBy}), style: Theme.of(context).textTheme.bodySmall),
          ],
          if (payment.slipUrl != null || payment.receiptUrl != null)
            Wrap(
              spacing: 4,
              children: [
                if (payment.slipUrl != null)
                  TextButton.icon(
                    onPressed: () => openExternal(context, payment.slipUrl),
                    icon: const Icon(Icons.image_outlined, size: 18),
                    label: Text(context.t('payment.slip')),
                  ),
                if (payment.receiptUrl != null)
                  TextButton.icon(
                    onPressed: () => openExternal(context, payment.receiptUrl),
                    icon: const Icon(Icons.receipt_long_outlined, size: 18),
                    label: Text(context.t('payment.receipt')),
                  ),
              ],
            ),
          if (payment.hasActions) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                if (payment.canVerify)
                  Expanded(
                    child: FilledButton.icon(
                      style: FilledButton.styleFrom(backgroundColor: const Color(0xFF047857), minimumSize: const Size(0, 44)),
                      onPressed: busy ? null : () => onAction('verify'),
                      icon: const Icon(Icons.verified_rounded, size: 18),
                      label: Text(context.t('payment.verify.action')),
                    ),
                  ),
                if (payment.canVerify && payment.canReject) const SizedBox(width: 10),
                if (payment.canReject)
                  Expanded(
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFFB91C1C), minimumSize: const Size(0, 44)),
                      onPressed: busy ? null : () => onAction('reject'),
                      icon: const Icon(Icons.block_rounded, size: 18),
                      label: Text(context.t('payment.reject.action')),
                    ),
                  ),
                if (payment.canMarkRefunded && !payment.canVerify && !payment.canReject)
                  Expanded(
                    child: OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(minimumSize: const Size(0, 44)),
                      onPressed: busy ? null : () => onAction('refunded'),
                      icon: const Icon(Icons.undo_rounded, size: 18),
                      label: Text(context.t('payment.refunded.action')),
                    ),
                  ),
              ],
            ),
            if (payment.canMarkRefunded && (payment.canVerify || payment.canReject))
              TextButton.icon(
                onPressed: busy ? null : () => onAction('refunded'),
                icon: const Icon(Icons.undo_rounded, size: 18),
                label: Text(context.t('payment.refunded.action')),
              ),
          ],
        ],
      ),
    );
  }
}

class _TimelineCard extends StatelessWidget {
  const _TimelineCard({required this.items});

  final List<TimelineItem> items;

  @override
  Widget build(BuildContext context) => NCard(
    child: Column(
      children: [
        for (var i = 0; i < items.length; i++)
          IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                SizedBox(
                  width: 56,
                  child: Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      items[i].time ?? '',
                      style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5, color: NColors.wine),
                    ),
                  ),
                ),
                Column(
                  children: [
                    Container(
                      width: 12,
                      height: 12,
                      margin: const EdgeInsets.only(top: 4),
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(color: NColors.gold, width: 2.5),
                        color: Colors.white,
                      ),
                    ),
                    if (i < items.length - 1) Expanded(child: Container(width: 2, color: NColors.lineSoft)),
                  ],
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Padding(
                    padding: EdgeInsets.only(bottom: i < items.length - 1 ? 18 : 0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(items[i].title, style: const TextStyle(fontWeight: FontWeight.w700)),
                        if (items[i].location != null)
                          Padding(
                            padding: const EdgeInsets.only(top: 2),
                            child: Row(
                              children: [
                                const Icon(Icons.place_outlined, size: 14, color: NColors.inkMuted),
                                const SizedBox(width: 4),
                                Expanded(child: Text(items[i].location!, style: Theme.of(context).textTheme.bodySmall)),
                              ],
                            ),
                          ),
                        if (items[i].notes != null)
                          Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(items[i].notes!, style: Theme.of(context).textTheme.bodySmall),
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
      ],
    ),
  );
}

class _ReviewCard extends StatelessWidget {
  const _ReviewCard({required this.review});

  final Review review;

  @override
  Widget build(BuildContext context) => NCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            for (var i = 1; i <= 5; i++) Icon(i <= review.rating ? Icons.star_rounded : Icons.star_outline_rounded, color: NColors.gold, size: 24),
            const SizedBox(width: 8),
            Text('${review.rating}/5', style: const TextStyle(fontWeight: FontWeight.w700)),
          ],
        ),
        if (review.comment != null) ...[const SizedBox(height: 8), Text('“${review.comment!}”')],
      ],
    ),
  );
}

class _ActionBar extends StatelessWidget {
  const _ActionBar({required this.booking, required this.busy, required this.onComplete, required this.onCancel});

  final BookingDetail booking;
  final bool busy;
  final ValueChanged<BookingDetail> onComplete;
  final ValueChanged<BookingDetail> onCancel;

  @override
  Widget build(BuildContext context) => Container(
    decoration: const BoxDecoration(
      color: Colors.white,
      border: Border(top: BorderSide(color: NColors.lineSoft)),
      boxShadow: [BoxShadow(color: Color(0x0F2A1E1F), blurRadius: 16, offset: Offset(0, -4))],
    ),
    child: SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
        child: Row(
          children: [
            if (booking.canCancel)
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFFB91C1C), minimumSize: const Size(0, 50)),
                  onPressed: busy ? null : () => onCancel(booking),
                  child: Text(context.t('booking.cancel')),
                ),
              ),
            if (booking.canCancel && booking.canComplete) const SizedBox(width: 12),
            if (booking.canComplete)
              Expanded(
                child: FilledButton.icon(
                  onPressed: busy ? null : () => onComplete(booking),
                  icon: const Icon(Icons.task_alt_rounded, size: 20),
                  label: Text(context.t('booking.complete')),
                ),
              ),
          ],
        ),
      ),
    ),
  );
}

class _CancelDialog extends StatefulWidget {
  const _CancelDialog();

  @override
  State<_CancelDialog> createState() => _CancelDialogState();
}

class _CancelDialogState extends State<_CancelDialog> {
  final _reason = TextEditingController();
  bool _showError = false;

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
    title: Text(context.t('booking.cancel_title')),
    content: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(context.t('booking.cancel_body')),
        const SizedBox(height: 14),
        TextField(
          controller: _reason,
          autofocus: true,
          minLines: 2,
          maxLines: 4,
          maxLength: 500,
          textCapitalization: TextCapitalization.sentences,
          decoration: InputDecoration(
            labelText: context.t('booking.cancel_reason'),
            errorText: _showError ? context.t('booking.cancel_reason_required') : null,
          ),
        ),
      ],
    ),
    actions: [
      TextButton(onPressed: () => Navigator.pop(context), child: Text(context.t('common.back'))),
      FilledButton(
        style: FilledButton.styleFrom(backgroundColor: const Color(0xFFB91C1C), minimumSize: const Size(64, 44)),
        onPressed: () {
          final reason = _reason.text.trim();
          if (reason.isEmpty) {
            setState(() => _showError = true);

            return;
          }
          Navigator.pop(context, reason);
        },
        child: Text(context.t('booking.cancel_confirm')),
      ),
    ],
  );
}
