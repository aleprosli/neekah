@php
    use App\Support\Ringgit;

    // A vendor already signed in goes straight to subscribing; anyone else
    // signs up first, since Pro sits on top of a free listing.
    $isVendor = auth()->user()?->isVendor() ?? false;
    $primaryUrl = $isVendor ? route('vendor.pro.index') : route('vendor.register');
    $primaryLabel = $isVendor ? __('pages.features.pro.subscribe') : __('pages.landing.daftar_sebagai_vendor');
@endphp
<x-site.feature-page :title="__('pages.features.pro.name')" :explore="$explore">
    <x-slot:hero>
        <x-site.feature-hero icon="crown" :name="__('pages.features.pro.name')" :title="__('pages.landing.plans.title')" :lead="__('pages.landing.plans.body')">
            <x-site.button :href="$primaryUrl">{{ $primaryLabel }}</x-site.button>
            <x-site.button href="#pelan" variant="secondary">{{ __('pages.features.pro.see_plans') }}</x-site.button>
        </x-site.feature-hero>
    </x-slot:hero>

    {{-- resources/js/components/vendor/VendorProShowcase.vue: the same story as /vendor/pro, Pro Elite (#elite) inside it. --}}
    <x-site.feature-section id="cerita" tone="raised">
        <div data-vue="vendor-pro-showcase" data-props="@vueProps($proStory)">
            <ul class="grid gap-4 sm:grid-cols-2">
                @foreach ($proStory['benefits'] as $benefit)
                    <li class="rounded-2xl border border-line bg-ivory p-5">
                        <p class="font-semibold">{{ $benefit['title'] }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $benefit['body'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-site.feature-section>

    <x-site.feature-section id="pelan" :eyebrow="__('pages.features.packages_eyebrow')" :title="__('pages.features.pro.plans_title')" :body="__('pages.features.pro.plans_body')">
        <div class="mx-auto grid max-w-5xl min-w-0 gap-6 lg:grid-cols-2">
            <article class="flex min-w-0 flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-6 sm:p-8">
                <div>
                    <h3 class="font-display text-2xl font-semibold">Basic</h3>
                    <p class="mt-1 font-display text-3xl font-semibold">{{ __('pages.landing.plans.free') }}</p>
                    <p class="text-sm text-ink-muted">{{ __('pages.landing.plans.basic_body') }}</p>
                </div>
                <ul class="flex flex-col gap-3 text-sm">
                    @foreach ($plans['basic'] as $feature)
                        <li class="flex items-start gap-2"><x-nav-icon name="check" class="mt-0.5 size-4 shrink-0 text-gold-600" /><span><span class="font-semibold">{{ $feature['label'] }}</span> <span class="text-ink-muted">· {{ $feature['description'] }}</span></span></li>
                    @endforeach
                </ul>
            </article>

            {{-- What each Pro feature does is told in the story above; here it is the price and the list. --}}
            <article class="relative flex min-w-0 flex-col gap-4 overflow-hidden rounded-3xl bg-linear-to-br from-ink via-brand-900 to-ink p-6 text-surface ring-1 ring-gold-300/60 sm:p-8">
                <span class="pointer-events-none absolute -top-20 -right-16 size-64 rounded-full bg-gold-300/15 blur-3xl" aria-hidden="true"></span>
                <div class="relative">
                    <h3 class="flex items-center gap-2 font-display text-2xl font-semibold">Neekah Pro <x-vendors.pro-badge /></h3>
                    <p class="mt-1 font-display text-3xl font-semibold">{{ Ringgit::format($plans['monthly']) }}<span class="text-base font-normal text-surface/70"> {{ __('pages.landing.plans.per_month') }}</span></p>
                    <p class="text-sm text-surface/70">{{ __('pages.landing.plans.pro_yearly', ['price' => Ringgit::format($plans['yearly'])]) }}</p>
                </div>
                <p class="relative text-sm font-medium text-gold-300">{{ __('pages.landing.plans.pro_includes') }}</p>
                <ul class="relative grid gap-x-4 gap-y-2.5 text-sm sm:grid-cols-2">
                    @foreach ($proStory['benefits'] as $benefit)
                        <li class="flex items-start gap-2"><x-nav-icon name="check" class="mt-0.5 size-4 shrink-0 text-gold-300" /><span>{{ $benefit['title'] }}</span></li>
                    @endforeach
                    @if ($plans['elite'] !== null)
                        <li class="flex items-start gap-2"><span class="mt-px w-4 shrink-0 text-center text-gold-300" aria-hidden="true">✦</span><a href="#elite" class="underline decoration-gold-300/50 underline-offset-4 hover:text-gold-300">{{ __('pages.landing.plans.pro_elite') }}</a></li>
                    @endif
                </ul>
                <p class="relative mt-auto text-xs text-surface/60">{{ __('pages.landing.plans.deposit_note') }}</p>
            </article>
        </div>
    </x-site.feature-section>

    <x-site.feature-cta :title="__('pages.features.pro.cta_title')" :body="__('pages.features.pro.cta_body')">
        <x-site.button :href="$primaryUrl">{{ $primaryLabel }}</x-site.button>
    </x-site.feature-cta>
</x-site.feature-page>
