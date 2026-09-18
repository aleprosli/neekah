@props(['title', 'wide' => false])

{{-- Signing in and signing up are their own place, not a page of the
     marketplace: no site header, no footer, nothing to wander off to while
     you are halfway through a password. A shell of its own, too, so
     navigation.js never swaps a form into the site furniture or back. --}}
<x-layouts.app :title="$title" shell="auth">
    <div class="relative isolate flex min-h-screen flex-col overflow-hidden bg-surface">
        {{-- Decoration only: a soft brand wash, a dot grid, and two rings that
             overlap the way the brand mark does. --}}
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-brand-50),transparent_60%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(var(--color-brand-200)_1px,transparent_1px)] [background-size:22px_22px] opacity-30 [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>
            <svg class="absolute top-1/2 left-1/2 h-[140vh] w-[140vh] max-w-none -translate-x-1/2 -translate-y-1/2 text-brand-200/45" viewBox="0 0 800 800" fill="none" stroke="currentColor" stroke-width="1">
                <circle cx="330" cy="400" r="300" />
                <circle cx="470" cy="400" r="300" />
                <circle cx="330" cy="400" r="210" stroke-dasharray="2 8" />
                <circle cx="470" cy="400" r="210" stroke-dasharray="2 8" />
            </svg>
        </div>

        <div @class([
            'mx-auto flex w-full flex-1 flex-col justify-center px-4 py-10 sm:px-6 sm:py-14',
            'max-w-2xl' => $wide,
            'max-w-[30rem]' => ! $wide,
        ])>
            <a href="{{ route('vendors.index') }}" class="mx-auto mb-8 flex" aria-label="{{ config('app.name') }}">
                <x-brand.lockup class="h-9 max-w-[60vw] object-contain sm:h-10" />
            </a>

            {{ $slot }}
        </div>

        <p class="pb-8 text-center text-sm text-ink-muted">
            <a href="{{ route('vendors.index') }}" class="inline-flex items-center gap-1.5 transition hover:text-ink">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Kembali ke Neekah
            </a>
        </p>
    </div>
</x-layouts.app>
