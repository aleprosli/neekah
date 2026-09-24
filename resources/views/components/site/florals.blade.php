@props([
    /** Which corners carry the light watercolor botanical: 'both', 'left', 'right' or 'none'. */
    'corners' => 'both',
    /** A faint second botanical further down the page. */
    'particles' => false,
    /** A botanical spray low on the left, for a long page that would otherwise go bare. */
    'sprig' => false,
    /** A botanical spray part-way down the right, for a page of many cards. */
    'cluster' => false,
])

{{-- The watercolor botanical every public page wears, in one clipped layer.

     The layer clips itself rather than the page: overflow-hidden on <main>
     quietly stops anything sticky inside it (the gallery's category bar, the
     comparison's vendor row) from sticking, because the clipped ancestor
     becomes the box those elements stick to. Put this first inside a
     `relative` element and keep the content in a `relative` sibling. --}}
<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    @if (in_array($corners, ['both', 'left'], true))
        <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -top-16 -left-24 w-72 opacity-25 sm:w-[26rem]" decoding="async">
    @endif
    @if (in_array($corners, ['both', 'right'], true))
        <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -top-20 -right-28 w-72 scale-x-[-1] opacity-15 sm:w-[26rem]" decoding="async">
    @endif
    @if ($particles)
        <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute top-[24rem] left-1/2 w-72 -translate-x-1/2 rotate-45 opacity-[0.06] sm:w-[30rem]" loading="lazy" decoding="async">
    @endif
    @if ($sprig)
        <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute top-[36rem] -left-24 w-72 opacity-10 sm:w-96" loading="lazy" decoding="async">
    @endif
    @if ($cluster)
        <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute top-[16rem] -right-28 w-80 scale-x-[-1] opacity-10 sm:w-[28rem]" loading="lazy" decoding="async">
    @endif
</div>
