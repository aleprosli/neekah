@props([
    'title',
    'body' => null,
])

{{-- The closing invitation on About and every feature page: a framed card,
     floral rule, a line or two and the page's actions in the slot. --}}
<section {{ $attributes->class(['mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8']) }}>
    <div class="relative overflow-hidden rounded-3xl border border-gold-300 bg-surface-raised px-6 py-14 text-center sm:px-12 sm:py-16">
        <span class="pointer-events-none absolute inset-3 rounded-[1.25rem] border border-gold-300/70" aria-hidden="true"></span>
        <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="pointer-events-none absolute -bottom-28 -left-24 w-80 opacity-15 sm:w-96" loading="lazy" decoding="async">
        <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="pointer-events-none absolute -top-28 -right-24 w-80 scale-x-[-1] opacity-10 sm:w-96" loading="lazy" decoding="async">

        <div class="relative">
            <x-site.ornament name="divider-floral" class="mx-auto h-6 w-44" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
            <h2 class="mt-5 font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ $title }}</h2>
            @if ($body)
                <p class="mx-auto mt-4 max-w-xl text-lg text-ink-muted">{{ $body }}</p>
            @endif
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
