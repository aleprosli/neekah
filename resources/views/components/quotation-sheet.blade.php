@props(['quotation', 'asInvoice' => false, 'fluid' => false])

{{-- A quotation, or the invoice issued from it, as a sheet of A4. The same
     sheet is the client's page, the vendor's view of it and the PDF; pair it
     with <x-print-fit /> so it prints on one page. --}}
@php
    use App\Models\Quotation;

    $vendor = $quotation->vendor;
    $money = fn ($amount) => Quotation::money($amount);
    $title = $asInvoice ? __('pages.quotation_doc.invoice') : __('pages.quotation_doc.quotation');
    $number = $asInvoice ? $quotation->invoice_number : $quotation->number;
    $vendorLines = array_values(array_filter([
        $vendor->phone,
        $vendor->user?->email,
        collect([$vendor->city, $vendor->state])->filter()->implode(', '),
    ]));
    $facts = array_filter([
        __('pages.quotation_doc.issued_on') => ($asInvoice ? $quotation->invoiced_at : ($quotation->sent_at ?? $quotation->created_at))?->translatedFormat('j F Y'),
        __('pages.quotation_doc.quotation_ref') => $asInvoice ? $quotation->number : null,
        __('pages.quotation_doc.valid_until') => $asInvoice ? null : $quotation->valid_until->translatedFormat('j F Y'),
        __('pages.quotation_doc.event_date') => $quotation->event_date?->translatedFormat('j F Y'),
        __('pages.quotation_doc.event_location') => $quotation->event_location,
    ]);
    $paid = $quotation->booking?->paidAmount();
    $badge = match (true) {
        $asInvoice => [$quotation->invoice_status->label(), $quotation->invoice_status->tone()],
        $quotation->isExpired() => [__('pages.quotations.expired'), 'muted'],
        default => [$quotation->status->label(), $quotation->status->tone()],
    };
@endphp

<article data-doc {{ $attributes->class([
    '@container relative mx-auto overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgb(0_0_0/0.08),0_12px_40px_-12px_rgb(0_0_0/0.15)] print:max-w-none print:rounded-none print:shadow-none',
    $fluid ? 'w-full' : 'max-w-[210mm]',
]) }}>
    <div class="h-2 bg-gradient-to-r from-brand-700 via-brand-500 to-gold-400 print:[print-color-adjust:exact]"></div>

    <div data-doc-body class="p-5 @lg:p-8 @2xl:p-12 print:p-0 print:pt-6">
        <header class="flex flex-col-reverse gap-6 @xl:flex-row @xl:items-start @xl:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                @if ($vendor->logoUrl())
                    <img src="{{ $vendor->logoUrl() }}" alt="" class="size-16 shrink-0 rounded-2xl object-cover">
                @endif
                <div class="min-w-0">
                    <p class="font-display text-xl font-semibold break-words @xl:text-2xl">{{ $vendor->name }}</p>
                    <div class="mt-2 space-y-0.5 text-sm break-words text-ink-muted">
                        @foreach ($vendorLines as $line)
                            <p>{{ $line }}</p>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="@xl:text-right">
                <p class="font-display text-3xl font-semibold tracking-wide text-brand-700 uppercase @2xl:text-4xl">{{ $title }}</p>
                <p class="mt-1 font-mono text-sm font-semibold">{{ $number }}</p>
                <span @class([
                    'mt-3 inline-flex rounded-full border-2 px-3 py-1 text-xs font-bold tracking-[0.18em] uppercase print:[print-color-adjust:exact]',
                    'border-emerald-600 text-emerald-700' => $badge[1] === 'emerald',
                    'border-sky-600 text-sky-700' => $badge[1] === 'sky',
                    'border-amber-500 text-amber-700' => $badge[1] === 'amber',
                    'border-line text-ink-muted' => $badge[1] === 'muted',
                ])>{{ $badge[0] }}</span>
            </div>
        </header>

        <section class="mt-10 grid gap-6 border-y border-line py-6 @xl:grid-cols-2">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.quotation_doc.prepared_for') }}</p>
                <p class="mt-2 font-semibold break-words">{{ $quotation->client_name }}</p>
                <div class="mt-1 space-y-0.5 text-sm break-words text-ink-muted">
                    @foreach (array_filter([$quotation->client_phone, $quotation->client_email]) as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>
            </div>
            <dl class="grid min-w-0 grid-cols-[auto_1fr] gap-x-6 gap-y-1.5 text-sm @xl:justify-self-end">
                @foreach ($facts as $label => $value)
                    <dt class="text-ink-muted">{{ $label }}</dt>
                    <dd class="font-medium break-words @xl:text-right">{{ $value }}</dd>
                @endforeach
            </dl>
        </section>

        {{-- Narrow (a phone): one line per item, stacked, so nothing runs off
             the side. From a medium width, and always in print, the table. --}}
        <ul class="mt-8 border-t-2 border-ink @xl:hidden">
            @foreach ($quotation->items as $item)
                <li class="border-b border-line py-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 font-semibold break-words">{{ $item->name }}</p>
                        <p class="shrink-0 font-medium whitespace-nowrap tabular-nums">{{ $money($item->line_total) }}</p>
                    </div>
                    @if ($item->description)
                        <p class="mt-0.5 whitespace-pre-line text-ink-muted">{{ $item->description }}</p>
                    @endif
                    @if ($item->features)
                        <p class="mt-0.5 text-xs text-ink-muted">{{ implode(' · ', $item->features) }}</p>
                    @endif
                    <p class="mt-1 text-xs text-ink-muted tabular-nums">{{ $item->quantity }} × {{ $money($item->unit_price) }}</p>
                </li>
            @endforeach
        </ul>

        <div class="mt-8 hidden overflow-x-auto @xl:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-ink text-left">
                        <th class="pb-2.5 text-[11px] font-semibold tracking-[0.14em] uppercase">{{ __('pages.receipt.item') }}</th>
                        <th class="pb-2.5 text-right text-[11px] font-semibold tracking-[0.14em] uppercase">{{ __('pages.quotation_doc.quantity') }}</th>
                        <th class="pb-2.5 text-right text-[11px] font-semibold tracking-[0.14em] whitespace-nowrap uppercase">{{ __('pages.quotation_doc.unit_price') }}</th>
                        <th class="pb-2.5 text-right text-[11px] font-semibold tracking-[0.14em] uppercase">{{ __('pages.receipt.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->items as $item)
                        <tr class="border-b border-line align-top">
                            <td class="py-4 pr-4">
                                <p class="font-semibold">{{ $item->name }}</p>
                                @if ($item->description)
                                    <p class="mt-0.5 whitespace-pre-line text-ink-muted">{{ $item->description }}</p>
                                @endif
                                @if ($item->features)
                                    <ul data-doc-features class="mt-1.5 list-disc space-y-0.5 pl-5 text-ink-muted">
                                        @foreach ($item->features as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                    <p data-doc-features-inline class="mt-0.5 hidden text-xs text-ink-muted">{{ implode(' · ', $item->features) }}</p>
                                @endif
                            </td>
                            <td class="py-4 pr-4 text-right tabular-nums">{{ $item->quantity }}</td>
                            <td class="py-4 pr-4 text-right whitespace-nowrap tabular-nums">{{ $money($item->unit_price) }}</td>
                            <td class="py-4 text-right font-medium whitespace-nowrap tabular-nums">{{ $money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <dl class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('pages.receipt.subtotal') }}</dt>
                    <dd class="tabular-nums">{{ $money($quotation->subtotal) }}</dd>
                </div>
                @if ((float) $quotation->discount_amount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-muted">{{ $quotation->discount_type === App\Enums\DepositType::Percent ? __('pages.quotation_doc.discount_percent', ['percent' => rtrim(rtrim(number_format((float) $quotation->discount_value, 2), '0'), '.')]) : __('pages.quotation_doc.discount') }}</dt>
                        <dd class="tabular-nums">− {{ $money($quotation->discount_amount) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 rounded-xl bg-ivory px-4 py-3 text-base font-semibold print:[print-color-adjust:exact]">
                    <dt>{{ __('pages.receipt.total') }}</dt>
                    <dd class="tabular-nums">{{ $money($quotation->total) }}</dd>
                </div>
                @if ($quotation->hasDeposit())
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-muted">{{ __('pages.quotation_doc.deposit') }}</dt>
                        <dd class="font-medium tabular-nums">{{ $money($quotation->deposit_amount) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-muted">{{ __('pages.quotation_doc.balance') }}</dt>
                        <dd class="tabular-nums">{{ $money($quotation->balanceAmount()) }}</dd>
                    </div>
                @endif
                @if ($asInvoice && $paid !== null)
                    <div class="flex justify-between gap-4 border-t border-line pt-2">
                        <dt class="text-ink-muted">{{ __('pages.quotation_doc.paid_so_far') }}</dt>
                        <dd class="tabular-nums">{{ $money($paid) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 font-semibold">
                        <dt>{{ __('pages.quotation_doc.outstanding') }}</dt>
                        <dd class="tabular-nums">{{ $money(max(0, (float) $quotation->total - $paid)) }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if (filled($quotation->notes))
            <section class="mt-8">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.quotation_doc.notes') }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ $quotation->notes }}</p>
            </section>
        @endif

        @if (filled($quotation->terms))
            <section class="mt-8 rounded-2xl border border-line p-5 print:break-inside-avoid">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.quotation_doc.terms') }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink-muted">{{ $quotation->terms }}</p>
            </section>
        @endif

        @if ($quotation->accepted_at)
            <section class="mt-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm print:break-inside-avoid print:[print-color-adjust:exact]">
                <p class="font-semibold text-emerald-900">{{ __('pages.quotation_doc.accepted_by', ['name' => $quotation->accepted_name]) }}</p>
                <p class="mt-1 text-emerald-800">{{ $quotation->accepted_at->translatedFormat('j F Y, g:i A') }}</p>
            </section>
        @elseif ($quotation->declined_at)
            <section class="mt-8 rounded-2xl border border-line bg-surface-muted p-5 text-sm print:hidden">
                <p class="font-semibold">{{ __('pages.quotation_doc.declined_on', ['date' => $quotation->declined_at->translatedFormat('j F Y')]) }}</p>
            </section>
        @endif

        <footer class="mt-12 border-t border-line pt-6 text-xs leading-relaxed text-ink-muted">
            <p>{{ __('pages.quotation_doc.footnote', ['vendor' => $vendor->name]) }}</p>
            <p class="mt-2">{{ __('pages.receipt.computer_generated') }}</p>
        </footer>
    </div>
</article>
