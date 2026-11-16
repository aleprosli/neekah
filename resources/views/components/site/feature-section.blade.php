@props([
    'id' => null,
    'eyebrow' => null,
    'title' => null,
    'body' => null,
    /** plain: the ivory page; raised: a white band between gold rules; deep: the darker ivory. */
    'tone' => 'plain',
])

{{-- A section of a feature page: the same width, padding and centred heading
     everywhere, on one of three backgrounds that alternate down the page. --}}
<section @if ($id) id="{{ $id }}" @endif @class([
    'relative scroll-mt-24 overflow-hidden',
    'border-y border-gold-300/60 bg-surface-raised' => $tone === 'raised',
    'bg-ivory-deep' => $tone === 'deep',
])>
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        @if ($title)
            <div class="mx-auto mb-10 max-w-2xl text-center sm:mb-12">
                @if ($eyebrow)
                    <p class="text-sm font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ $eyebrow }}</p>
                @endif
                <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ $title }}</h2>
                @if ($body)
                    <p class="mt-4 text-lg text-ink-muted text-pretty">{{ $body }}</p>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</section>
