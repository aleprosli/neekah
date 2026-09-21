@props(['state'])

@php($url = App\Support\States::flagUrl($state))

@if ($url)
    <img src="{{ $url }}" alt="" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'inline-block h-3.5 w-5 shrink-0 rounded-[2px] object-cover align-[-0.15em] ring-1 ring-black/10']) }}>
@endif
