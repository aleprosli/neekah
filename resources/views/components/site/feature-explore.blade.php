@props([
    /** FeaturePages::available(): {key, url, icon, audience, title, tagline}. */
    'pages',
    'eyebrow',
    'title',
    'id' => null,
])

{{-- A card per feature page, the same on About and at the foot of each
     feature page, so the set always looks like one family. --}}
<x-site.feature-section :id="$id" :eyebrow="$eyebrow" :title="$title" tone="deep">
    <ul @class([
        'mx-auto grid gap-5',
        'max-w-md' => count($pages) === 1,
        'max-w-4xl sm:grid-cols-2' => count($pages) === 2,
        'sm:grid-cols-2 lg:grid-cols-3' => count($pages) === 3,
        'sm:grid-cols-2 lg:grid-cols-4' => count($pages) >= 4,
    ])>
        @foreach ($pages as $page)
            <li class="min-w-0">
                <a href="{{ $page['url'] }}" data-feature-card="{{ $page['key'] }}" class="group relative flex h-full flex-col gap-3 overflow-hidden rounded-3xl border border-line bg-surface-raised p-6 transition hover:-translate-y-0.5 hover:border-gold-400 hover:shadow-lg hover:shadow-brand-900/5">
                    <x-site.ornament name="corner-blossom" class="absolute -top-6 -right-6 size-28 opacity-0 transition group-hover:opacity-40" color="var(--color-brand-200)" />
                    <span class="text-[10px] font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ __('pages.features.menu.'.$page['audience']) }}</span>
                    <span class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-gold-300">
                        <x-nav-icon :name="$page['icon']" class="size-5" />
                    </span>
                    <span class="font-display text-lg font-semibold">{{ $page['title'] }}</span>
                    <span class="text-sm text-ink-muted">{{ $page['tagline'] }}</span>
                    <span class="mt-auto pt-2 text-sm font-semibold text-brand-600 transition group-hover:text-brand-700">{{ __('pages.features.explore.more') }} <span aria-hidden="true">→</span></span>
                </a>
            </li>
        @endforeach
    </ul>
</x-site.feature-section>
