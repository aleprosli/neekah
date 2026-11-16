@php use App\Support\Ringgit; @endphp
<x-site.feature-page :title="__('pages.features.kenangan.name')" :explore="$explore">
    <x-slot:hero>
        <x-site.feature-hero icon="camera" :name="__('pages.features.kenangan.name')" :title="__('pages.landing.kenangan.title')" :lead="__('pages.landing.kenangan.body')">
            <x-site.button :href="route('camera.index')">{{ __('pages.landing.kenangan.cta') }}</x-site.button>
            <x-site.button href="#pakej" variant="secondary">{{ __('pages.features.kenangan.see_packages') }}</x-site.button>
        </x-site.feature-hero>
    </x-slot:hero>

    {{-- A phone plays the four steps a guest goes through (KenanganPhoneDemo.vue). --}}
    <x-site.feature-section id="cara" tone="raised" :eyebrow="__('pages.features.how_eyebrow')" :title="__('pages.features.kenangan.how_title')" :body="__('pages.features.kenangan.how_body')">
        <div class="mx-auto max-w-3xl" data-vue="kenangan-phone-demo" data-props="@vueProps(['steps' => $steps])">
            <ol class="flex flex-col">
                @foreach ($steps as $step)
                    <li class="relative flex min-w-0 items-start gap-4 pb-6 last:pb-0">
                        @unless ($loop->last)
                            <span class="absolute top-16 bottom-0 left-8 border-l-2 border-dashed border-gold-300" aria-hidden="true"></span>
                        @endunless
                        <span class="relative">
                            <x-site.kenangan-step :name="$step['key']" class="size-16" />
                            <span class="absolute -top-1 -right-1 flex size-6 items-center justify-center rounded-full bg-brand-600 font-display text-xs font-semibold text-white ring-2 ring-surface-raised">{{ $loop->iteration }}</span>
                        </span>
                        <span class="min-w-0 pt-2 text-sm"><span class="block font-semibold">{{ $step['title'] }}</span> <span class="text-ink-muted">{{ $step['body'] }}</span></span>
                    </li>
                @endforeach
            </ol>
        </div>
    </x-site.feature-section>

    <x-site.feature-section id="pakej" :eyebrow="__('pages.features.packages_eyebrow')" :title="__('pages.features.kenangan.packages_title')">
        <div class="mx-auto grid max-w-4xl gap-6 sm:grid-cols-2">
            @foreach ($tiers as $tier)
                <article @class([
                    'relative flex min-w-0 flex-col gap-4 overflow-hidden rounded-3xl border p-6 sm:p-8',
                    'border-brand-300 bg-linear-to-b from-brand-50 to-surface-raised shadow-lg shadow-brand-900/5' => $tier['pro'],
                    'border-line bg-surface-raised' => ! $tier['pro'],
                ])>
                    <div>
                        <h3 class="font-display text-2xl font-semibold">{{ $tier['label'] }}</h3>
                        <p class="mt-1 font-display text-3xl font-semibold">{{ Ringgit::format($tier['price']) }}</p>
                        <p class="text-xs text-ink-muted">{{ __('pages.landing.kenangan.per_album') }}</p>
                    </div>
                    <ul class="flex flex-col gap-2 text-sm">
                        @foreach ($tier['features'] as $feature)
                            <li class="flex items-start gap-2"><x-nav-icon name="check" class="mt-0.5 size-4 shrink-0 text-gold-600" /><span>{{ $feature }}</span></li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </x-site.feature-section>

    <x-site.feature-cta :title="__('pages.features.kenangan.cta_title')" :body="__('pages.features.kenangan.cta_body')">
        <x-site.button :href="route('camera.index')">{{ __('pages.landing.kenangan.cta') }}</x-site.button>
    </x-site.feature-cta>
</x-site.feature-page>
