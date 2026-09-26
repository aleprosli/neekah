{{-- The invoice or receipt as a sheet of A4: read on screen, printed, or
     saved as a PDF from the browser's print dialog. The toolbar never prints.
     ?cetak=1 opens the print dialog straight away. --}}
@php
    app(App\Support\Seo::class)->noindex();
    $issuer = $document->issuer();
    $billTo = $document->billTo();
    $paid = $document->isReceipt();
    $back = auth()->user()->isAdmin() ? route('admin.payments.show', $payment) : route('payments.show', $payment);
@endphp

<x-layouts.app :title="$document->title().' '.$document->number()" shell="document" :preloader="false">
    <style>
        @page { size: A4; margin: 12mm; }
        @media print { body { background: #fff !important; } }
    </style>

    <div class="min-h-screen bg-ivory px-3 py-6 print:bg-white print:p-0 sm:px-6 sm:py-10">
        <div class="mx-auto mb-5 flex max-w-[210mm] flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ $back }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-ink-muted transition hover:text-ink">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
                {{ __('pages.receipt.back') }}
            </a>
            <button type="button" data-print class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3h10v6M7 17H4v-7h16v7h-3" /><path d="M7 14h10v7H7z" /></svg>
                {{ __('pages.receipt.print') }}
            </button>
            <p class="w-full text-right text-xs text-ink-muted">{{ __('pages.receipt.print_hint') }}</p>
        </div>

        <article class="relative mx-auto max-w-[210mm] overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgb(0_0_0/0.08),0_12px_40px_-12px_rgb(0_0_0/0.15)] print:max-w-none print:rounded-none print:shadow-none">
            <div class="h-2 bg-gradient-to-r from-brand-700 via-brand-500 to-gold-400 print:[print-color-adjust:exact]"></div>

            <div class="p-7 sm:p-12 print:p-0 print:pt-6">
                <header class="flex flex-col-reverse gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        @if ($document->issuedByVendor())
                            <p class="font-display text-2xl font-semibold">{{ $issuer['name'] }}</p>
                        @else
                            <img src="{{ asset(config('neekah.brand.lockup')) }}" alt="{{ $issuer['name'] }}" class="h-10 w-auto">
                        @endif
                        <div class="mt-3 space-y-0.5 text-sm text-ink-muted">
                            @if (! $document->issuedByVendor())
                                <p class="font-semibold text-ink">{{ $issuer['name'] }}</p>
                            @endif
                            @foreach ($issuer['lines'] as $line)
                                <p class="whitespace-pre-line">{{ $line }}</p>
                            @endforeach
                        </div>
                    </div>

                    <div class="sm:text-right">
                        <p class="font-display text-4xl font-semibold tracking-wide text-brand-700 uppercase">{{ $document->title() }}</p>
                        <p class="mt-1 font-mono text-sm font-semibold">{{ $document->number() }}</p>
                        <span @class([
                            'mt-3 inline-flex rounded-full border-2 px-3 py-1 text-xs font-bold tracking-[0.18em] uppercase print:[print-color-adjust:exact]',
                            'border-emerald-600 text-emerald-700' => $paid,
                            'border-amber-500 text-amber-700' => ! $paid,
                        ])>{{ $payment->status->label() }}</span>
                    </div>
                </header>

                <section class="mt-10 grid gap-6 border-y border-line py-6 sm:grid-cols-2">
                    <div>
                        <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.receipt.bill_to') }}</p>
                        <p class="mt-2 font-semibold">{{ $billTo['name'] }}</p>
                        <div class="mt-1 space-y-0.5 text-sm text-ink-muted">
                            @foreach ($billTo['lines'] as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    </div>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1.5 text-sm sm:justify-self-end">
                        @foreach ($document->facts() as $label => $value)
                            <dt class="text-ink-muted">{{ $label }}</dt>
                            <dd class="font-medium break-words sm:text-right sm:whitespace-nowrap">{{ $value }}</dd>
                        @endforeach
                    </dl>
                </section>

                <div class="mt-8 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-ink text-left">
                                <th class="pb-2.5 text-[11px] font-semibold tracking-[0.14em] uppercase">{{ __('pages.receipt.item') }}</th>
                                <th class="pb-2.5 text-right text-[11px] font-semibold tracking-[0.14em] uppercase">{{ __('pages.receipt.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->items() as $item)
                                <tr class="border-b border-line align-top">
                                    <td class="py-4 pr-4">
                                        <p class="font-semibold">{{ $item['description'] }}</p>
                                        @if ($item['detail'])
                                            <p class="mt-0.5 text-ink-muted">{{ $item['detail'] }}</p>
                                        @endif
                                    </td>
                                    <td class="py-4 text-right font-medium tabular-nums">{{ $item['amount'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <dl class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ __('pages.receipt.subtotal') }}</dt>
                            <dd class="tabular-nums">{{ $document->total() }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 rounded-xl bg-ivory px-4 py-3 text-base font-semibold print:[print-color-adjust:exact]">
                            <dt>{{ __($paid ? 'pages.receipt.amount_paid' : 'pages.receipt.amount_due') }}</dt>
                            <dd class="tabular-nums">{{ $document->total() }}</dd>
                        </div>
                    </dl>
                </div>

                @if ($document->bookingSummary() !== [])
                    <section class="mt-8 rounded-2xl border border-line p-5">
                        <p class="text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase">{{ __('pages.receipt.booking_status') }}</p>
                        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                            @foreach ($document->bookingSummary() as $label => $value)
                                <div>
                                    <dt class="text-ink-muted">{{ $label }}</dt>
                                    <dd class="mt-0.5 font-semibold tabular-nums">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                <footer class="mt-12 border-t border-line pt-6 text-xs leading-relaxed text-ink-muted">
                    <p>{{ $document->footnote() }}</p>
                    <p class="mt-2">{{ __('pages.receipt.computer_generated') }}</p>
                    <p class="mt-4 font-display text-base text-brand-700">{{ __('pages.receipt.thanks') }}</p>
                </footer>
            </div>
        </article>
    </div>

    <script>
        document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
        @if (request()->boolean('cetak'))
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        @endif
    </script>
</x-layouts.app>
