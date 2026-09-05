{{-- Moden: airy, generous white space, oversized type. --}}
<div class="min-h-screen bg-white text-neutral-900">
    <header class="grid min-h-[70vh] place-items-center px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            @if ($site->cover_image)
                <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="mx-auto mb-10 h-56 w-full max-w-md rounded-2xl object-cover">
            @endif
            <p class="text-xs tracking-[0.4em] text-neutral-400 uppercase">Majlis perkahwinan</p>
            <h1 class="mt-8 text-5xl leading-[1.05] font-light tracking-tight sm:text-7xl">
                {{ $site->bride_name }}<br><span class="text-neutral-300">and</span><br>{{ $site->groom_name }}
            </h1>
            <p class="mt-10 text-sm tracking-[0.2em] text-neutral-500 uppercase">{{ $site->event_date->translatedFormat('j . m . Y') }}</p>
        </div>
    </header>

    <main class="mx-auto flex max-w-2xl flex-col gap-16 px-6 pb-24">
        @if ($site->salutation)
            <p class="text-center leading-relaxed text-neutral-600">{{ $site->salutation }}</p>
        @endif

        @if ($site->bride_parents || $site->groom_parents)
            <div class="grid gap-6 border-y border-neutral-200 py-10 text-center sm:grid-cols-2">
                @if ($site->bride_parents)
                    <div><p class="text-xs tracking-[0.2em] text-neutral-400 uppercase">Ibu bapa pengantin perempuan</p><p class="mt-2">{{ $site->bride_parents }}</p></div>
                @endif
                @if ($site->groom_parents)
                    <div><p class="text-xs tracking-[0.2em] text-neutral-400 uppercase">Ibu bapa pengantin lelaki</p><p class="mt-2">{{ $site->groom_parents }}</p></div>
                @endif
            </div>
        @endif

        @if ($site->invitation_note)
            <p class="text-center leading-relaxed text-neutral-600">{{ $site->invitation_note }}</p>
        @endif

        <section class="grid gap-10 sm:grid-cols-2">
            <div>
                <p class="text-xs tracking-[0.2em] text-neutral-400 uppercase">Bila</p>
                <p class="mt-3 text-2xl font-light">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
                @if ($site->startsAtLabel())<p class="text-neutral-500">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) – {{ $site->endsAtLabel() }}@endif</p>@endif
            </div>
            <div>
                <p class="text-xs tracking-[0.2em] text-neutral-400 uppercase">Di mana</p>
                @if ($site->venue_name)<p class="mt-3 text-2xl font-light">{{ $site->venue_name }}</p>@endif
                @if ($site->venue_address)<p class="text-neutral-500">{{ $site->venue_address }}</p>@endif
                @if ($site->map_url)<a href="{{ $site->map_url }}" target="_blank" rel="noopener" class="mt-3 inline-block text-sm underline underline-offset-4">Buka peta</a>@endif
            </div>
        </section>

        @if (filled($site->itinerary))
            <section>
                <p class="text-xs tracking-[0.2em] text-neutral-400 uppercase">Atur cara</p>
                <ul class="mt-6 divide-y divide-neutral-200">
                    @foreach ($site->itinerary as $row)
                        <li class="flex items-baseline justify-between gap-6 py-4">
                            <span class="text-neutral-500">{{ $row['time'] }}</span>
                            <span class="text-right">{{ $row['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($site->acceptsRsvps())
            <section class="rounded-3xl bg-neutral-50 p-8 text-center sm:p-10">
                <p class="text-xs tracking-[0.2em] text-neutral-400 uppercase">RSVP</p>
                <h2 class="mt-3 mb-6 text-2xl font-light">Sahkan kehadiran anda</h2>
                @include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview])
            </section>
        @endif

        @if (filled($site->contacts))
            <section class="text-center">
                <p class="text-xs tracking-[0.2em] text-neutral-400 uppercase">Hubungi</p>
                <ul class="mt-4 flex flex-wrap justify-center gap-x-8 gap-y-2 text-sm">
                    @foreach ($site->contacts as $contact)
                        <li>{{ $contact['name'] }} <a href="tel:{{ $contact['phone'] }}" class="underline underline-offset-4">{{ $contact['phone'] }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($site->closing_note)
            <p class="text-center text-sm leading-relaxed text-neutral-500">{{ $site->closing_note }}</p>
        @endif
    </main>
</div>
