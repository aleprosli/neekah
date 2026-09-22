@props([
    /** A file in public/img/layers, without the extension. */
    'name',
    /** Any CSS colour; the artwork is a mask and this is what fills it. */
    'color' => 'var(--color-brand-200)',
    'color2' => null,
])

{{-- The same tinted SVG ornaments the invitation cards are drawn with, so the
     site wears the florals of its own stationery. The artwork is the mask and
     the colour is a gradient, exactly as resources/js/card/layerStyle.js does it. --}}
<div
    {{ $attributes->class(['pointer-events-none select-none']) }}
    aria-hidden="true"
    style="background:linear-gradient(135deg,{{ $color }},{{ $color2 ?? $color }});-webkit-mask:url('{{ asset('img/layers/'.$name.'.svg') }}') center/contain no-repeat;mask:url('{{ asset('img/layers/'.$name.'.svg') }}') center/contain no-repeat;{{ $attributes->get('style') }}"
></div>
