@props(['template'])

@php
    $layout = $template->layout();
    $ornament = $template->ornament();
    // Photo-led covers push the names into the lower half of the card.
    $lower = in_array($layout, ['banner', 'split', 'mosaic', 'arch'], true);
@endphp

{{-- A miniature of the cover, drawn from the same JSON as the real card: same
     paper, same double rule, same ornament and faces. Everything decorative sits
     in its own band, so nothing is ever drawn over the names. --}}
<span class="nk-card nk-paper @container relative flex aspect-[3/4] w-full flex-col items-center overflow-hidden text-center" style="{{ $template->cssVariables() }}">
    <span class="absolute inset-[5px] z-[1] border" style="border-color: color-mix(in oklab, var(--nk-accent) 50%, transparent)"></span>

    @if ($ornament !== 'none')
        @include('sites.ornaments.'.$ornament, ['position' => 'top-left', 'ornamentSize' => 'w-[30%]'])
        @include('sites.ornaments.'.$ornament, ['position' => 'bottom-right', 'ornamentSize' => 'w-[30%]'])
    @endif

    {{-- The picture band --}}
    @if ($layout === 'arch')
        <span class="nk-arch nk-photo-fallback relative mt-[12%] block aspect-[3/4] w-[38%] shrink-0" style="border: 1px solid var(--nk-accent)"></span>
    @elseif ($layout === 'banner')
        <span class="nk-photo-fallback relative block h-[44%] w-full shrink-0" style="mask-image: linear-gradient(to bottom, #000 60%, transparent)"></span>
    @elseif ($layout === 'split')
        <span class="relative mt-[12%] block aspect-[4/5] w-[36%] shrink-0">
            <span class="absolute inset-0 translate-x-1 translate-y-1" style="border: 1px solid var(--nk-accent)"></span>
            <span class="nk-photo-fallback relative block size-full"></span>
        </span>
    @elseif ($layout === 'mosaic')
        <span class="relative mt-[12%] grid h-[34%] w-[64%] shrink-0 grid-cols-3 grid-rows-2 gap-0.5">
            <span class="nk-photo-fallback col-span-2 row-span-2"></span>
            <span class="nk-photo-fallback"></span>
            <span class="nk-photo-fallback"></span>
        </span>
    @endif

    {{-- The words --}}
    <span @class([
        'relative z-[2] flex w-full flex-1 flex-col items-center justify-center px-[9cqw]',
        'pb-[14%]' => ! $lower,
        'pb-[10%] pt-2' => $lower,
    ])>
        @if (! $lower && $template->showsBismillah())
            <span class="nk-arabic block text-[6cqw] leading-none" lang="ar" dir="rtl">بِسْمِ ٱللَّٰهِ</span>
        @endif
        @if (in_array($layout, ['centered', 'ribbon'], true))
            <span class="nk-monogram mt-[4cqw] size-[16cqw] text-[6.5cqw]" style="box-shadow: 0 0 0 2px var(--nk-page), 0 0 0 3px color-mix(in oklab, var(--nk-accent) 35%, transparent)">AH</span>
        @endif
        <span class="nk-muted mt-[4cqw] block max-w-full truncate text-[3.6cqw] tracking-[0.25em] uppercase">{{ $template->eyebrow() }}</span>

        @if ($layout === 'ribbon')
            <span class="nk-ribbon nk-script -mx-[9cqw] mt-[4cqw] block w-[calc(100%+18cqw)] py-[3cqw] text-[9.5cqw] leading-tight">Aina &amp; Hakim</span>
        @elseif ($layout === 'minimal')
            <span class="nk-name mt-[4cqw] block text-[8.5cqw] leading-tight font-light">Aina<br><span class="nk-script nk-accent text-[7cqw]">&amp;</span><br>Hakim</span>
        @elseif ($layout === 'frame')
            <span class="nk-plaque mt-[4cqw] block w-full px-[4cqw] py-[4cqw]">
                <span class="nk-script nk-name block text-[10.5cqw] leading-none">Aina</span>
                <span class="nk-script nk-accent block text-[6cqw] leading-none">&amp;</span>
                <span class="nk-script nk-name block text-[10.5cqw] leading-none">Hakim</span>
            </span>
        @else
            <span class="nk-script nk-name mt-[3cqw] block text-[12cqw] leading-none">Aina</span>
            <span class="nk-script nk-accent block text-[7cqw] leading-none">&amp;</span>
            <span class="nk-script nk-name block text-[12cqw] leading-none">Hakim</span>
        @endif

        <span class="nk-muted mt-[4cqw] flex items-center gap-[3cqw] text-[4.2cqw] tracking-[0.2em] uppercase">
            <span>Sabtu</span><span class="nk-hairline border-x px-[3cqw] text-[5.5cqw]" style="color: var(--nk-name)">20</span><span>Dis 2026</span>
        </span>
    </span>
</span>
