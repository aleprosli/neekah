@props(['slug' => null, 'fallback' => null, 'alt' => ''])

@php
    /**
     * Illustration file per category slug; a null slug is the "all categories" tile.
     * Pelamin and Decoration share the floral-arch drawing.
     */
    $files = [
        null => 'all',
        'catering' => 'catering',
        'pelamin' => 'decoration',
        'decoration' => 'decoration',
        'photography' => 'photography',
        'videography' => 'videography',
        'emcee' => 'emcee',
        'makeup' => 'makeup',
        'bridal' => 'bridal',
        'venue' => 'venue',
        'cake' => 'cake',
        'entertainment' => 'entertainment',
        'invitation' => 'invitation',
    ];
    $file = $files[$slug] ?? null;
@endphp

@if ($file)
    <img src="{{ asset('img/icon/'.$file.'.svg') }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->merge(['class' => 'object-contain mix-blend-multiply']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center leading-none']) }} aria-hidden="true">{{ $fallback }}</span>
@endif
