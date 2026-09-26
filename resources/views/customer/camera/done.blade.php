{{-- Where Herepay sends the couple back. Herepay's callback, usually a few
     seconds later, is what opens the album; a waiting page looks again on
     its own for a minute. --}}
<x-layouts.customer :title="__('pages.camera.title')" :heading="__('pages.camera.title')">
    @if ($state === 'waiting' && request()->integer('cuba') < 10)
        <script>
            setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('cuba', String({{ request()->integer('cuba') + 1 }}));
                window.location.replace(url.toString());
            }, 6000);
        </script>
    @endif

    <section class="mx-auto flex max-w-lg flex-col items-center gap-4 rounded-2xl border border-line bg-surface-raised px-6 py-10 text-center">
        <span class="text-4xl" aria-hidden="true">{{ ['paid' => '📸', 'waiting' => '⏳', 'failed' => '⚠️'][$state] }}</span>
        <h2 class="font-display text-2xl font-semibold">{{ __("pages.camera.done_{$state}_title") }}</h2>
        <p class="text-sm text-ink-muted">{{ __("pages.camera.done_{$state}_body", ['tier' => $purchase->tier->label()]) }}</p>
        <a href="{{ $openUrl }}" class="mt-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.camera.open_album') }}</a>
    </section>
</x-layouts.customer>
