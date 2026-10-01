@props([
    'tier',
    /** The accessible name; without one the badge is decorative. */
    'label' => null,
])

@php
    /*
     * The vendor rank badges, drawn as SVG instead of the ~600 KB PNG artwork
     * they replace: a bevelled metal hexagon framing a gem-cut face in the
     * rank's colour, a fine lattice and the Alex Brush "n" of Neekah engraved in
     * it, the rank's emblem at its foot and its name on a folded ribbon. Elite
     * adds a three-point crown and a laurel branch either side. Gradient ids carry a per-badge
     * suffix because a page can draw the same rank more than once.
     *
     * Given a slot, the face shows that instead of the monogram (clipped to the
     * hexagon): vendor-ranked-avatar puts the vendor's logo there.
     */
    $palettes = [
        \App\Enums\VendorTier::New->value => ['light' => '#f3bf97', 'mid' => '#c8774a', 'dark' => '#7a3a18', 'frame' => '#f8d6b8', 'frameDark' => '#9a522a', 'ribbon' => '#c8774a', 'ribbonDark' => '#8a4520', 'ribbonText' => '#3d1a08'],
        \App\Enums\VendorTier::Verified->value => ['light' => '#6fcb8b', 'mid' => '#2d8550', 'dark' => '#103f25', 'frame' => '#c6f0d2', 'frameDark' => '#1e6b3d', 'ribbon' => '#237a46', 'ribbonDark' => '#0f4527', 'ribbonText' => '#ffffff'],
        \App\Enums\VendorTier::Trusted->value => ['light' => '#4b8cec', 'mid' => '#1749a8', 'dark' => '#081f55', 'frame' => '#fbe29a', 'frameDark' => '#b8860f', 'ribbon' => '#14408f', 'ribbonDark' => '#0a2560', 'ribbonText' => '#fbe29a'],
        \App\Enums\VendorTier::Top->value => ['light' => '#c39ae8', 'mid' => '#6c3fa6', 'dark' => '#2c1557', 'frame' => '#f7cfe0', 'frameDark' => '#8f4f7c', 'ribbon' => '#45247d', 'ribbonDark' => '#25104a', 'ribbonText' => '#ffffff'],
        \App\Enums\VendorTier::Recommended->value => ['light' => '#f28aa0', 'mid' => '#b0233f', 'dark' => '#4f0b1b', 'frame' => '#fbe6a4', 'frameDark' => '#b8860f', 'ribbon' => '#7a1630', 'ribbonDark' => '#420817', 'ribbonText' => '#fbe29a'],
    ];
    $colour = $palettes[$tier->value];
    $id = 'rb'.substr(md5(uniqid('', true)), 0, 8);
    $isElite = $tier === \App\Enums\VendorTier::Recommended;

    // Hexagon corners, clockwise from the top point; the face sits inside the frame.
    $outer = [[60, 12], [96, 33], [96, 75], [60, 96], [24, 75], [24, 33]];
    $inner = [[60, 17.5], [91, 35.5], [91, 72.5], [60, 90.5], [29, 72.5], [29, 35.5]];
    $centre = [60, 54];
    $points = fn (array $corners): string => collect($corners)->map(fn (array $c): string => $c[0].','.$c[1])->implode(' ');

    // Light from the top left: each bevel edge and each facet of the face gets its own shade.
    $bevelShades = [['#fff', .55], ['#fff', .12], ['#000', .28], ['#000', .38], ['#000', .08], ['#fff', .4]];
    $facetShades = [['#fff', .16], ['#fff', .04], ['#000', .14], ['#000', .24], ['#000', .08], ['#fff', .22]];
    // Elite's laurel: leaves in pairs along a round arc about the hexagon's
    // centre, rising from behind the ribbon, one turned out and one turned in at
    // each node, smaller toward the tip. Past the widest point the arc opens
    // outward, so the tips stand clear of the crown.
    $wreath = ['cx' => 60, 'cy' => 54, 'r' => 46.5, 'from' => 113, 'to' => 224];
    $onWreath = function (float $degrees) use ($wreath): array {
        $radius = $wreath['r'] + max(0, $degrees - 185) * 0.12;

        return [
            round($wreath['cx'] + $radius * cos(deg2rad($degrees)), 2),
            round($wreath['cy'] + $radius * sin(deg2rad($degrees)), 2),
        ];
    };
    $stemPoints = collect(range($wreath['from'], $wreath['to'], 4))->map(fn (int $degrees): string => implode(',', $onWreath($degrees)))->implode(' ');
    $stemEnd = $onWreath($wreath['to']);
    $leaves = collect(range(0, 8))->flatMap(function (int $n) use ($wreath, $onWreath): array {
        $degrees = $wreath['from'] + 7 + $n * 12;
        [$x, $y] = $onWreath($degrees);
        $heading = $degrees + 90 - max(0, $degrees - 185) * 0.25;
        $size = 1.3 - $n * 0.045;

        return [
            ['x' => $x, 'y' => $y, 'angle' => round($heading - 42, 1), 'size' => round($size, 3)],
            ['x' => $x, 'y' => $y, 'angle' => round($heading + 28, 1), 'size' => round($size * 0.78, 3)],
        ];
    })->push(['x' => $stemEnd[0], 'y' => $stemEnd[1], 'angle' => $wreath['to'] + 90 - ($wreath['to'] - 185) * 0.25, 'size' => 1]);
@endphp

<svg
    {{ $attributes->class(['shrink-0']) }}
    viewBox="0 0 120 120"
    data-rank-badge="{{ $tier->value }}"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
>
    <defs>
        <linearGradient id="{{ $id }}-face" x1="0" y1="0" x2=".8" y2="1">
            <stop offset="0" stop-color="{{ $colour['light'] }}" />
            <stop offset=".5" stop-color="{{ $colour['mid'] }}" />
            <stop offset="1" stop-color="{{ $colour['dark'] }}" />
        </linearGradient>
        <radialGradient id="{{ $id }}-glow" cx=".38" cy=".3" r=".6">
            <stop offset="0" stop-color="#fff" stop-opacity=".45" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="{{ $id }}-frame" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="{{ $colour['frame'] }}" />
            <stop offset=".45" stop-color="{{ $colour['frameDark'] }}" />
            <stop offset=".7" stop-color="{{ $colour['frame'] }}" />
            <stop offset="1" stop-color="{{ $colour['frameDark'] }}" />
        </linearGradient>
        <linearGradient id="{{ $id }}-gold" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fff2c4" />
            <stop offset=".45" stop-color="#f0bf45" />
            <stop offset="1" stop-color="#a46a0c" />
        </linearGradient>
        <linearGradient id="{{ $id }}-ribbon" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="{{ $colour['ribbon'] }}" />
            <stop offset="1" stop-color="{{ $colour['ribbonDark'] }}" />
        </linearGradient>
        <pattern id="{{ $id }}-lattice" width="7" height="7" patternUnits="userSpaceOnUse" patternTransform="rotate(45 60 54)">
            <path d="M0 0h7v7" fill="none" stroke="#fff" stroke-opacity=".09" stroke-width=".6" />
        </pattern>
        <clipPath id="{{ $id }}-clip">
            <polygon points="{{ $points($inner) }}" />
        </clipPath>
        <filter id="{{ $id }}-shadow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="1.2" stdDeviation="1" flood-color="#000" flood-opacity=".35" />
        </filter>
    </defs>

    {{-- Everything sits a little low, so Elite's crown clears the top edge. --}}
    <g transform="translate(0 2.5)">

    @if ($isElite)
        {{-- A laurel branch either side, behind the hexagon and the ribbon: a
             curved stem, and pointed leaves in two tones with a midrib. --}}
        @foreach ([1, -1] as $side)
            <g transform="translate(60 0) scale({{ $side }} 1) translate(-60 0)">
                <polyline points="{{ $stemPoints }}" fill="none" stroke="#b07a14" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                @foreach ($leaves as $leaf)
                    <g transform="translate({{ $leaf['x'] }} {{ $leaf['y'] }}) rotate({{ $leaf['angle'] }}) scale({{ $leaf['size'] }})">
                        <path d="M0 0C2-3.6 8-3.6 10.5 0 8 3.6 2 3.6 0 0z" fill="#d89a26" stroke="#9c6a10" stroke-width=".5" stroke-linejoin="round" />
                        <path d="M0 0C2-3.6 8-3.6 10.5 0z" fill="#ffe08c" />
                        <path d="M.8 0H9.6" stroke="#a46a0c" stroke-width=".45" stroke-linecap="round" />
                    </g>
                @endforeach
            </g>
        @endforeach
    @endif

    @if ($isElite)
        {{-- Three-point crown behind the top of the hexagon: lit left face, shaded
             right face, a ball on every point. --}}
        <g filter="url(#{{ $id }}-shadow)">
            <path d="M45 20 40.5 3.5 50.5 11 60 1.5 69.5 11 79.5 3.5 75 20z" fill="url(#{{ $id }}-gold)" stroke="#9c6a10" stroke-width=".9" stroke-linejoin="round" />
            <path d="M60 1.5 69.5 11 79.5 3.5 75 20H60z" fill="#b8790f" fill-opacity=".45" />
            <path d="M40.5 3.5 50.5 11 45.5 16z" fill="#fff6d6" fill-opacity=".45" />
            @foreach ([[40.5, 2.2], [60, .2], [79.5, 2.2]] as [$x, $y])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="2.3" fill="url(#{{ $id }}-gold)" stroke="#9c6a10" stroke-width=".6" />
                <circle cx="{{ $x - .6 }}" cy="{{ $y - .7 }}" r=".7" fill="#fffbe8" />
            @endforeach
        </g>
    @endif

    {{-- Drop shadow under the whole badge. --}}
    <polygon points="{{ $points($outer) }}" transform="translate(0 3.5)" fill="#000" opacity=".2" />

    {{-- Frame with a bevel on each edge. --}}
    <polygon points="{{ $points($outer) }}" fill="url(#{{ $id }}-frame)" />
    @foreach ($outer as $i => $corner)
        @php($next = ($i + 1) % 6)
        <polygon points="{{ $points([$corner, $outer[$next], $inner[$next], $inner[$i]]) }}" fill="{{ $bevelShades[$i][0] }}" fill-opacity="{{ $bevelShades[$i][1] }}" />
    @endforeach
    <polygon points="{{ $points($outer) }}" fill="none" stroke="{{ $colour['frameDark'] }}" stroke-width="1.2" stroke-linejoin="round" />

    {{-- Face: gem-cut facets, a soft glow from the top left, a fine lattice and an inner rim. --}}
    <polygon points="{{ $points($inner) }}" fill="url(#{{ $id }}-face)" />
    @if ($slot->isNotEmpty())
        <g clip-path="url(#{{ $id }}-clip)">{{ $slot }}</g>
        <polygon points="{{ $points($inner) }}" fill="url(#{{ $id }}-glow)" opacity=".6" />
    @else
        @foreach ($inner as $i => $corner)
            <polygon points="{{ $points([$centre, $corner, $inner[($i + 1) % 6]]) }}" fill="{{ $facetShades[$i][0] }}" fill-opacity="{{ $facetShades[$i][1] }}" />
        @endforeach
        <polygon points="{{ $points($inner) }}" fill="url(#{{ $id }}-lattice)" />
        <polygon points="{{ $points($inner) }}" fill="url(#{{ $id }}-glow)" />
    @endif
    <polygon points="{{ $points($inner) }}" fill="none" stroke="{{ $colour['dark'] }}" stroke-opacity=".6" stroke-width="1" stroke-linejoin="round" />
    <polygon points="60,21 88,37.3 88,70.7 60,87 32,70.7 32,37.3" fill="none" stroke="#fff" stroke-opacity=".22" stroke-width=".7" stroke-linejoin="round" />

    @if ($slot->isEmpty())
        {{-- The n of Neekah in Alex Brush, engraved: a dark cut with a lit edge. The
             glyph is its outline (font units, y up), so no font has to load for it. --}}
        <g transform="translate(60 55) scale(.085 -.085) translate(-204 -162)">
            <path transform="translate(10 -12)" d="M255 -2Q228 -2 217 14Q206 29 206 52Q206 110 246 176L310 280Q242 230 175 155Q157 135 134 108Q112 81 85 46Q49 -1 42 -1Q28 -1 19 12Q10 24 10 34Q10 42 15 64Q20 85 27 101Q34 118 42 134Q49 151 56 167L-4 104Q-6 102 -16 92Q-26 82 -32 82Q-37 82 -37 89Q-37 91 -36 94Q-34 98 -29 103Q-14 118 2 135Q18 152 35 174Q44 185 62 208Q80 230 102 256Q123 282 142 304Q147 311 153 314Q159 316 161 316Q188 316 188 295Q188 289 185 283Q95 159 77 94Q193 210 264 268Q334 327 364 327Q380 327 388 322Q397 317 397 303Q397 298 396 295Q393 288 355 239Q332 211 314 188Q297 165 286 148Q268 120 259 96Q250 71 250 54Q250 47 252 37Q254 27 264 27Q287 27 336 67Q359 87 379 108Q399 128 417 148Q421 152 430 164Q439 175 441 175Q445 175 445 167Q445 163 440 154Q436 144 431 139Q420 126 398 103Q377 80 350 56Q324 32 299 15Q274 -2 255 -2Z" fill="{{ $colour['dark'] }}" fill-opacity=".6" />
            <path d="M255 -2Q228 -2 217 14Q206 29 206 52Q206 110 246 176L310 280Q242 230 175 155Q157 135 134 108Q112 81 85 46Q49 -1 42 -1Q28 -1 19 12Q10 24 10 34Q10 42 15 64Q20 85 27 101Q34 118 42 134Q49 151 56 167L-4 104Q-6 102 -16 92Q-26 82 -32 82Q-37 82 -37 89Q-37 91 -36 94Q-34 98 -29 103Q-14 118 2 135Q18 152 35 174Q44 185 62 208Q80 230 102 256Q123 282 142 304Q147 311 153 314Q159 316 161 316Q188 316 188 295Q188 289 185 283Q95 159 77 94Q193 210 264 268Q334 327 364 327Q380 327 388 322Q397 317 397 303Q397 298 396 295Q393 288 355 239Q332 211 314 188Q297 165 286 148Q268 120 259 96Q250 71 250 54Q250 47 252 37Q254 27 264 27Q287 27 336 67Q359 87 379 108Q399 128 417 148Q421 152 430 164Q439 175 441 175Q445 175 445 167Q445 163 440 154Q436 144 431 139Q420 126 398 103Q377 80 350 56Q324 32 299 15Q274 -2 255 -2Z" fill="#fff" fill-opacity=".34" stroke="#fff" stroke-opacity=".4" stroke-width="5" />
        </g>
    @endif

    {{-- Sparkles on the frame. --}}
    <path d="M33 30l.9 2.6 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9z" fill="#fff" fill-opacity=".9" />
    <path d="M91 61l.6 1.7 1.7.6-1.7.6-.6 1.7-.6-1.7-1.7-.6 1.7-.6z" fill="#fff" fill-opacity=".75" />

    {{-- The rank's emblem at the foot of the hexagon. --}}
    <g filter="url(#{{ $id }}-shadow)">
        @switch($tier)
            @case(\App\Enums\VendorTier::New)
                <path d="M60 73.5l3.4 7 7.7 1.1-5.6 5.4 1.3 7.7L60 91l-6.8 3.7 1.3-7.7-5.6-5.4 7.7-1.1z" fill="url(#{{ $id }}-gold)" stroke="#8a5a10" stroke-width="1" stroke-linejoin="round" />
                <path d="M60 77.5v11.5M54.2 83.2 60 85.4l5.8-2.2" fill="none" stroke="#fff2c4" stroke-opacity=".7" stroke-width=".7" stroke-linecap="round" />
                @break
            @case(\App\Enums\VendorTier::Verified)
                <circle cx="60" cy="84.5" r="10" fill="#fff" />
                <circle cx="60" cy="84.5" r="8" fill="{{ $colour['mid'] }}" />
                <path d="M54 82a8 8 0 0 1 12 0" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="1.2" stroke-linecap="round" />
                <path d="m55.6 84.6 3 3 5.8-6.3" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                @break
            @case(\App\Enums\VendorTier::Trusted)
                <path d="M60 73 71 77.5v6.5c0 7-4.7 11.4-11 13.6-6.3-2.2-11-6.6-11-13.6v-6.5z" fill="url(#{{ $id }}-gold)" stroke="#8a5a10" stroke-width="1" stroke-linejoin="round" />
                <path d="M60 76.2 68 79.4v4.8c0 5.2-3.4 8.5-8 10.2-4.6-1.7-8-5-8-10.2v-4.8z" fill="{{ $colour['mid'] }}" />
                <path d="M60 76.2 68 79.4v4.8c0 1-.1 1.9-.4 2.8L60 80.5z" fill="#fff" fill-opacity=".18" />
                <path d="m55.8 84.8 2.8 2.8 5.6-6" fill="none" stroke="#fbe29a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                @break
            @case(\App\Enums\VendorTier::Top)
                <path d="M47.5 92.5 46 77.5l7 6 7-9 7 9 7-6-1.5 15z" fill="url(#{{ $id }}-gold)" stroke="#8a5a10" stroke-width="1" stroke-linejoin="round" />
                <rect x="47.5" y="90" width="25" height="3.5" rx="1.2" fill="#c98a1c" />
                <circle cx="46" cy="77.5" r="1.5" fill="#fff2c4" />
                <circle cx="60" cy="74.5" r="1.7" fill="#fff2c4" />
                <circle cx="74" cy="77.5" r="1.5" fill="#fff2c4" />
                <path d="M60 83.5l2 2.8-2 2.8-2-2.8z" fill="{{ $colour['mid'] }}" stroke="#fff2c4" stroke-width=".5" />
                @break
            @default
                <path d="M60 75.5l2.8 5.8 6.4.9-4.6 4.5 1.1 6.3-5.7-3-5.7 3 1.1-6.3-4.6-4.5 6.4-.9z" fill="url(#{{ $id }}-gold)" stroke="#8a5a10" stroke-width="1" stroke-linejoin="round" />
                <circle cx="60" cy="85.5" r="1.6" fill="{{ $colour['mid'] }}" />
        @endswitch
    </g>

    {{-- The ribbon: folded tails behind, a band with a sheen, the rank's name. --}}
    <path d="M30 101h-9l3.5 5-3.5 5h9z" fill="{{ $colour['ribbonDark'] }}" />
    <path d="M90 101h9l-3.5 5 3.5 5h-9z" fill="{{ $colour['ribbonDark'] }}" />
    <path d="M30 111l2-2.5h-2zM90 111l-2-2.5h2z" fill="#000" fill-opacity=".35" />
    <rect x="28" y="97.5" width="64" height="15" rx="4" fill="url(#{{ $id }}-ribbon)" stroke="url(#{{ $id }}-frame)" stroke-width="1.4" filter="url(#{{ $id }}-shadow)" />
    <rect x="30.5" y="99.5" width="59" height="4.5" rx="2" fill="#fff" fill-opacity=".16" />
    <path d="M32 109.8h56" stroke="#000" stroke-opacity=".15" stroke-width=".6" />
    <text x="60" y="108.6" text-anchor="middle" font-size="7.8" font-weight="800" letter-spacing="1.1" fill="#000" fill-opacity=".3" style="font-family: var(--font-sans)">{{ Str::upper($tier->label()) }}</text>
    <text x="60" y="108" text-anchor="middle" font-size="7.8" font-weight="800" letter-spacing="1.1" fill="{{ $colour['ribbonText'] }}" style="font-family: var(--font-sans)">{{ Str::upper($tier->label()) }}</text>
    </g>
</svg>
