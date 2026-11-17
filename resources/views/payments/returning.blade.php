{{-- The answer to a gateway posting the payer back (ReturnController): a page
     of our own that moves on to the payment page by itself, so that next
     request is ours and carries the session cookie a cross-site POST does not. --}}
@php
    app(App\Support\Seo::class)->noindex();
@endphp

<x-layouts.app :title="__('pages.payment_checkout.returning_title')" shell="checkout" :preloader="false">
    <main class="flex min-h-screen items-center justify-center bg-ivory px-4">
        <div class="w-full max-w-sm rounded-3xl border border-line bg-surface-raised px-6 py-10 text-center shadow-sm">
            <svg class="mx-auto size-10 animate-spin text-brand-600 [animation-duration:1.6s]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" class="opacity-25" /><path d="M12 3a9 9 0 0 1 9 9" /></svg>
            <h1 class="mt-5 font-display text-2xl font-semibold">{{ __('pages.payment_checkout.returning_title') }}</h1>
            <a href="{{ $url }}" class="mt-6 inline-flex rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.payment_checkout.returning_continue') }}</a>
        </div>
    </main>

    <script>window.location.replace(@json($url));</script>
</x-layouts.app>
