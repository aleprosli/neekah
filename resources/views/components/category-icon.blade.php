@props(['category' => null, 'slug' => null, 'fallback' => null, 'alt' => ''])

@php
    // A category shows what its admin uploaded, if anything; a bare slug is one
    // of the tiles drawn from the shipped illustrations, and a null slug with no
    // category is the "all categories" tile.
    $uploaded = filled($category?->image) ? $category->illustrationUrl() : null;
    $slug ??= $category?->slug;
    $fallback ??= $category?->icon;
    $file = $uploaded ? null : ($slug === null ? 'all' : (App\Models\Category::ILLUSTRATIONS[$slug] ?? null));
@endphp

@if ($uploaded)
    <img src="{{ $uploaded }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->merge(['class' => 'object-contain']) }}>
@elseif ($file)
    <img src="{{ asset('img/icon/'.$file.'.svg') }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->merge(['class' => 'object-contain mix-blend-multiply']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center leading-none']) }} aria-hidden="true">{{ $fallback }}</span>
@endif
