@props([
    /** Which corners carry a peony: 'both', 'left', 'right' or 'none'. */
    'corners' => 'both',
    /** A drifting sprinkle of gold across the whole area. */
    'particles' => false,
    /** A leaf sprig low on the left, for a long page that would otherwise go bare. */
    'sprig' => false,
    /** A cluster of peonies part-way down the right, for a page of many cards. */
    'cluster' => false,
])

{{-- The florals every public page wears, in one clipped layer.

     The layer clips itself rather than the page: overflow-hidden on <main>
     quietly stops anything sticky inside it (the gallery's category bar, the
     comparison's vendor row) from sticking, because the clipped ancestor
     becomes the box those elements stick to. Put this first inside a
     `relative` element and keep the content in a `relative` sibling. --}}
<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    @if (in_array($corners, ['both', 'left'], true))
        <x-site.ornament name="corner-peony" class="absolute -top-16 -left-16 size-[18rem] opacity-50 sm:size-[26rem]" color="var(--color-brand-200)" color2="var(--color-brand-100)" />
    @endif
    @if (in_array($corners, ['both', 'right'], true))
        <x-site.ornament name="corner-peony" class="absolute -top-12 -right-20 size-[18rem] rotate-90 opacity-40 sm:size-[26rem]" color="var(--color-gold-300)" color2="var(--color-brand-100)" />
    @endif
    @if ($particles)
        <x-site.ornament name="particles" class="absolute inset-x-0 top-0 h-[60rem] opacity-30" color="var(--color-gold-400)" />
    @endif
    @if ($sprig)
        <x-site.ornament name="leaf-sprig" class="absolute top-[36rem] -left-8 h-64 w-40 opacity-25 sm:h-80 sm:w-52" color="var(--color-brand-200)" />
    @endif
    @if ($cluster)
        <x-site.ornament name="cluster-peony" class="absolute top-[14rem] -right-24 size-72 opacity-25 sm:size-96" color="var(--color-gold-300)" color2="var(--color-brand-200)" />
    @endif
</div>
