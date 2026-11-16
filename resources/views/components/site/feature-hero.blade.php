@props([
    /** components/nav-icon name shown in the chip above the headline. */
    'icon',
    /** The feature's name, in the chip and the breadcrumb. */
    'name',
    'title',
    'lead',
])

{{-- One hero for every feature page: back to About, the feature's chip, the
     headline, a floral rule, the lead and the page's own actions (the slot). --}}
<section class="relative overflow-hidden">
    <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="pointer-events-none absolute -bottom-24 -left-28 w-80 opacity-25 sm:w-[30rem]" decoding="async">
    <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="pointer-events-none absolute -top-32 -right-36 w-80 scale-x-[-1] opacity-10 sm:w-[28rem]" decoding="async">

    <div class="relative mx-auto flex max-w-4xl flex-col items-center gap-6 px-4 pt-28 pb-16 text-center sm:px-6 lg:pt-36 lg:pb-20">
        <nav class="text-sm text-ink-muted" aria-label="{{ __('pages.features.breadcrumb') }}">
            <a href="{{ route('landing') }}" class="transition hover:text-brand-700">{{ __('pages.features.menu.about') }}</a>
            <span class="px-1.5 text-gold-500" aria-hidden="true">/</span>
            <span class="text-ink">{{ $name }}</span>
        </nav>

        <span class="inline-flex items-center gap-2 rounded-full border border-gold-300 bg-surface-raised px-4 py-1.5 text-sm font-semibold text-brand-700 shadow-sm">
            <x-nav-icon :name="$icon" class="size-4" />
            {{ $name }}
        </span>

        <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">{{ $title }}</h1>

        <x-site.ornament name="divider-floral" class="h-6 w-44" color="var(--color-gold-500)" color2="var(--color-gold-300)" />

        <p class="max-w-2xl text-lg text-ink-muted text-pretty">{{ $lead }}</p>

        @if ($slot->isNotEmpty())
            <div class="flex w-full flex-col items-center justify-center gap-3 sm:w-auto sm:flex-row">
                {{ $slot }}
            </div>
        @endif

        @isset($note)
            <p class="text-sm text-ink-muted">{{ $note }}</p>
        @endisset
    </div>
</section>
