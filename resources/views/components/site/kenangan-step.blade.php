@props([
    /** One of scan, share, wish, keep: the Neekah Kenangan step it draws. */
    'name',
])

{{-- A small drawing of one Neekah Kenangan step on the About page, in the
     site's brand and gold, so a couple sees how the album works before reading it. --}}
<svg {{ $attributes->class(['shrink-0']) }} viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="32" cy="32" r="31" fill="var(--color-ivory)" stroke="var(--color-gold-400)" stroke-width="1.5" />
    @switch($name)
        @case('scan')
            {{-- The table card with its QR, and a phone reading it. --}}
            <path d="M14 46 22 18h14l8 28z" fill="#fff" stroke="var(--color-brand-600)" stroke-width="1.75" />
            <rect x="23" y="24" width="12" height="12" rx="1.5" fill="var(--color-brand-50)" stroke="var(--color-brand-600)" stroke-width="1.25" />
            <path d="M26 27h2v2h-2zM30 27h2v2h-2zM26 31h2v2h-2zM31 31h1v1h-1z" fill="var(--color-brand-600)" />
            <path d="M12 46h40" stroke="var(--color-gold-500)" stroke-width="1.75" />
            <rect x="39" y="20" width="12" height="20" rx="2.5" fill="var(--color-brand-600)" />
            <rect x="41" y="23" width="8" height="13" rx="1" fill="var(--color-brand-100)" />
            <path d="M35 28h3M35 32h3" stroke="var(--color-gold-500)" stroke-width="1.5" stroke-dasharray="1 2" />
            @break
        @case('share')
            {{-- A phone sending photos and a video up into the shared album. --}}
            <rect x="22" y="22" width="20" height="30" rx="3.5" fill="var(--color-brand-600)" />
            <rect x="24.5" y="26" width="15" height="21" rx="1.5" fill="#fff" />
            <circle cx="32" cy="36.5" r="4" stroke="var(--color-brand-600)" stroke-width="1.5" />
            <rect x="12" y="13" width="11" height="9" rx="1.5" transform="rotate(-12 17 17)" fill="var(--color-gold-300)" stroke="var(--color-gold-600)" stroke-width="1.25" />
            <path d="m13 20 3-3 2 2 2-2 2 2" transform="rotate(-12 17 17)" stroke="var(--color-gold-600)" stroke-width="1.1" />
            <rect x="41" y="11" width="11" height="9" rx="1.5" transform="rotate(10 46 15)" fill="var(--color-brand-100)" stroke="var(--color-brand-500)" stroke-width="1.25" />
            <path d="m45 13.5 3 2-3 2z" transform="rotate(10 46 15)" fill="var(--color-brand-500)" />
            <path d="M32 18v-6m-2.5 2.5L32 12l2.5 2.5" stroke="var(--color-gold-500)" stroke-width="1.75" />
            @break
        @case('wish')
            {{-- A sealed wish for the couple, and the voice note Pro adds. --}}
            <rect x="12" y="22" width="30" height="22" rx="2.5" fill="#fff" stroke="var(--color-brand-600)" stroke-width="1.75" />
            <path d="m12.5 23.5 14.5 11 14.5-11" stroke="var(--color-brand-600)" stroke-width="1.75" />
            <path d="M27 31.5c-1.4-1.5-4-1-4 1.1 0 1.8 2.3 3.2 4 4.4 1.7-1.2 4-2.6 4-4.4 0-2.1-2.6-2.6-4 -1.1z" fill="var(--color-brand-500)" />
            <circle cx="46" cy="40" r="8" fill="var(--color-gold-300)" stroke="var(--color-gold-600)" stroke-width="1.25" />
            <rect x="44" y="35" width="4" height="7" rx="2" fill="var(--color-brand-600)" />
            <path d="M42.5 40a3.5 3.5 0 0 0 7 0M46 43.5V45" stroke="var(--color-brand-600)" stroke-width="1.25" />
            <path d="M50 18c2 2 2 5 0 7M53 15.5c3.4 3.4 3.4 8.6 0 12" stroke="var(--color-gold-500)" stroke-width="1.5" />
            @break
        @case('keep')
            {{-- The album, kept and downloaded as one ZIP. --}}
            <rect x="15" y="16" width="26" height="32" rx="2.5" fill="var(--color-brand-600)" />
            <rect x="19" y="20" width="18" height="14" rx="1" fill="var(--color-brand-100)" />
            <path d="m19 31 5-5 4 4 3-3 6 6" stroke="var(--color-brand-600)" stroke-width="1.5" />
            <circle cx="32" cy="24" r="1.75" fill="var(--color-gold-400)" />
            <path d="M19 39h12M19 43h8" stroke="var(--color-gold-300)" stroke-width="1.5" />
            <circle cx="45" cy="42" r="9" fill="var(--color-gold-300)" stroke="var(--color-gold-600)" stroke-width="1.25" />
            <path d="M45 37v8m-3.5-3.5L45 45l3.5-3.5M41 48h8" stroke="var(--color-brand-700)" stroke-width="1.75" />
            @break
    @endswitch
</svg>
