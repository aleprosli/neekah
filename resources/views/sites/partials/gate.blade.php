@props(['site', 'palette'])

{{-- The sealed card. Guests tap to open, the way a physical kad is opened. --}}
<div data-gate class="fixed inset-0 z-50 flex flex-col items-center justify-center overflow-hidden px-8 text-center {{ $palette['gate'] }}">
    @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'top-left'])
    @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'bottom-right'])

    <div class="relative">
        <p class="text-[11px] tracking-[0.42em] uppercase {{ $palette['gateMuted'] }}">Walimatulurus</p>

        <p class="mt-8 leading-none" style="font-family: var(--font-script)">
            <span class="block text-4xl sm:text-5xl {{ $palette['gateName'] }}">{{ $site->bride_name }}</span>
            <span class="my-3 block text-2xl {{ $palette['gateAmp'] }}">&amp;</span>
            <span class="block text-4xl sm:text-5xl {{ $palette['gateName'] }}">{{ $site->groom_name }}</span>
        </p>

        <div class="mt-8">
            @include('sites.partials.divider', ['tone' => $palette['floral'], 'accent' => $palette['accent']])
        </div>

        <p class="mt-6 text-sm tracking-[0.2em] uppercase {{ $palette['gateMuted'] }}">{{ $site->event_date->translatedFormat('j F Y') }}</p>

        <button type="button" data-gate-open class="nk-glow mt-10 inline-flex items-center gap-2 rounded-full px-8 py-3.5 text-sm font-medium tracking-wide {{ $palette['gateButton'] }}">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5 12 14l9-5.5"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
            Buka kad
        </button>
    </div>
</div>
