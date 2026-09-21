@props(['site'])

@if ($site->venue_name || $site->venue_address)
    <section id="lokasi" data-reveal class="scroll-mt-8 py-12 text-center">
        @include('sites.partials.section-heading', ['eyebrow' => 'Lokasi majlis', 'title' => 'Tempat'])
        <div class="nk-plaque mt-8 px-6 py-9">
            @if ($site->venue_name)<h3 class="nk-name text-2xl font-medium">{{ $site->venue_name }}</h3>@endif
            @if ($site->venue_address)<p class="nk-body mt-3 leading-relaxed">{{ $site->venue_address }}</p>@endif
            @if ($site->map_url)
                <a href="{{ $site->map_url }}" target="_blank" rel="noopener" class="nk-button mt-6 inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-medium">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    Buka peta
                </a>
            @endif
        </div>
    </section>
@endif
