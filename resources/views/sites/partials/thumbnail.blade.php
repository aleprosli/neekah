@props(['template'])

@php $d = $template->design; $p = $d['palette'] ?? []; $layout = $template->layout(); @endphp

{{-- A miniature of the real design, drawn from the same JSON. --}}
<span class="relative flex aspect-[3/4] w-full items-center justify-center overflow-hidden rounded-2xl"
      style="{{ $template->cssVariables() }}; background: var(--nk-page)">

    @if ($layout === 'frame')
        <span class="absolute inset-3 rounded-lg" style="border: 1px solid var(--nk-line); outline: 1px solid var(--nk-accent); outline-offset: 4px"></span>
    @elseif ($layout === 'arch')
        <span class="nk-arch absolute top-5 h-[58%] w-[52%]" style="background: var(--nk-panel); border: 1px solid var(--nk-accent)"></span>
    @elseif ($layout === 'banner')
        <span class="absolute inset-x-0 top-0 h-[62%]" style="background: var(--nk-panel)"></span>
    @elseif ($layout === 'split')
        <span class="absolute inset-x-5 top-5 h-[38%] rounded" style="background: var(--nk-panel); border: 1px solid var(--nk-line)"></span>
    @elseif ($layout === 'ribbon')
        <span class="absolute inset-x-0 top-[42%] h-8" style="background: var(--nk-accent)"></span>
    @elseif ($layout === 'mosaic')
        <span class="absolute inset-x-4 top-4 grid h-[45%] grid-cols-3 grid-rows-2 gap-1">
            @for ($i = 0; $i < 5; $i++)
                <span @class(['rounded-sm', 'col-span-2 row-span-2' => $i === 0]) style="background: var(--nk-panel); border: 1px solid var(--nk-line)"></span>
            @endfor
        </span>
    @endif

    @if (! in_array($template->ornament(), ['none'], true))
        <span class="absolute top-1 left-1 w-14 opacity-70">@include('sites.ornaments.'.$template->ornament(), ['position' => 'top-left'])</span>
    @endif

    <span class="relative px-4 text-center" style="{{ $layout === 'banner' || $layout === 'mosaic' || $layout === 'split' ? 'margin-top: 42%;' : '' }}">
        <span class="nk-eyebrow block text-[7px]">{{ $template->eyebrow() }}</span>
        <span class="nk-script nk-name mt-1.5 block text-xl leading-tight">Aina<br><span class="nk-accent text-sm">&amp;</span><br>Hakim</span>
        <span class="nk-muted mt-1.5 block text-[8px] tracking-[0.2em]">20 . 12 . 2026</span>
    </span>
</span>
