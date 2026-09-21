@props(['site', 'template', 'preview' => false, 'guest' => null, 'sections' => null])

@php
    $ornament = $template->ornament();
    $eyebrow = $template->eyebrow();
    $sections ??= App\Support\CardSections::resolve($site->sections);
@endphp

{{-- One sheet of card stock with a double rule round its edge. On a phone the
     sheet is the screen; from sm up it lies on a darker "table" like a real kad. --}}
<div data-card-root class="nk-card nk-table relative min-h-screen overflow-hidden" style="{{ $template->cssVariables() }}">
    @include('sites.partials.motion', ['template' => $template])

    @unless ($preview)
        @include('sites.partials.gate', ['site' => $site, 'template' => $template, 'eyebrow' => $eyebrow, 'guest' => $guest])
    @endunless

    <div data-card class="nk-sheet nk-paper relative z-10 mx-auto max-w-md overflow-hidden pb-28">
        <div class="nk-sheet-frame"></div>

        {{-- The drawn piece at the head of the sheet, behind the hero. It is
             what stops a kad reading as a web page, so it is laid on the paper
             itself rather than inside one layout. --}}
        @if ($template->artwork() && $template->artwork() !== 'none')
            <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-[100svh] overflow-hidden">
                @include('sites.artwork.'.$template->artwork())
            </div>
        @endif

        @include('sites.layouts.'.$template->layout(), ['site' => $site, 'template' => $template, 'preview' => $preview, 'ornament' => $ornament, 'eyebrow' => $eyebrow])

        <div class="relative z-[2] px-8 sm:px-10">
            {{-- The couple's own arrangement. Each partial keeps its own guard,
                 so a section that is on but has nothing to say prints nothing. --}}
            @foreach ($sections as $section)
                @include('sites.sections.'.$section, ['site' => $site, 'preview' => $preview, 'guest' => $guest])
            @endforeach

            {{-- The signature closes every kad, wherever the rest was put. --}}
            @include('sites.sections.closing', ['site' => $site])
        </div>
    </div>

    @include('sites.partials.dock', ['site' => $site, 'preview' => $preview])
</div>
