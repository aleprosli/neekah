<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IssueReceipt;
use App\Actions\RequeryPayment;
use App\Actions\SettlePayment;
use App\Enums\CameraTier;
use App\Enums\PaymentMerchant;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VendorPlan;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Notifications\PaymentReceipt;
use App\Support\Payments\PaymentDocument;
use App\Support\Payments\PaymentGateways;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin → Kewangan: every payment on the platform in one ledger, whatever it
 * was for, whose account took it and through which gateway; and one payment
 * with everything that passed between Neekah and its gateway, a requery,
 * and settling it by hand when the money is known to have arrived.
 */
class PaymentController extends Controller
{
    /**
     * Columns for components/ui/DataTable.vue.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => __('pages.payments.col_reference'), 'sortable' => true, 'type' => 'html'],
            ['key' => 'purpose', 'label' => __('pages.payments.col_purpose'), 'type' => 'html'],
            ['key' => 'for', 'label' => __('pages.payments.col_for')],
            ['key' => 'payer', 'label' => __('pages.payments.col_payer')],
            ['key' => 'gateway', 'label' => __('pages.payments.col_gateway')],
            ['key' => 'amount', 'label' => __('pages.payments.col_amount'), 'sortable' => true, 'align' => 'right'],
            ['key' => 'status', 'label' => __('pages.payments.col_status'), 'type' => 'html'],
            ['key' => 'date', 'label' => __('pages.payments.col_date'), 'sort' => 'created_at', 'sortable' => true, 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $paid = Payment::query()->where('status', PaymentStatus::Paid);
        $month = fn () => $paid->clone()->where('paid_at', '>=', now()->startOfMonth());
        $neekahByPurpose = $month()->where('merchant', PaymentMerchant::NEEKAH)
            ->selectRaw('purpose, SUM(amount) as total')->groupBy('purpose')->pluck('total', 'purpose');

        return view('admin.payments.index', [
            'props' => VueProps::for([
                'stats' => [
                    [
                        'label' => __('pages.payments.stat_neekah_month'),
                        'value' => self::ringgit((float) $neekahByPurpose->sum()),
                        'hint' => collect([PaymentPurpose::VendorPro, PaymentPurpose::BoostTokens, PaymentPurpose::Kenangan])
                            ->map(fn (PaymentPurpose $purpose): string => $purpose->label().' '.self::ringgit((float) ($neekahByPurpose[$purpose->value] ?? 0)))
                            ->implode(' · '),
                    ],
                    ['label' => __('pages.payments.stat_neekah_all'), 'value' => self::ringgit((float) $paid->clone()->where('merchant', PaymentMerchant::NEEKAH)->sum('amount')), 'hint' => __('pages.payments.stat_neekah_all_hint')],
                    ['label' => __('pages.payments.stat_vendor_month'), 'value' => self::ringgit((float) $month()->where('merchant', PaymentMerchant::VENDOR)->sum('amount')), 'hint' => __('pages.payments.stat_vendor_hint')],
                    ['label' => __('pages.payments.stat_open'), 'value' => (string) Payment::query()->whereIn('status', [PaymentStatus::Pending, PaymentStatus::AwaitingVerification])->count(), 'hint' => __('pages.payments.stat_open_hint')],
                ],
                'table' => [
                    'dataUrl' => route('admin.payments.data'),
                    'columns' => self::columns(),
                    'filters' => [
                        TableFilter::fromEnum('purpose', PaymentPurpose::cases(), TableFilter::requested($request, 'purpose', array_column(PaymentPurpose::cases(), 'value')), label: __('pages.payments.filter_purpose')),
                        TableFilter::fromEnum('status', PaymentStatus::cases(), TableFilter::requested($request, 'status', array_column(PaymentStatus::cases(), 'value')), label: __('pages.payments.filter_status')),
                        [
                            'key' => 'gateway',
                            'label' => __('pages.payments.filter_gateway'),
                            'value' => TableFilter::requested($request, 'gateway', self::gateways()),
                            'options' => collect(self::gateways())
                                ->map(fn (string $gateway): array => ['value' => $gateway, 'label' => self::gatewayLabel($gateway)])->all(),
                        ],
                        [
                            'key' => 'merchant',
                            'label' => __('pages.payments.filter_merchant'),
                            'value' => TableFilter::requested($request, 'merchant', [PaymentMerchant::NEEKAH, PaymentMerchant::VENDOR]),
                            'options' => collect([PaymentMerchant::NEEKAH, PaymentMerchant::VENDOR])
                                ->map(fn (string $merchant): array => ['value' => $merchant, 'label' => __('enums.payment_merchant.'.$merchant)])->all(),
                        ],
                    ],
                ],
            ]),
        ]);
    }

    /** A page of the ledger for the table. */
    public function data(Request $request): JsonResponse
    {
        $sort = in_array($request->string('sort')->toString(), ['reference', 'amount', 'created_at'], true) ? $request->string('sort')->toString() : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $purposes = TableFilter::requestedEnums($request, 'purpose', PaymentPurpose::class);
        $statuses = TableFilter::requestedEnums($request, 'status', PaymentStatus::class);
        $gateways = TableFilter::requested($request, 'gateway', self::gateways());
        $merchants = TableFilter::requested($request, 'merchant', [PaymentMerchant::NEEKAH, PaymentMerchant::VENDOR]);

        $matching = Payment::query()->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
            $like = '%'.$keyword.'%';
            $query->where(fn ($query) => $query
                ->where('reference', 'like', $like)
                ->orWhere('gateway_reference', 'like', $like)
                ->orWhere('gateway_invoice', 'like', $like)
                ->orWhereHas('vendor', fn ($query) => $query->where('name', 'like', $like))
                ->orWhereHas('wedding', fn ($query) => $query->where('title', 'like', $like))
                ->orWhereHas('booking', fn ($query) => $query->where('reference', 'like', $like))
                ->orWhereHas('recorder', fn ($query) => $query->where('email', 'like', $like)->orWhere('name', 'like', $like)));
        });

        $filtered = $matching->clone()
            ->when($purposes, fn ($query) => $query->whereIn('purpose', $purposes))
            ->when($statuses, fn ($query) => $query->whereIn('status', $statuses))
            ->when($gateways, fn ($query) => $query->whereIn('gateway', $gateways))
            ->when($merchants, fn ($query) => $query->whereIn('merchant', $merchants));

        $payments = $filtered->clone()
            ->with(['vendor', 'wedding', 'booking', 'album.wedding', 'recorder'])
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 25), 100));

        return response()->json([
            'data' => $payments->getCollection()->map(fn (Payment $payment): array => [
                'url' => route('admin.payments.show', $payment),
                'reference' => '<span class="font-mono text-xs whitespace-nowrap">'.e($payment->reference).'</span>',
                'purpose' => self::pill($payment->purpose->label(), $payment->purpose->tone()),
                'for' => self::subject($payment),
                'payer' => $payment->recorder?->name ?? '—',
                'gateway' => self::gatewayLabel($payment->gateway).($payment->method && $payment->method !== 'manual' && $payment->method !== 'manual_transfer' ? ' · '.$payment->method : ''),
                'amount' => self::ringgit((float) $payment->amount),
                'status' => self::pill($payment->status->label(), $payment->status->tone()),
                'date' => '<span class="whitespace-nowrap">'.e($payment->created_at->translatedFormat('j M Y')).'</span><span class="block text-xs text-ink-muted">'.e($payment->created_at->format('g:i A')).'</span>',
            ])->all(),
            'filters' => [
                'purpose' => TableFilter::countsByColumn($matching->clone(), 'purpose'),
                'status' => TableFilter::countsByColumn($matching->clone(), 'status'),
                'gateway' => TableFilter::countsByColumn($matching->clone(), 'gateway'),
                'merchant' => TableFilter::countsByColumn($matching->clone(), 'merchant'),
            ],
            'meta' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    public function show(Payment $payment, PaymentGateways $gateways): View
    {
        $payment->load(['vendor.user', 'wedding.user', 'booking.user', 'album', 'recorder', 'verifier', 'events.user']);
        $gateway = $gateways->has($payment->gateway) ? $gateways->for($payment->gateway) : null;

        return view('admin.payments.show', [
            'payment' => $payment,
            'props' => VueProps::for([
                'payment' => [
                    'reference' => $payment->reference,
                    'purpose' => $payment->purpose->label(),
                    'purpose_tone' => $payment->purpose->tone(),
                    'status' => $payment->status->label(),
                    'status_tone' => $payment->status->tone(),
                    'is_paid' => $payment->isPaid(),
                    'amount' => self::ringgit((float) $payment->amount),
                    'merchant' => __('enums.payment_merchant.'.$payment->merchant),
                    'gateway' => self::gatewayLabel($payment->gateway),
                    'method' => $payment->method,
                    'created_at' => $payment->created_at->translatedFormat('j M Y, g:i:s A'),
                    'paid_at' => $payment->paid_at?->translatedFormat('j M Y, g:i:s A'),
                    'expires_at' => $payment->expires_at?->translatedFormat('j M Y, g:i A'),
                    'last_checked_at' => $payment->last_checked_at?->translatedFormat('j M Y, g:i:s A'),
                    'payer' => $payment->recorder ? ['name' => $payment->recorder->name, 'email' => $payment->recorder->email, 'url' => route('admin.users.show', $payment->recorder)] : null,
                    'subject' => self::subject($payment),
                    'links' => self::links($payment),
                    'gateway_fields' => array_filter([
                        __('pages.payments.gateway_reference') => $payment->gateway_reference,
                        __('pages.payments.gateway_invoice') => $payment->gateway_invoice,
                        __('pages.payments.gateway_transaction') => $payment->gateway_transaction_id,
                        __('pages.payments.gateway_status') => $payment->gateway_status,
                        __('pages.payments.payment_url') => $payment->payment_url,
                    ]),
                    'details' => self::details($payment) ?: (object) [],
                    'gateway_payload' => $payment->gateway_payload,
                    'note' => $payment->note,
                    'receipt_url' => $payment->receiptUrl(),
                ],
                'events' => $payment->events->map(fn (PaymentEvent $event): array => [
                    'id' => $event->id,
                    'type' => __('enums.payment_event.'.$event->type),
                    'raw_type' => $event->type,
                    'at' => $event->created_at->translatedFormat('j M Y, g:i:s A'),
                    'verified' => $event->verified,
                    'outcome' => $event->outcome,
                    'http_status' => $event->http_status,
                    'payload' => $event->payload,
                    'meta' => $event->meta,
                    'by' => $event->user?->name,
                ])->values(),
                'actions' => [
                    'can_requery' => $gateway?->canRequery($payment) ?? false,
                    'requery_url' => route('admin.payments.requery', $payment),
                    'can_attach_invoice' => $payment->isOnline() && ! $payment->isPaid(),
                    'invoice_url' => route('admin.payments.invoice', $payment),
                    'can_mark_paid' => ! $payment->isPaid() && $payment->status !== PaymentStatus::Refunded,
                    'mark_paid_url' => route('admin.payments.paid', $payment),
                ],
                'receipt' => [
                    'is_receipt' => PaymentDocument::for($payment)->isReceipt(),
                    'number' => $payment->receipt_number,
                    'sent_at' => $payment->receipt_sent_at?->translatedFormat('j M Y, g:i A'),
                    'recipient' => PaymentDocument::payerOf($payment)?->email,
                    'document_url' => route('payments.document', $payment),
                    'email_url' => $payment->isPaid() ? route('admin.payments.email', $payment) : null,
                    'send_url' => $payment->isPaid() && PaymentDocument::payerOf($payment) ? route('admin.payments.receipt', $payment) : null,
                ],
            ]),
        ]);
    }

    /** Ask the gateway where the payment stands, now. */
    public function requery(Payment $payment, RequeryPayment $requery): RedirectResponse
    {
        $outcome = $requery->handle($payment);

        return back()->with('status', __('pages.payments.requery_'.($outcome === 'unavailable' ? 'unavailable' : 'done'), ['outcome' => $outcome]));
    }

    /**
     * Tie a payment to the invoice the gateway's dashboard shows for it, when
     * neither its callback nor the payer's return ever arrived, then ask the
     * gateway about it.
     */
    public function invoice(Request $request, Payment $payment, RequeryPayment $requery): RedirectResponse
    {
        abort_if($payment->isPaid() || ! $payment->isOnline(), 404);
        $code = trim($request->validate(['invoice' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/']])['invoice']);

        // Either of Herepay's codes may be pasted; the payment code (HP-PAY-…) is the one a lookup finds.
        $payment->update(str_starts_with(strtoupper($code), 'HP-INV') ? ['gateway_invoice' => $code] : ['gateway_reference' => $code]);
        PaymentEvent::record($payment, $payment->gateway, PaymentEvent::INVOICE_ATTACHED, meta: ['code' => $code]);

        $outcome = $requery->handle($payment);

        return back()->with('status', __('pages.payments.requery_'.($outcome === 'unavailable' ? 'unavailable' : 'done'), ['outcome' => $outcome]));
    }

    /**
     * The money is known to have arrived (seen in the gateway's dashboard or
     * the bank) but nothing told Neekah: settle it by hand, with a reason.
     * It gives the payer what they bought, exactly as a callback would.
     */
    public function markPaid(Request $request, Payment $payment, SettlePayment $settle): RedirectResponse
    {
        abort_if($payment->isPaid() || $payment->status === PaymentStatus::Refunded, 404);
        $note = $request->validate(['note' => ['required', 'string', 'max:255']])['note'];

        PaymentEvent::record($payment, $payment->gateway, PaymentEvent::MANUAL_VERIFIED, meta: ['note' => $note], outcome: SettlePayment::PAID);
        $settle->markPaid($payment, ['note' => trim(($payment->note ? $payment->note."\n" : '').$note)]);

        Log::warning('Admin settled a payment by hand', ['admin_id' => $request->user()->id, 'payment' => $payment->reference, 'note' => $note]);

        return back()->with('status', __('pages.payments.marked_paid', ['reference' => $payment->reference]));
    }

    /** The receipt email exactly as the payer gets it. */
    public function email(Payment $payment): Response
    {
        $payer = PaymentDocument::payerOf($payment);
        abort_unless($payment->isPaid() && $payer, 404);

        return response((new PaymentReceipt($payment))->toMail($payer)->render());
    }

    /** Send the receipt again, to the payer's address as it is now. */
    public function sendReceipt(Payment $payment, IssueReceipt $receipt): RedirectResponse
    {
        abort_unless($payment->isPaid(), 404);

        return back()->with('status', $receipt->send($payment, again: true)
            ? __('flash.admin.receipt_sent', ['email' => PaymentDocument::payerOf($payment)?->email])
            : __('flash.admin.receipt_not_sent'));
    }

    /** What the payment was for, in a few words. */
    private static function subject(Payment $payment): string
    {
        return match ($payment->purpose) {
            PaymentPurpose::Booking => ($payment->booking?->reference ?? '—').' · '.($payment->vendor?->name ?? '—'),
            PaymentPurpose::VendorPro => ($payment->vendor?->name ?? '—').' · '.__('enums.vendor_plan.'.$payment->detail('plan', 'monthly')),
            PaymentPurpose::BoostTokens => ($payment->vendor?->name ?? '—').' · '.__('pages.payments.tokens', ['count' => $payment->detail('tokens', 0)]),
            PaymentPurpose::Kenangan => ($payment->album?->displayTitle() ?? $payment->detail('album_title') ?? $payment->wedding?->title ?? '—').' · '.Str::headline((string) $payment->detail('tier')),
        };
    }

    /**
     * Where to go from here: the vendor, the couple, the booking.
     *
     * @return list<array{label: string, url: string}>
     */
    private static function links(Payment $payment): array
    {
        return array_values(array_filter([
            $payment->vendor ? ['label' => __('pages.payments.link_vendor', ['name' => $payment->vendor->name]), 'url' => route('admin.vendors.show', $payment->vendor)] : null,
            $payment->booking ? ['label' => __('pages.payments.link_booking', ['reference' => $payment->booking->reference]), 'url' => route('admin.bookings.show', $payment->booking)] : null,
            $payment->wedding?->user ? ['label' => __('pages.payments.link_couple', ['name' => $payment->wedding->user->name]), 'url' => route('admin.users.show', $payment->wedding->user)] : null,
        ]));
    }

    /**
     * The purpose's own facts, labelled and readable.
     *
     * @return array<string, string>
     */
    private static function details(Payment $payment): array
    {
        return collect($payment->details ?? [])
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                __('pages.payments.detail_'.$key) => match (true) {
                    $key === 'plan' => VendorPlan::tryFrom((string) $value)?->label() ?? (string) $value,
                    $key === 'tier' => CameraTier::tryFrom((string) $value)?->label() ?? (string) $value,
                    $key === 'kind' => __('pages.payments.kind_'.$value),
                    in_array($key, ['starts_at', 'ends_at'], true) => Carbon::parse((string) $value)->translatedFormat('j M Y, g:i A'),
                    $key === 'album_event_date' => Carbon::parse((string) $value)->translatedFormat('j M Y'),
                    default => is_scalar($value) ? (string) $value : (string) json_encode($value),
                },
            ])
            ->all();
    }

    /**
     * Every gateway a payment can name.
     *
     * @return list<string>
     */
    private static function gateways(): array
    {
        return [...array_keys(PaymentGateways::DRIVERS), Payment::GATEWAY_MANUAL];
    }

    private static function gatewayLabel(string $gateway): string
    {
        return $gateway === Payment::GATEWAY_MANUAL ? __('pages.payments.gateway_manual') : Str::headline($gateway);
    }

    private static function pill(string $label, string $tone): string
    {
        return view('components.admin.status-pill', ['label' => $label, 'tone' => $tone])->render();
    }

    private static function ringgit(float $amount): string
    {
        return 'RM'.number_format($amount, 2);
    }
}
