{{-- A woven band across the head of the sheet, in the geometry of songket:
     one motif repeated, not a drawing of cloth. --}}
<svg viewBox="0 0 400 64" fill="none" aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 w-full opacity-70" preserveAspectRatio="none">
    <line x1="0" y1="6" x2="400" y2="6" stroke="var(--nk-accent)" stroke-opacity=".5" stroke-width="1.2"/>
    <line x1="0" y1="58" x2="400" y2="58" stroke="var(--nk-accent)" stroke-opacity=".5" stroke-width="1.2"/>
    <line x1="0" y1="11" x2="400" y2="11" stroke="var(--nk-accent)" stroke-opacity=".25" stroke-width=".8"/>
    <line x1="0" y1="53" x2="400" y2="53" stroke="var(--nk-accent)" stroke-opacity=".25" stroke-width=".8"/>

    @for ($i = 0; $i < 10; $i++)
        @php $x = 20 + $i * 40; @endphp
        <g transform="translate({{ $x }} 32)">
            <path d="M0 -15 L13 0 L0 15 L-13 0 Z" stroke="var(--nk-accent)" stroke-opacity=".55" stroke-width="1.1" fill="var(--nk-accent)" fill-opacity=".1"/>
            <path d="M0 -7 L6 0 L0 7 L-6 0 Z" fill="var(--nk-name)" fill-opacity=".3"/>
            <line x1="13" y1="0" x2="27" y2="0" stroke="var(--nk-accent)" stroke-opacity=".35" stroke-width="1"/>
        </g>
    @endfor
</svg>
