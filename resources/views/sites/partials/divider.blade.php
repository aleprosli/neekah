@props(['tone' => '#c98da5', 'accent' => '#d9b06a'])

<svg viewBox="0 0 200 26" fill="none" aria-hidden="true" {{ $attributes->class(['mx-auto h-6 w-44 opacity-80']) }}>
    <path d="M6 13h58M136 13h58" stroke="{{ $tone }}" stroke-opacity=".6" stroke-width="1.2" stroke-linecap="round"/>
    <path d="M70 13c8-7 16-7 22 0-6 7-14 7-22 0ZM130 13c-8-7-16-7-22 0 6 7 14 7 22 0Z" fill="{{ $tone }}" fill-opacity=".45"/>
    <circle cx="100" cy="13" r="4.5" fill="{{ $accent }}"/>
    <circle cx="100" cy="13" r="8.5" stroke="{{ $accent }}" stroke-opacity=".45" stroke-width="1"/>
</svg>
