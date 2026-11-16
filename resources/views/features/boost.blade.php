@php
    use App\Support\Ringgit;

    $isVendor = auth()->user()?->isVendor() ?? false;
    $primaryUrl = $isVendor ? route('vendor.boost.index') : route('vendor.register');
    $primaryLabel = $isVendor ? __('pages.features.boost.use') : __('pages.landing.daftar_sebagai_vendor');
@endphp
<x-site.feature-page :title="__('pages.features.boost.name')" :explore="$explore">
    <x-slot:hero>
        <x-site.feature-hero icon="rocket" :name="__('pages.features.boost.name')" :title="__('pages.landing.boost.title')" :lead="__('pages.landing.boost.body')">
            <x-site.button :href="$primaryUrl">{{ $primaryLabel }}</x-site.button>
            <x-site.button href="#cara" variant="secondary">{{ __('pages.features.boost.see_how') }}</x-site.button>
        </x-site.feature-hero>
    </x-slot:hero>

    <x-site.feature-section id="cara" tone="raised" :eyebrow="__('pages.features.how_eyebrow')" :title="__('pages.features.boost.how_title')">
        <ol class="mx-auto grid max-w-5xl gap-5 sm:grid-cols-3">
            @foreach (['category', 'token', 'top'] as $step)
                <li class="relative flex min-w-0 flex-col gap-3 rounded-3xl border border-line bg-ivory p-6">
                    <span class="flex size-10 items-center justify-center rounded-full border border-gold-400 bg-brand-50 font-display text-sm font-semibold text-brand-700">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="font-display text-lg font-semibold">{{ __('pages.features.boost.steps.'.$step.'.title') }}</h3>
                    <p class="text-sm text-ink-muted">{{ __('pages.features.boost.steps.'.$step.'.body') }}</p>
                </li>
            @endforeach
        </ol>

        <p class="mx-auto mt-8 flex max-w-3xl items-start gap-3 rounded-2xl bg-brand-50 p-5 text-sm text-brand-900/80">
            <x-nav-icon name="check" class="mt-0.5 size-4 shrink-0 text-gold-600" />
            <span>{{ __('pages.landing.boost.fair') }}</span>
        </p>
    </x-site.feature-section>

    <x-site.feature-section id="token" :eyebrow="__('pages.features.boost.sources_eyebrow')" :title="__('pages.features.boost.sources_title')">
        <ul class="mx-auto grid max-w-5xl gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <li class="flex min-w-0 flex-col gap-2 rounded-3xl border border-line bg-surface-raised p-6">
                <p class="text-[10px] font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ __('pages.features.boost.source_welcome') }}</p>
                <p class="font-display text-3xl font-semibold">{{ $welcome }} <span class="text-base font-normal text-ink-muted">{{ __('pages.features.boost.tokens') }}</span></p>
                <p class="text-sm text-ink-muted">{{ __('pages.landing.boost.welcome', ['count' => $welcome]) }}</p>
            </li>
            @if ($proMonthly !== null)
                <li class="relative flex min-w-0 flex-col gap-2 overflow-hidden rounded-3xl bg-linear-to-br from-ink via-brand-900 to-ink p-6 text-surface ring-1 ring-gold-300/60">
                    <p class="text-[10px] font-semibold tracking-[0.2em] text-gold-300 uppercase">Neekah Pro</p>
                    <p class="font-display text-3xl font-semibold">{{ $proMonthly }} <span class="text-base font-normal text-surface/70">{{ __('pages.features.boost.tokens_month') }}</span></p>
                    <p class="text-sm text-surface/75">{{ __('pages.landing.boost.pro', ['count' => $proMonthly]) }}</p>
                    <a href="{{ route('features.pro') }}" class="mt-auto pt-2 text-sm font-semibold text-gold-300 hover:underline">{{ __('pages.features.pro.name') }} <span aria-hidden="true">→</span></a>
                </li>
            @endif
            @foreach ($packs as $pack)
                <li class="flex min-w-0 flex-col gap-2 rounded-3xl border border-line bg-surface-raised p-6">
                    <p class="text-[10px] font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ __('pages.features.boost.source_pack') }}</p>
                    <p class="font-display text-3xl font-semibold">{{ $pack['tokens'] }} <span class="text-base font-normal text-ink-muted">{{ __('pages.features.boost.tokens') }}</span></p>
                    <p class="text-sm text-ink-muted">{{ __('pages.landing.boost.pack', ['count' => $pack['tokens'], 'price' => ltrim(Ringgit::format($pack['price']), 'RM')]) }}</p>
                </li>
            @endforeach
        </ul>
    </x-site.feature-section>

    <x-site.feature-cta :title="__('pages.features.boost.cta_title')" :body="__('pages.features.boost.cta_body')">
        <x-site.button :href="$primaryUrl">{{ $primaryLabel }}</x-site.button>
    </x-site.feature-cta>
</x-site.feature-page>
