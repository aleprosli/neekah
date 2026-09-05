{{-- Bunga: soft pastels with a floral arch. --}}
<div class="min-h-screen bg-[#fff7f8] text-[#4a2c39]">
    <header class="relative overflow-hidden px-6 py-20 text-center sm:py-28">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,#fbcfe8,transparent_55%),radial-gradient(ellipse_at_bottom_right,#fde68a,transparent_50%)]"></div>
        <div class="relative mx-auto max-w-xl">
            @if ($site->cover_image)
                <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="mx-auto mb-8 size-44 rounded-full border-4 border-white object-cover shadow-lg sm:size-56">
            @else
                <p class="mb-6 text-5xl">🌸</p>
            @endif
            <p class="font-display text-xs tracking-[0.3em] text-[#b47a90] uppercase">Kami akan berkahwin</p>
            <h1 class="mt-5 font-display text-4xl leading-tight font-semibold sm:text-6xl">{{ $site->bride_name }}</h1>
            <p class="my-2 font-display text-2xl text-[#d98ca6]">&amp;</p>
            <h1 class="font-display text-4xl leading-tight font-semibold sm:text-6xl">{{ $site->groom_name }}</h1>
            <p class="mt-8 inline-block rounded-full bg-white/70 px-6 py-2 text-sm backdrop-blur">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
        </div>
    </header>

    <main class="mx-auto flex max-w-xl flex-col gap-14 px-6 pb-20 text-center">
        @if ($site->salutation)
            <p class="leading-relaxed text-[#7a5566]">{{ $site->salutation }}</p>
        @endif

        @if ($site->bride_parents || $site->groom_parents)
            <div class="flex flex-col gap-1 rounded-3xl bg-white/70 p-6">
                @if ($site->bride_parents)<p class="font-display">{{ $site->bride_parents }}</p>@endif
                @if ($site->bride_parents && $site->groom_parents)<p class="text-sm text-[#d98ca6]">&amp;</p>@endif
                @if ($site->groom_parents)<p class="font-display">{{ $site->groom_parents }}</p>@endif
            </div>
        @endif

        @if ($site->invitation_note)
            <p class="leading-relaxed text-[#7a5566]">{{ $site->invitation_note }}</p>
        @endif

        <section class="rounded-3xl bg-white/70 p-8">
            <p class="text-3xl">📍</p>
            @if ($site->venue_name)<h2 class="mt-3 font-display text-2xl">{{ $site->venue_name }}</h2>@endif
            @if ($site->venue_address)<p class="mt-2 text-sm leading-relaxed text-[#7a5566]">{{ $site->venue_address }}</p>@endif
            @if ($site->startsAtLabel())<p class="mt-3 text-sm">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) hingga {{ $site->endsAtLabel() }}@endif</p>@endif
            @if ($site->map_url)
                <a href="{{ $site->map_url }}" target="_blank" rel="noopener" class="mt-5 inline-flex rounded-full bg-[#d98ca6] px-6 py-3 text-sm font-medium text-white transition hover:bg-[#c4728e]">Buka peta</a>
            @endif
        </section>

        @if (filled($site->itinerary))
            <section>
                <p class="font-display text-xs tracking-[0.3em] text-[#b47a90] uppercase">Atur cara</p>
                <ul class="mt-6 flex flex-col gap-3">
                    @foreach ($site->itinerary as $row)
                        <li class="flex items-center justify-between gap-4 rounded-2xl bg-white/70 px-5 py-3 text-left">
                            <span class="text-sm text-[#b47a90]">{{ $row['time'] }}</span>
                            <span class="text-right">{{ $row['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <p class="font-display text-5xl text-[#d98ca6]">{{ $site->daysUntil() }}</p>
            <p class="text-sm text-[#7a5566]">hari lagi</p>
        </section>

        @if ($site->acceptsRsvps())
            <section class="rounded-3xl bg-white/70 p-8">
                <p class="text-3xl">💌</p>
                <h2 class="mt-3 mb-6 font-display text-2xl">Sahkan kehadiran anda</h2>
                @include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview])
            </section>
        @endif

        @if (filled($site->contacts))
            <section>
                <p class="font-display text-xs tracking-[0.3em] text-[#b47a90] uppercase">Hubungi</p>
                <ul class="mt-4 flex flex-col gap-2 text-sm">
                    @foreach ($site->contacts as $contact)
                        <li>{{ $contact['name'] }} · <a href="tel:{{ $contact['phone'] }}" class="underline underline-offset-4">{{ $contact['phone'] }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($site->closing_note)
            <p class="leading-relaxed text-[#7a5566] italic">{{ $site->closing_note }}</p>
        @endif
    </main>
</div>
