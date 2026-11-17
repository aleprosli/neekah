{{-- The step between our confirmation page and the bank: the form the gateway
     handed back for this payment (CheckoutController::store), posted on by
     itself. The button is there for a browser that does not run it. --}}
@php
    app(App\Support\Seo::class)->noindex();
@endphp

<x-layouts.app :title="__('pages.payment_checkout.redirect_title')" shell="checkout" :preloader="false">
    <main class="flex min-h-screen items-center justify-center bg-ivory px-4">
        <form id="payment-redirect" method="{{ $redirect->method === 'get' ? 'GET' : 'POST' }}" action="{{ $redirect->action }}" class="w-full max-w-sm rounded-3xl border border-line bg-surface-raised px-6 py-10 text-center shadow-sm">
            @foreach ($redirect->fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <svg class="mx-auto size-10 animate-spin text-brand-600 [animation-duration:1.6s]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" class="opacity-25" /><path d="M12 3a9 9 0 0 1 9 9" /></svg>
            <h1 class="mt-5 font-display text-2xl font-semibold">{{ __('pages.payment_checkout.redirect_title') }}</h1>
            <p class="mt-2 text-sm text-ink-muted">{{ __('pages.payment_checkout.redirect_body') }}</p>
            <p class="mt-1 font-mono text-xs text-ink-muted">{{ $payment->reference }}</p>

            <button type="submit" class="mt-6 inline-flex rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.payment_checkout.redirect_continue') }}</button>
        </form>
    </main>

    <script>document.getElementById('payment-redirect').submit();</script>
</x-layouts.app>
