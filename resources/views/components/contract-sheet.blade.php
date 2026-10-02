@props(['contract'])

{{-- A contract as a sheet of A4: the client's page, the vendor's view of it
     and the PDF. Pair it with <x-print-fit /> so it prints on one page. --}}
@php
    use App\Models\Quotation;

    $vendor = $contract->vendor;
    $quotation = $contract->quotation;
    $vendorLines = array_values(array_filter([
        $vendor->phone,
        $vendor->user?->email,
        collect([$vendor->city, $vendor->state])->filter()->implode(', '),
    ]));
    $badge = [$contract->status->label(), $contract->status->tone()];
@endphp

<article data-doc {{ $attributes->class('@container relative mx-auto max-w-[210mm] overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgb(0_0_0/0.08),0_12px_40px_-12px_rgb(0_0_0/0.15)] print:max-w-none print:rounded-none print:shadow-none') }}>
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
                <p class="font-display text-3xl font-semibold tracking-wide text-brand-700 uppercase @2xl:text-4xl">{{ __('pages.contract_doc.contract') }}</p>
                <p class="mt-1 font-mono text-sm font-semibold">{{ $contract->number }}</p>
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
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.contract_doc.between') }}</p>
                <p class="mt-2 font-semibold break-words">{{ $vendor->name }}</p>
                <p class="text-sm text-ink-muted">{{ __('pages.contract_doc.the_vendor') }}</p>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.contract_doc.and') }}</p>
                <p class="mt-2 font-semibold break-words">{{ $contract->client_name }}</p>
                <p class="text-sm break-words text-ink-muted">{{ collect([$contract->client_phone, $contract->client_email])->filter()->implode(' · ') }}</p>
                <p class="text-sm text-ink-muted">{{ __('pages.contract_doc.the_client') }}</p>
            </div>
            @if ($contract->event_date)
                <p class="text-sm @xl:col-span-2"><span class="text-ink-muted">{{ __('pages.contract_doc.event_date') }}:</span> <span class="font-medium">{{ $contract->event_date->translatedFormat('l, j F Y') }}</span></p>
            @endif
        </section>

        @if ($quotation)
            <section class="mt-8 rounded-2xl border border-line p-5 print:break-inside-avoid">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.contract_doc.quotation', ['number' => $quotation->number]) }}</p>
                <ul class="mt-3 flex flex-col gap-1.5 text-sm">
                    @foreach ($quotation->items as $item)
                        <li class="flex justify-between gap-4">
                            <span class="min-w-0">{{ $item->name }}@if ($item->quantity > 1) × {{ $item->quantity }}@endif</span>
                            <span class="shrink-0 tabular-nums">{{ Quotation::money($item->line_total) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-3 flex flex-col gap-1 border-t border-line pt-3 text-sm">
                    <div class="flex justify-between gap-4 font-semibold"><dt>{{ __('pages.receipt.total') }}</dt><dd class="tabular-nums">{{ Quotation::money($quotation->total) }}</dd></div>
                    @if ($quotation->hasDeposit())
                        <div class="flex justify-between gap-4 text-ink-muted"><dt>{{ __('pages.quotation_doc.deposit') }}</dt><dd class="tabular-nums">{{ Quotation::money($quotation->deposit_amount) }}</dd></div>
                        <div class="flex justify-between gap-4 text-ink-muted"><dt>{{ __('pages.quotation_doc.balance') }}</dt><dd class="tabular-nums">{{ Quotation::money($quotation->balanceAmount()) }}</dd></div>
                    @endif
                </dl>
            </section>
        @endif

        <ol class="mt-8 flex flex-col gap-6">
            @foreach ($contract->sections ?? [] as $section)
                <li class="print:break-inside-avoid">
                    <h2 class="font-semibold">{{ $loop->iteration }}. {{ $section['title'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink-muted">{{ $section['body'] }}</p>
                </li>
            @endforeach
        </ol>

        <section class="mt-10 grid gap-6 border-t border-line pt-6 text-sm @xl:grid-cols-2 print:break-inside-avoid">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.contract_doc.vendor_side') }}</p>
                @if ($contract->sent_at)
                    <p class="mt-3 font-display text-xl">{{ $contract->vendor_signatory }}</p>
                    <p class="mt-1 text-ink-muted">{{ __('pages.contract_doc.issued_by', ['vendor' => $vendor->name, 'date' => $contract->sent_at->translatedFormat('j F Y, g:i A')]) }}</p>
                @else
                    <p class="mt-3 text-ink-muted">—</p>
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.contract_doc.client_side') }}</p>
                @if ($contract->signed_at)
                    <img src="{{ $contract->signatureUrl() }}" alt="{{ __('pages.contract_doc.signature_alt', ['name' => $contract->signer_name]) }}" class="mt-2 h-20 w-auto">
                    <p class="mt-1 font-semibold">{{ $contract->signer_name }}</p>
                    <p class="text-ink-muted">{{ __('pages.contract_doc.signed_on', ['date' => $contract->signed_at->translatedFormat('j F Y, g:i A')]) }}</p>
                    <p class="mt-2 text-xs break-all text-ink-muted">{{ __('pages.contract_doc.audit', ['ip' => $contract->signer_ip, 'hash' => substr((string) $contract->content_hash, 0, 12)]) }}</p>
                @else
                    <p class="mt-3 text-ink-muted">{{ __('pages.contract_doc.not_signed') }}</p>
                @endif
            </div>
        </section>

        <footer class="mt-12 border-t border-line pt-6 text-xs leading-relaxed text-ink-muted">
            <p>{{ __('pages.contract_doc.footnote', ['vendor' => $vendor->name]) }}</p>
        </footer>
    </div>
</article>
