{{-- Malam: dark, gold accents, for an evening reception. --}}
<div class="min-h-screen bg-[#0f1023] text-white">
    <header class="relative overflow-hidden px-6 py-24 text-center sm:py-32">
        @if ($site->cover_image)
            <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="absolute inset-0 size-full object-cover opacity-25">
        @endif
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,.35),transparent_60%),radial-gradient(ellipse_at_bottom,rgba(212,175,55,.2),transparent_55%)]"></div>

        <div class="relative mx-auto max-w-xl">
            <p class="text-xs tracking-[0.4em] text-gold-300 uppercase">Resepsi</p>
            <h1 class="mt-8 font-display text-4xl leading-tight font-semibold sm:text-6xl">{{ $site->bride_name }} <span class="text-gold-400">&amp;</span> {{ $site->groom_name }}</h1>
            <div class="mx-auto my-8 h-px w-24 bg-gold-400/50"></div>
            <p class="font-display text-lg text-white/80">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
            @if ($site->startsAtLabel())<p class="mt-1 text-sm text-white/50">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) hingga {{ $site->endsAtLabel() }}@endif</p>@endif
        </div>
    </header>

    <main class="mx-auto flex max-w-xl flex-col gap-14 px-6 pb-24 text-center">
        @if ($site->salutation)
            <p class="leading-relaxed text-white/70">{{ $site->salutation }}</p>
        @endif

        @if ($site->bride_parents || $site->groom_parents)
            <div class="flex flex-col gap-1 rounded-3xl border border-white/10 bg-white/5 p-6">
                @if ($site->bride_parents)<p class="font-display">{{ $site->bride_parents }}</p>@endif
                @if ($site->bride_parents && $site->groom_parents)<p class="text-sm text-gold-400">&amp;</p>@endif
                @if ($site->groom_parents)<p class="font-display">{{ $site->groom_parents }}</p>@endif
            </div>
        @endif

        @if ($site->invitation_note)
            <p class="leading-relaxed text-white/70">{{ $site->invitation_note }}</p>
        @endif

        @if ($site->venue_name || $site->venue_address)
            <section class="rounded-3xl border border-white/10 bg-white/5 p-8">
                <p class="text-xs tracking-[0.3em] text-gold-300 uppercase">Lokasi</p>
                @if ($site->venue_name)<h2 class="mt-3 font-display text-2xl">{{ $site->venue_name }}</h2>@endif
                @if ($site->venue_address)<p class="mt-2 text-sm leading-relaxed text-white/60">{{ $site->venue_address }}</p>@endif
                @if ($site->map_url)
                    <a href="{{ $site->map_url }}" target="_blank" rel="noopener" class="mt-5 inline-flex rounded-full bg-gold-400 px-6 py-3 text-sm font-semibold text-[#0f1023] transition hover:bg-gold-300">Buka peta</a>
                @endif
            </section>
        @endif

        @if (filled($site->itinerary))
            <section>
                <p class="text-xs tracking-[0.3em] text-gold-300 uppercase">Atur cara</p>
                <ul class="mt-6 flex flex-col gap-3">
                    @foreach ($site->itinerary as $row)
                        <li class="flex items-center justify-between gap-4 border-b border-white/10 pb-3 text-left last:border-b-0">
                            <span class="text-sm text-gold-300">{{ $row['time'] }}</span>
                            <span class="text-right text-white/80">{{ $row['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <p class="font-display text-6xl text-gold-400">{{ $site->daysUntil() }}</p>
            <p class="text-sm text-white/50">hari lagi</p>
        </section>

        @if ($site->acceptsRsvps())
            <section class="rounded-3xl border border-white/10 bg-white/5 p-8">
                <p class="text-xs tracking-[0.3em] text-gold-300 uppercase">RSVP</p>
                <h2 class="mt-3 mb-6 font-display text-2xl">Sahkan kehadiran anda</h2>
                @include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview, 'tone' => 'dark'])
            </section>
        @endif

        @if (filled($site->contacts))
            <section>
                <p class="text-xs tracking-[0.3em] text-gold-300 uppercase">Hubungi</p>
                <ul class="mt-4 flex flex-col gap-2 text-sm text-white/70">
                    @foreach ($site->contacts as $contact)
                        <li>{{ $contact['name'] }} · <a href="tel:{{ $contact['phone'] }}" class="underline underline-offset-4">{{ $contact['phone'] }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($site->closing_note)
            <p class="leading-relaxed text-white/60 italic">{{ $site->closing_note }}</p>
        @endif
    </main>
</div>
