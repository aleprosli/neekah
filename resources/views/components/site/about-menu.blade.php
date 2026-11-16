@props([
    /** The phone sheet: About links as small pills (plus Blog, which the phone header has no room for), features in one column. */
    'compact' => false,
])

@php
    // Only features on sale are listed (FeaturePages), grouped by who they are for.
    $featureGroups = collect(app(App\Support\FeaturePages::class)->available())->groupBy('audience');
@endphp

{{-- What the "Tentang" menu holds: the header dropdown on a wide screen and
     the sheet behind the phone bottom bar, so both always list the same pages. --}}
<div {{ $attributes }}>
    @if ($compact)
        <div class="flex flex-wrap gap-2 px-1">
            <a href="{{ route('landing') }}" @class(['rounded-full border px-3 py-1.5 text-sm font-semibold', 'border-brand-300 bg-brand-50 text-brand-700' => App\Support\Locales::routeIs('landing'), 'border-line' => ! App\Support\Locales::routeIs('landing')])>{{ __('pages.features.menu.about') }}</a>
            <a href="{{ route('landing') }}#cara" class="rounded-full border border-line px-3 py-1.5 text-sm font-semibold">{{ __('nav.how_it_works') }}</a>
            <a href="{{ route('blog.index') }}" @class(['rounded-full border px-3 py-1.5 text-sm font-semibold', 'border-brand-300 bg-brand-50 text-brand-700' => App\Support\Locales::routeIs('blog.*'), 'border-line' => ! App\Support\Locales::routeIs('blog.*')])>{{ __('nav.blog') }}</a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-1">
            <a href="{{ route('landing') }}" class="flex flex-col rounded-2xl px-3 py-2.5 hover:bg-surface-muted">
                <span class="font-semibold">{{ __('pages.features.menu.about') }}</span>
                <span class="text-xs font-normal text-ink-muted">{{ __('pages.features.menu.about_body') }}</span>
            </a>
            <a href="{{ route('landing') }}#cara" class="flex flex-col rounded-2xl px-3 py-2.5 hover:bg-surface-muted">
                <span class="font-semibold">{{ __('nav.how_it_works') }}</span>
                <span class="text-xs font-normal text-ink-muted">{{ __('pages.features.menu.how_body') }}</span>
            </a>
        </div>
    @endif

    @if ($featureGroups->isNotEmpty())
        <div @class(['mt-3 grid gap-x-1 gap-y-3 border-t border-line pt-3', 'grid-cols-1 sm:grid-cols-2' => $compact, 'grid-cols-2' => ! $compact])>
            @foreach (['couples', 'vendors'] as $audience)
                @if ($featureGroups->has($audience))
                    <div class="flex min-w-0 flex-col gap-1">
                        <p class="px-3 text-[10px] font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ __('pages.features.menu.'.$audience) }}</p>
                        @foreach ($featureGroups[$audience] as $page)
                            <a href="{{ $page['url'] }}" @class(['flex items-start gap-3 rounded-2xl px-3 py-2.5 text-left hover:bg-surface-muted', 'bg-surface-muted' => App\Support\Locales::routeIs($page['route'])])>
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-gold-300">
                                    <x-nav-icon :name="$page['icon']" class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-ink">{{ $page['title'] }}</span>
                                    <span class="block text-xs font-normal text-ink-muted">{{ $page['tagline'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
