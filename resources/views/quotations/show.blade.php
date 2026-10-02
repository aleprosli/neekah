{{-- A vendor's quotation, or the invoice issued from it, as one sheet of A4:
     read on screen, printed, or saved as a PDF from the browser's print
     dialog. The client needs no account; the toolbar and the answer form
     never print. ?cetak=1 opens the print dialog straight away. --}}
@php
    use App\Models\Quotation;

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

<x-layouts.app :title="$title.' '.$number.' · '.$vendor->name" shell="document" :preloader="false">
    <style>
        @page { size: A4; margin: 12mm; }
        @media print { body { background: #fff !important; } }
    </style>

    <div class="min-h-screen bg-ivory px-3 py-6 print:bg-white print:p-0 sm:px-6 sm:py-10">
        <div class="mx-auto mb-5 flex max-w-[210mm] flex-wrap items-center justify-between gap-3 print:hidden">
            @if ($isOwner)
                <a href="{{ route('vendor.quotations.show', $quotation) }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-ink-muted transition hover:text-ink">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
                    {{ __('pages.quotation_doc.back') }}
                </a>
            @else
                <span></span>
            @endif
            <button type="button" data-print class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3h10v6M7 17H4v-7h16v7h-3" /><path d="M7 14h10v7H7z" /></svg>
                {{ __('pages.quotation_doc.download_pdf') }}
            </button>
            <p class="w-full text-right text-xs text-ink-muted">{{ __('pages.receipt.print_hint') }}</p>

            @if ($isOwner && $quotation->status === App\Enums\QuotationStatus::Draft)
                <p class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('pages.quotation_doc.draft_preview') }}</p>
            @endif
            @if (session('status'))
                <p class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</p>
            @endif
        </div>

        <article class="relative mx-auto max-w-[210mm] overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgb(0_0_0/0.08),0_12px_40px_-12px_rgb(0_0_0/0.15)] print:max-w-none print:rounded-none print:shadow-none">
            <div class="h-2 bg-gradient-to-r from-brand-700 via-brand-500 to-gold-400 print:[print-color-adjust:exact]"></div>

            <div class="p-7 sm:p-12 print:p-0 print:pt-6">
                <header class="flex flex-col-reverse gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        @if ($vendor->logoUrl())
                            <img src="{{ $vendor->logoUrl() }}" alt="" class="size-16 shrink-0 rounded-2xl object-cover">
                        @endif
                        <div class="min-w-0">
                            <p class="font-display text-2xl font-semibold break-words">{{ $vendor->name }}</p>
                            <div class="mt-2 space-y-0.5 text-sm break-words text-ink-muted">
                                @foreach ($vendorLines as $line)
                                    <p>{{ $line }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="sm:text-right">
                        <p class="font-display text-4xl font-semibold tracking-wide text-brand-700 uppercase">{{ $title }}</p>
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

                <section class="mt-10 grid gap-6 border-y border-line py-6 sm:grid-cols-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.quotation_doc.prepared_for') }}</p>
                        <p class="mt-2 font-semibold break-words">{{ $quotation->client_name }}</p>
                        <div class="mt-1 space-y-0.5 text-sm break-words text-ink-muted">
                            @foreach (array_filter([$quotation->client_phone, $quotation->client_email]) as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    </div>
                    <dl class="grid min-w-0 grid-cols-[auto_1fr] gap-x-6 gap-y-1.5 text-sm sm:justify-self-end">
                        @foreach ($facts as $label => $value)
                            <dt class="text-ink-muted">{{ $label }}</dt>
                            <dd class="font-medium break-words sm:text-right">{{ $value }}</dd>
                        @endforeach
                    </dl>
                </section>

                <div class="mt-8 overflow-x-auto">
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
                                            <ul class="mt-1.5 list-disc space-y-0.5 pl-5 text-ink-muted">
                                                @foreach ($item->features as $feature)
                                                    <li>{{ $feature }}</li>
                                                @endforeach
                                            </ul>
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

        {{-- The client's answer. Never printed; once given, the sheet above says so. --}}
        @if ($quotation->awaitsClient() && ! $isOwner)
            <section id="jawapan" class="mx-auto mt-6 grid max-w-[210mm] gap-4 print:hidden sm:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)]">
                <form method="POST" action="{{ route('quotations.public.accept', $quotation->token) }}" class="flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                    @csrf
                    <div>
                        <p class="font-semibold">{{ __('pages.quotation_doc.accept_title') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('pages.quotation_doc.accept_body', ['vendor' => $vendor->name]) }}</p>
                    </div>
                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ __('pages.quotation_doc.full_name') }}</span>
                        <input type="text" name="name" value="{{ old('name', $quotation->client_name) }}" required maxlength="120" autocomplete="name" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        @error('name') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="agree" value="1" required class="mt-0.5 size-4 rounded border-line text-brand-600" @checked(old('agree'))>
                        <span>{{ __('pages.quotation_doc.agree', ['number' => $quotation->number, 'total' => $money($quotation->total)]) }}</span>
                    </label>
                    @error('agree') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                    <div><button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.quotation_doc.accept_submit') }}</button></div>
                </form>

                <details class="min-w-0 self-start rounded-2xl border border-line bg-surface-raised">
                    <summary class="cursor-pointer list-none px-5 py-4 text-sm font-medium text-ink-muted [&::-webkit-details-marker]:hidden">{{ __('pages.quotation_doc.decline_title') }}</summary>
                    <form method="POST" action="{{ route('quotations.public.decline', $quotation->token) }}" class="flex flex-col gap-3 border-t border-line p-5">
                        @csrf
                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium">{{ __('pages.quotation_doc.decline_reason') }}</span>
                            <textarea name="reason" rows="3" maxlength="500" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">{{ old('reason') }}</textarea>
                        </label>
                        <div><button type="submit" class="rounded-full border border-line px-5 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.quotation_doc.decline_submit') }}</button></div>
                    </form>
                </details>
            </section>
        @elseif ($quotation->isExpired())
            <p class="mx-auto mt-6 max-w-[210mm] rounded-2xl border border-line bg-surface-raised p-5 text-sm text-ink-muted print:hidden">{{ __('pages.quotation_doc.expired_body', ['vendor' => $vendor->name]) }}</p>
        @endif

        <p class="mx-auto mt-6 max-w-[210mm] text-center text-xs text-ink-muted print:hidden">{{ __('pages.quotation_doc.powered_by') }}</p>
    </div>

    <script>
        document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
        @if (request()->boolean('cetak'))
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        @endif
    </script>
</x-layouts.app>
