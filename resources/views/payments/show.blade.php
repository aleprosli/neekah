{{-- Where every payment lands once the payer is back from the gateway. It
     reads our own records: the callback usually settles the payment within
     seconds, so a waiting page looks again on its own for a minute. --}}
@php
    $tones = [
        'paid' => ['ring' => 'bg-emerald-50 text-emerald-600 ring-emerald-100', 'pill' => 'emerald'],
        'waiting' => ['ring' => 'bg-amber-50 text-amber-600 ring-amber-100', 'pill' => 'amber'],
        'failed' => ['ring' => 'bg-brand-50 text-brand-600 ring-brand-100', 'pill' => 'brand'],
        'refunded' => ['ring' => 'bg-surface-muted text-ink-muted ring-line', 'pill' => 'muted'],
    ][$state];
    $item = $document->items()[0];
    $retries = request()->integer('cuba');
@endphp

<x-dynamic-component :component="$layout" :title="__('pages.payment_page.title')" :heading="__('pages.payment_page.title')" :subheading="$payment->purpose->label()">
    @if ($state === 'waiting' && $payment->isOnline() && $retries < 10)
        <script>
            setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('cuba', String({{ $retries + 1 }}));
                window.location.replace(url.toString());
            }, 6000);
        </script>
    @endif

    <div class="mx-auto flex max-w-2xl flex-col gap-5">
        <section class="relative overflow-hidden rounded-3xl border border-line bg-surface-raised px-6 pt-10 pb-8 text-center shadow-sm sm:px-10">
            @if ($state === 'paid')
                <x-site.ornament name="corner-peony" class="absolute -top-14 -left-14 size-48 opacity-50" color="var(--color-brand-200)" color2="var(--color-brand-100)" />
                <x-site.ornament name="corner-peony" class="absolute -top-14 -right-14 size-48 -scale-x-100 opacity-40" color="var(--color-gold-300)" color2="var(--color-brand-100)" />
            @endif

            <div class="relative mx-auto flex size-20 items-center justify-center rounded-full ring-8 {{ $tones['ring'] }}">
                @switch($state)
                    @case('paid')
                        <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
                        @break
                    @case('waiting')
                        <svg class="size-10 {{ $payment->isOnline() ? 'animate-spin [animation-duration:2.4s]' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" class="opacity-25" /><path d="M12 3a9 9 0 0 1 9 9" /></svg>
                        @break
                    @default
                        <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 7.5v6M12 16.8v.2" /><circle cx="12" cy="12" r="9" /></svg>
                @endswitch
            </div>

            <p class="relative mt-6 text-xs font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ $payment->purpose->label() }}</p>
            <h2 class="relative mt-2 font-display text-3xl font-semibold text-balance sm:text-4xl">{{ __("pages.payment_page.{$state}_title") }}</h2>
            <p class="relative mx-auto mt-3 max-w-md text-sm leading-relaxed text-ink-muted text-pretty">{{ $message }}</p>

            <p class="relative mt-6 font-display text-4xl font-semibold tabular-nums">{{ $document->total() }}</p>
            <div class="relative mt-2 flex items-center justify-center gap-2">
                <x-admin.status-pill :label="$payment->status->label()" :tone="$tones['pill']" />
            </div>

            <div class="relative mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ $next['url'] }}" class="inline-flex w-full items-center justify-center rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:w-auto">{{ $next['label'] }}</a>
                <a href="{{ route('payments.document', $payment) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-line bg-surface-raised px-6 py-3 text-sm font-semibold transition hover:border-brand-400 sm:w-auto">
                    <svg class="size-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l5 5v13H7z" /><path d="M14 3v5h5M10 13h6M10 17h6" /></svg>
                    {{ __($document->isReceipt() ? 'pages.payment_page.view_receipt' : 'pages.payment_page.view_invoice') }}
                </a>
            </div>

            @if ($state === 'waiting' && $payment->isOnline())
                <p class="relative mt-6 text-xs text-ink-muted">
                    {{ $retries < 10 ? __('pages.payment_page.checking') : __('pages.payment_page.waiting_still', ['reference' => $payment->reference]) }}
                </p>
            @endif
        </section>

        <section class="rounded-3xl border border-line bg-surface-raised p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h3 class="font-display text-xl font-semibold">{{ __('pages.payment_page.summary') }}</h3>
                <span class="text-sm font-medium text-ink-muted">{{ $document->title() }} · <span class="font-mono text-ink">{{ $document->number() }}</span></span>
            </div>

            <div class="mt-5 flex items-start justify-between gap-4 rounded-2xl bg-ivory px-4 py-3.5">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $item['description'] }}</p>
                    @if ($item['detail'])
                        <p class="mt-0.5 text-sm text-ink-muted">{{ $item['detail'] }}</p>
                    @endif
                </div>
                <p class="shrink-0 font-semibold tabular-nums">{{ $item['amount'] }}</p>
            </div>

            <dl class="mt-5 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                @foreach ($document->facts() as $label => $value)
                    <div class="flex justify-between gap-4 border-b border-line/70 pb-2.5 sm:block sm:border-0 sm:pb-0">
                        <dt class="text-ink-muted">{{ $label }}</dt>
                        <dd class="text-right font-medium break-all sm:mt-0.5 sm:text-left">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($document->isReceipt() && $payment->receipt_sent_at && $payer)
                <p class="mt-6 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16v12H4z" /><path d="M4 7l8 6 8-6" /></svg>
                    {{ __('pages.payment_page.receipt_emailed', ['email' => $payer->email]) }}
                </p>
            @endif

            <p class="mt-5 text-xs leading-relaxed text-ink-muted">{{ $document->footnote() }}</p>
        </section>
    </div>
</x-dynamic-component>
