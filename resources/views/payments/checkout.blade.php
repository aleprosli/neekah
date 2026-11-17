{{-- The order confirmation before the bank (Payments\CheckoutController): a
     page of its own, without the dashboard around it. The summary on one
     side, the bank on the other; Pay starts the payment and the next page
     posts the payer on to their bank. --}}
@php
    $item = $document->items()[0];
    $total = $document->total();
    $isBooking = $payment->purpose === App\Enums\PaymentPurpose::Booking;
    $oldMethod = old('payment_method', '');
    $oldCode = old('bank_code', '');
@endphp

<x-layouts.app :title="__('pages.payment_checkout.title')" shell="checkout" :preloader="false">
    <div class="min-h-screen bg-ivory">
        <header class="border-b border-line/70 bg-surface-raised/80">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <a href="{{ $back }}" class="inline-flex items-center gap-1.5 rounded-full py-2 pr-3 text-sm font-medium text-ink-muted transition hover:text-ink">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
                    {{ __('pages.payment_checkout.back') }}
                </a>
                <img src="{{ asset(config('neekah.brand.lockup')) }}" alt="{{ config('app.name') }}" class="h-7 w-auto">
                <span class="hidden items-center gap-1.5 text-xs font-medium text-ink-muted sm:inline-flex">
                    <svg class="size-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                    {{ __('pages.payment_checkout.secure') }}
                </span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
            <div class="mb-8 max-w-2xl">
                <p class="text-xs font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ $payment->purpose->label() }}</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-balance sm:text-4xl">{{ __('pages.payment_checkout.heading') }}</h1>
                <p class="mt-2 text-sm leading-relaxed text-ink-muted text-pretty">{{ __('pages.payment_checkout.intro') }}</p>
            </div>

            <form method="POST" action="{{ route('payments.pay', $payment) }}" class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start" onsubmit="this.querySelectorAll('button[type=submit]').forEach((button) => { button.disabled = true; })">
                @csrf

                <aside class="order-first min-w-0 rounded-3xl border border-line bg-surface-raised p-6 shadow-sm lg:sticky lg:top-6 lg:order-last">
                    <h2 class="font-display text-xl font-semibold">{{ __('pages.payment_checkout.summary') }}</h2>

                    <div class="mt-4 rounded-2xl bg-ivory px-4 py-3.5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $item['description'] }}</p>
                                @if ($item['detail'])
                                    <p class="mt-0.5 text-sm text-ink-muted">{{ $item['detail'] }}</p>
                                @endif
                            </div>
                            <p class="shrink-0 font-semibold tabular-nums">{{ $item['amount'] }}</p>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ __('pages.payment_checkout.reference') }}</dt>
                            <dd class="font-mono font-medium">{{ $payment->reference }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ __('pages.payment_checkout.pay_to') }}</dt>
                            <dd class="min-w-0 text-right font-medium">{{ $issuer['name'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-muted">{{ __('pages.payment_checkout.payer') }}</dt>
                            <dd class="min-w-0 text-right">
                                <span class="block font-medium">{{ $payer->name }}</span>
                                <span class="block text-ink-muted">{{ $payer->email }}</span>
                                @if ($payer->phone)
                                    <span class="block text-ink-muted">{{ $payer->phone }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex items-baseline justify-between gap-4 border-t border-line pt-5">
                        <p class="text-sm font-semibold">{{ __('pages.payment_checkout.total') }}</p>
                        <p class="font-display text-3xl font-semibold tabular-nums">{{ $total }}</p>
                    </div>

                    @if ($isBooking)
                        <p class="mt-4 rounded-xl bg-surface-muted px-3.5 py-3 text-xs leading-relaxed text-ink-muted">{{ __('pages.payment_checkout.booking_note', ['vendor' => $issuer['name']]) }}</p>
                    @endif

                    @if ($payment->expires_at)
                        <p class="mt-4 text-xs text-ink-muted">{{ __('pages.payment_checkout.valid_until', ['date' => $payment->expires_at->translatedFormat('j F Y, g:i A')]) }}</p>
                    @endif
                </aside>

                <section class="min-w-0 rounded-3xl border border-line bg-surface-raised p-6 shadow-sm sm:p-8">
                    @if ($errors->any())
                        <ul class="mb-5 flex flex-col gap-1 rounded-2xl bg-brand-50 p-4 text-sm text-brand-800" role="alert">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($needsPhone)
                        <label class="mb-6 flex flex-col gap-1.5">
                            <span class="text-sm font-semibold">{{ __('pages.payment_checkout.phone') }}</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="012-345 6789" required maxlength="30" class="w-full rounded-xl border border-line bg-surface-raised px-4 py-2.5 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                            <span class="text-xs text-ink-muted">{{ __('pages.payment_checkout.phone_help') }}</span>
                        </label>
                    @endif

                    <h2 class="mb-4 font-display text-xl font-semibold">{{ __('pages.payment_checkout.choose_bank') }}</h2>

                    @if ($banks === [])
                        <div class="rounded-2xl border border-dashed border-line px-5 py-8 text-center">
                            <p class="text-sm text-ink-muted">{{ __('pages.payment_checkout.banks_unavailable') }}</p>
                            <a href="{{ route('payments.checkout', $payment) }}" class="mt-4 inline-flex rounded-full border border-line px-5 py-2 text-sm font-semibold transition hover:border-brand-400">{{ __('pages.payment_checkout.banks_retry') }}</a>
                        </div>
                    @else
                        <div data-vue="payment-bank-picker" data-props="@vueProps([
                            'groups' => $banks,
                            'payLabel' => __('pages.payment_checkout.pay', ['amount' => $total]),
                            'oldMethod' => $oldMethod,
                            'oldCode' => $oldCode,
                        ])">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex flex-col gap-1.5">
                                    <span class="text-xs font-medium">{{ __('pages.payment_checkout.method') }}</span>
                                    <select name="payment_method" required class="rounded-xl border border-line bg-surface-raised px-3 py-2.5 text-sm">
                                        @foreach ($banks as $group)
                                            <option value="{{ $group['method'] }}" @selected($oldMethod === $group['method'])>{{ $group['method'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex flex-col gap-1.5">
                                    <span class="text-xs font-medium">{{ __('pages.payment_checkout.bank') }}</span>
                                    <select name="bank_code" required class="rounded-xl border border-line bg-surface-raised px-3 py-2.5 text-sm">
                                        @foreach ($banks as $group)
                                            <optgroup label="{{ $group['method'] }}">
                                                @foreach ($group['channels'] as $bank)
                                                    <option value="{{ $bank['code'] }}" @selected($oldCode === $bank['code'])>{{ $bank['name'] }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <button type="submit" class="mt-5 inline-flex w-full items-center justify-center rounded-full bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('pages.payment_checkout.pay', ['amount' => $total]) }}</button>
                        </div>
                    @endif

                    <p class="mt-5 text-center text-xs text-ink-muted">{{ __('pages.payment_checkout.terms') }}</p>
                </section>
            </form>
        </main>
    </div>
</x-layouts.app>
