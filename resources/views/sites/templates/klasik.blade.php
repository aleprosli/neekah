{{-- Klasik: songket motifs, deep maroon and gold. --}}
<div class="min-h-screen bg-[#fdfaf4] text-[#3b2318]">
    <header class="relative overflow-hidden bg-linear-to-b from-brand-800 to-brand-900 px-6 py-20 text-center text-white sm:py-28">
        @if ($site->cover_image)
            <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="absolute inset-0 size-full object-cover opacity-30">
        @endif
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_0,rgba(212,175,55,.35),transparent_60%)]"></div>

        <div class="relative mx-auto max-w-xl">
            <p class="font-display text-sm tracking-[0.35em] text-gold-300 uppercase">Walimatulurus</p>
            <div class="mx-auto my-6 h-px w-16 bg-gold-400/60"></div>
            <h1 class="font-display text-4xl leading-tight font-semibold sm:text-6xl">{{ $site->bride_name }}<span class="mx-3 text-gold-300">&amp;</span>{{ $site->groom_name }}</h1>
            <p class="mt-6 font-display text-lg text-gold-200">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
            @if ($site->startsAtLabel())
                <p class="text-sm text-white/70">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) hingga {{ $site->endsAtLabel() }}@endif</p>
            @endif
        </div>
    </header>

    <main class="mx-auto flex max-w-xl flex-col gap-14 px-6 py-16 text-center">
        @if ($site->salutation)
            <p class="leading-relaxed text-[#6b4a38]">{{ $site->salutation }}</p>
        @endif

        @if ($site->bride_parents || $site->groom_parents)
            <div class="flex flex-col gap-2">
                @if ($site->bride_parents)<p class="font-display text-lg">{{ $site->bride_parents }}</p>@endif
                @if ($site->bride_parents && $site->groom_parents)<p class="text-sm text-[#a1836f]">&amp;</p>@endif
                @if ($site->groom_parents)<p class="font-display text-lg">{{ $site->groom_parents }}</p>@endif
            </div>
        @endif

        @if ($site->invitation_note)
            <p class="leading-relaxed text-[#6b4a38]">{{ $site->invitation_note }}</p>
        @endif

        @if ($site->venue_name || $site->venue_address)
            <section class="rounded-3xl border border-[#e6d5bd] bg-white p-8">
                <p class="font-display text-xs tracking-[0.3em] text-[#a1836f] uppercase">Lokasi</p>
                @if ($site->venue_name)<h2 class="mt-3 font-display text-2xl">{{ $site->venue_name }}</h2>@endif
                @if ($site->venue_address)<p class="mt-2 text-sm leading-relaxed text-[#6b4a38]">{{ $site->venue_address }}</p>@endif
                @if ($site->map_url)
                    <a href="{{ $site->map_url }}" target="_blank" rel="noopener" class="mt-5 inline-flex rounded-full bg-brand-800 px-6 py-3 text-sm font-medium text-white transition hover:bg-brand-900">Buka peta</a>
                @endif
            </section>
        @endif

        @if (filled($site->itinerary))
            <section>
                <p class="font-display text-xs tracking-[0.3em] text-[#a1836f] uppercase">Atur cara</p>
                <ul class="mt-6 flex flex-col gap-4">
                    @foreach ($site->itinerary as $row)
                        <li class="flex items-baseline justify-center gap-4">
                            <span class="w-32 text-right font-display text-sm text-[#a1836f]">{{ $row['time'] }}</span>
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-gold-400"></span>
                            <span class="w-40 text-left">{{ $row['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <p class="font-display text-xs tracking-[0.3em] text-[#a1836f] uppercase">Menghitung hari</p>
            <p class="mt-3 font-display text-5xl text-brand-800">{{ $site->daysUntil() }}</p>
            <p class="text-sm text-[#6b4a38]">hari lagi</p>
        </section>

        @if ($site->acceptsRsvps())
            <section>
                <p class="font-display text-xs tracking-[0.3em] text-[#a1836f] uppercase">RSVP</p>
                <h2 class="mt-3 mb-6 font-display text-2xl">Sahkan kehadiran anda</h2>
                @include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview])
            </section>
        @endif

        @if (filled($site->contacts))
            <section>
                <p class="font-display text-xs tracking-[0.3em] text-[#a1836f] uppercase">Hubungi</p>
                <ul class="mt-4 flex flex-col gap-2 text-sm">
                    @foreach ($site->contacts as $contact)
                        <li>{{ $contact['name'] }} · <a href="tel:{{ $contact['phone'] }}" class="underline underline-offset-4">{{ $contact['phone'] }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($site->closing_note)
            <p class="border-t border-[#e6d5bd] pt-10 leading-relaxed text-[#6b4a38] italic">{{ $site->closing_note }}</p>
        @endif
    </main>
</div>
