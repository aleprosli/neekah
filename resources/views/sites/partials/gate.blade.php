@props(['site', 'template', 'eyebrow', 'guest' => null])

{{-- The sealed envelope. Guests break the seal to open the card, the way a
     printed kad is taken out of its sampul. --}}
<div data-gate class="nk-card nk-paper fixed inset-0 z-50 flex flex-col items-center justify-center overflow-hidden px-6 text-center" style="{{ $template->cssVariables() }}">
    @include('sites.ornaments.'.$template->ornament(), ['position' => 'top-left'])
    @include('sites.ornaments.'.$template->ornament(), ['position' => 'bottom-right'])

    <div class="relative flex w-full max-w-xs flex-col items-center">
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        @if ($guest)
            <p class="nk-name mt-3 text-base font-medium">Kepada {{ $guest['name'] }}</p>
        @endif

        <div class="relative mt-8 w-full" style="perspective: 900px">
            <div class="nk-envelope aspect-[4/3] w-full overflow-visible rounded-md">
                {{-- The lower pocket, folded in from both sides. --}}
                <svg class="absolute inset-0 size-full" viewBox="0 0 400 300" preserveAspectRatio="none" fill="none" aria-hidden="true">
                    <path d="M0 300 200 150 400 300" stroke="var(--nk-line)" stroke-width="1.5"/>
                    <path d="M0 0 170 170M400 0 230 170" stroke="var(--nk-line)" stroke-width="1"/>
                </svg>
            </div>

            <svg class="nk-envelope-flap absolute inset-x-0 top-0 h-[58%] w-full" viewBox="0 0 400 174" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 0h400L200 174Z" fill="var(--nk-panel)" stroke="var(--nk-line)" stroke-width="1.5"/>
                <path d="M14 0 200 160 386 0" fill="none" stroke="var(--nk-accent)" stroke-opacity=".45" stroke-width="1"/>
            </svg>

            <button type="button" data-gate-open class="nk-seal absolute top-[58%] left-1/2 flex size-20 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full text-3xl transition hover:scale-105" aria-label="Buka kad">
                {{ $site->initials() }}
            </button>
        </div>

        <p class="nk-script nk-name mt-10 text-4xl leading-tight">{{ $site->bride_name }} <span class="nk-accent block text-2xl">&amp;</span> {{ $site->groom_name }}</p>
        <p class="nk-muted mt-5 text-xs tracking-[0.3em] uppercase">{{ $site->event_date->translatedFormat('j F Y') }}</p>
        <button type="button" data-gate-open class="nk-button mt-6 inline-flex items-center gap-2 rounded-full px-8 py-3 text-sm font-medium tracking-wide shadow-lg">
            Buka kad
        </button>
    </div>
</div>
