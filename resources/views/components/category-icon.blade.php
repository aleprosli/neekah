@props(['slug' => null, 'fallback' => null, 'alt' => ''])

@php
    // A null slug is the "all categories" tile; the rest come from the model.
    $file = $slug === null ? 'all' : (App\Models\Category::ILLUSTRATIONS[$slug] ?? null);
@endphp

@if ($file)
    <img src="{{ asset('img/icon/'.$file.'.svg') }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->merge(['class' => 'object-contain mix-blend-multiply']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center leading-none']) }} aria-hidden="true">{{ $fallback }}</span>
@endif
