@props([
    /** One of plan, search, contact, deal, invite, celebrate: the About page step it draws. */
    'name',
])

{{-- A small drawing of one "how it works" step, shown on the About page's phone
     mockups in the site's brand and gold. --}}
<svg {{ $attributes->class(['shrink-0']) }} viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <ellipse cx="32" cy="56" rx="20" ry="3" fill="var(--color-gold-300)" opacity=".45" />
    @switch($name)
        @case('plan')
            {{-- The wedding checklist on a clipboard, a pencil ticking it off. --}}
            <rect x="14" y="10" width="30" height="40" rx="4" fill="var(--color-brand-600)" />
            <rect x="18" y="15" width="22" height="31" rx="2" fill="#fff" />
            <rect x="23" y="7" width="12" height="6" rx="2" fill="var(--color-gold-400)" stroke="var(--color-gold-600)" stroke-width="1.25" />
            <path d="m21 22 2 2 3.5-3.5M21 30l2 2 3.5-3.5" stroke="var(--color-brand-600)" stroke-width="1.75" />
            <path d="M29 22.5h7M29 30.5h7M21 38h15" stroke="var(--color-gold-500)" stroke-width="1.75" />
            <path d="m44 26 7 7-12 12-8 1 1-8z" fill="var(--color-gold-300)" stroke="var(--color-gold-600)" stroke-width="1.25" />
            <path d="m41 29 7 7" stroke="var(--color-gold-600)" stroke-width="1.25" />
            @break
        @case('search')
            {{-- A vendor's shopfront under the magnifying glass. --}}
            <path d="M10 26 13 16h26l3 10z" fill="var(--color-brand-500)" />
            <path d="M10 26h32v3a4 4 0 0 1-8 0 4 4 0 0 1-8 0 4 4 0 0 1-8 0 4 4 0 0 1-8 0z" fill="var(--color-brand-100)" stroke="var(--color-brand-500)" stroke-width="1.25" />
            <rect x="12" y="32" width="28" height="18" rx="1.5" fill="#fff" stroke="var(--color-brand-600)" stroke-width="1.5" />
            <rect x="17" y="38" width="7" height="12" fill="var(--color-gold-300)" stroke="var(--color-gold-600)" stroke-width="1.25" />
            <circle cx="43" cy="35" r="10" fill="#fff" fill-opacity=".7" stroke="var(--color-gold-500)" stroke-width="3" />
            <path d="M39 32a5 5 0 0 1 5-2" stroke="var(--color-gold-300)" stroke-width="1.5" />
            <path d="m50.5 42.5 6 6" stroke="var(--color-brand-700)" stroke-width="4" />
            @break
        @case('contact')
            {{-- A phone with the conversation going both ways. --}}
            <rect x="12" y="10" width="22" height="40" rx="4" fill="var(--color-brand-600)" />
            <rect x="14.5" y="14" width="17" height="31" rx="1.5" fill="#fff" />
            <rect x="17" y="18" width="11" height="5" rx="2.5" fill="var(--color-brand-100)" />
            <rect x="19" y="26" width="10" height="5" rx="2.5" fill="var(--color-gold-300)" />
            <rect x="17" y="34" width="8" height="5" rx="2.5" fill="var(--color-brand-100)" />
            <path d="M36 20h16a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-9l-5 4v-4h-2a3 3 0 0 1-3-3v-9a3 3 0 0 1 3-3z" fill="var(--color-gold-400)" stroke="var(--color-gold-600)" stroke-width="1.25" />
            <circle cx="40" cy="27.5" r="1.5" fill="#fff" />
            <circle cx="45" cy="27.5" r="1.5" fill="#fff" />
            <circle cx="50" cy="27.5" r="1.5" fill="#fff" />
            @break
        @case('deal')
            {{-- The agreement, signed, with the deposit paid straight to the vendor. --}}
            <path d="M14 8h20l8 8v34H14z" fill="#fff" stroke="var(--color-brand-600)" stroke-width="1.75" />
            <path d="M34 8v8h8" fill="var(--color-brand-100)" stroke="var(--color-brand-600)" stroke-width="1.75" />
            <path d="M19 21h12M19 27h18M19 33h14" stroke="var(--color-gold-500)" stroke-width="1.75" />
            <path d="M19 43c2-3 3-3 4 0s2 3 4 0 3-2 5 0" stroke="var(--color-brand-600)" stroke-width="1.5" />
            <circle cx="46" cy="44" r="10" fill="var(--color-gold-400)" stroke="var(--color-gold-600)" stroke-width="1.5" />
            <circle cx="46" cy="44" r="7" stroke="var(--color-gold-300)" stroke-width="1.25" />
            <text x="46" y="47" text-anchor="middle" font-size="7" font-weight="700" fill="var(--color-brand-700)" font-family="ui-sans-serif, system-ui, sans-serif">RM</text>
            @break
        @case('invite')
            {{-- The digital card rising out of its envelope. --}}
            <rect x="18" y="8" width="28" height="30" rx="2" fill="var(--color-ivory)" stroke="var(--color-gold-500)" stroke-width="1.5" />
            <path d="M32 18.5c-1.6-1.8-4.5-1.2-4.5 1.2 0 2 2.6 3.6 4.5 5 1.9-1.4 4.5-3 4.5-5 0-2.4-2.9-3-4.5-1.2z" fill="var(--color-brand-500)" />
            <path d="M25 30h14" stroke="var(--color-gold-500)" stroke-width="1.5" />
            <path d="M10 28 32 42l22-14v22H10z" fill="var(--color-brand-600)" />
            <path d="m10 50 17-12M54 50 37 38" stroke="var(--color-brand-700)" stroke-width="1.5" />
            <path d="M10 28 32 42l22-14" stroke="var(--color-brand-700)" stroke-width="1.5" />
            @break
        @default
            {{-- Two rings and a little sparkle: the day itself. --}}
            <circle cx="25" cy="36" r="12" stroke="var(--color-gold-500)" stroke-width="4" />
            <circle cx="39" cy="36" r="12" stroke="var(--color-gold-400)" stroke-width="4" />
            <path d="m25 18 3.5 4.5h-7z" fill="var(--color-brand-100)" stroke="var(--color-brand-500)" stroke-width="1.25" />
            <path d="M48 10v6M45 13h6M14 12v4M12 14h4" stroke="var(--color-brand-500)" stroke-width="1.5" />
            <path d="M53 22l1 2 2 1-2 1-1 2-1-2-2-1 2-1z" fill="var(--color-gold-500)" />
    @endswitch
</svg>
