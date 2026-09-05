@props(['site', 'template', 'eyebrow'])

{{-- The sealed card. Guests tap to open, the way a physical kad is opened. --}}
<div data-gate class="nk-card fixed inset-0 z-50 flex flex-col items-center justify-center overflow-hidden px-8 text-center" style="{{ $template->cssVariables() }}">
    @include('sites.ornaments.'.$template->ornament(), ['position' => 'top-left'])
    @include('sites.ornaments.'.$template->ornament(), ['position' => 'bottom-right'])

    <div class="relative">
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        <p class="nk-script mt-8">
            <span class="nk-name block text-4xl sm:text-5xl">{{ $site->bride_name }}</span>
            <span class="nk-accent my-3 block text-2xl">&amp;</span>
            <span class="nk-name block text-4xl sm:text-5xl">{{ $site->groom_name }}</span>
        </p>
        @include('sites.partials.divider-css', ['class' => 'mt-8'])
        <p class="nk-muted mt-6 text-sm tracking-[0.2em] uppercase">{{ $site->event_date->translatedFormat('j F Y') }}</p>
        <button type="button" data-gate-open class="nk-button nk-glow mt-10 inline-flex items-center gap-2 rounded-full px-8 py-3.5 text-sm font-medium tracking-wide shadow-lg">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5 12 14l9-5.5"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
            Buka kad
        </button>
    </div>
</div>
