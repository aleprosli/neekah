@props(['class' => ''])

@php
    use App\Support\Locales;
    $alternates = Locales::alternates();
    $current = Locales::current();
@endphp

{{-- A page with no counterpart (an error page) has nothing to switch to. --}}
@if (count($alternates) > 1)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-0.5 rounded-full border border-line p-0.5 text-xs font-semibold '.$class]) }}>
        @foreach ($alternates as $code => $href)
            <a
                href="{{ $href }}"
                hreflang="{{ Locales::hreflang($code) }}"
                @class([
                    'rounded-full px-2.5 py-1 transition',
                    'bg-brand-600 text-white' => $code === $current,
                    'text-ink-muted hover:text-ink' => $code !== $current,
                ])
                @if ($code === $current) aria-current="true" @endif
                title="{{ Locales::label($code) }}"
            >{{ strtoupper($code) }}</a>
        @endforeach
    </div>
@endif
