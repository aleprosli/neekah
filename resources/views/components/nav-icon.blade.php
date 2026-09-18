@props(['name'])

@php
    /**
     * The line icons every dashboard sidebar draws from. Emoji used to stand in
     * here, but each operating system draws its own, at its own weight and
     * size, so a sidebar never looked like one set. These match the mobile
     * navigation: a 24px box, stroked in currentColor, round caps.
     *
     * An unknown name renders a marked placeholder, which NavIconTest fails on.
     */
    $paths = [
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-6M22 20H2"/>',
        'trending' => '<path d="M3 17l6-6 4 4 7-7"/><path d="M14 8h6v6"/>',
        'store' => '<path d="M3 9.5 5 4h14l2 5.5"/><path d="M4 9.5h16V20H4z"/><path d="M9 20v-5h6v5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.5a3.5 3.5 0 0 1 0 7"/><path d="M17.5 14.5A6.5 6.5 0 0 1 21.5 20"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'alert' => '<path d="M12 4 2.5 20h19L12 4Z"/><path d="M12 10v4M12 17h.01"/>',
        'wallet' => '<path d="M3 7h15a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7Z"/><path d="M3 7a2 2 0 0 1 2-2h11"/><path d="M17 13h.01"/>',
        'pencil' => '<path d="M4 20h4L20 8l-4-4L4 16v4Z"/><path d="m14 6 4 4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.2 5.2l2.1 2.1M16.7 16.7l2.1 2.1M18.8 5.2l-2.1 2.1M7.3 16.7l-2.1 2.1"/>',
        'pulse' => '<path d="M2 12h4l3 7 4-14 3 7h6"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
        'image' => '<path d="M3 5h18v14H3z"/><circle cx="8.5" cy="10" r="1.5"/><path d="m4 18 5-5 4 4 3-3 4 4"/>',
        'calendar' => '<path d="M4 6h16v14H4z"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4V5Z"/><path d="M9 10h6"/>',
        'trophy' => '<path d="M8 4h8v5a4 4 0 0 1-8 0V4Z"/><path d="M8 5H5v2a3 3 0 0 0 3 3M16 5h3v2a3 3 0 0 1-3 3"/><path d="M12 13v4M9 20h6"/>',
        'rings' => '<circle cx="9" cy="14" r="5.5"/><circle cx="16" cy="14" r="5.5"/><path d="m9 5 1.5 2.5h-3L9 5Z"/>',
        'check' => '<path d="M4 5h16v15H4z"/><path d="m8 12 3 3 5-6"/>',
        'mail' => '<path d="M3 6h18v12H3z"/><path d="m3 7 9 6 9-6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-4 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" @unless (isset($paths[$name])) data-icon-missing="{{ $name }}" @endunless>
    @isset($paths[$name])
        {!! $paths[$name] !!}
    @else
        <circle cx="12" cy="12" r="8"/>
    @endisset
</svg>
