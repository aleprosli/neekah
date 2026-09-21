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
                {{-- navigation.js swaps <main> and the nav regions and keeps
                     everything else, but changing language changes the whole
                     document: the lang attribute, the strings the browser was
                     given for Vue, the sidebar, the header. So this one link
                     asks for an ordinary page load. --}}
                data-no-swap
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
