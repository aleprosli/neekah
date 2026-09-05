@props(['site', 'preview' => false, 'palette'])

@php $tone = $palette['tone']; @endphp

<div class="relative min-h-screen overflow-hidden {{ $palette['page'] }}" style="font-family: var(--font-serif-card)">
    @include('sites.partials.petals', ['colors' => $palette['petals'], 'opacity' => $palette['petalOpacity']])

    @unless ($preview)
        @include('sites.partials.gate', ['site' => $site, 'palette' => $palette])
    @endunless

    <div data-card class="relative z-10 mx-auto max-w-lg px-6 pb-20">

        {{-- Cover --}}
        <section class="relative flex min-h-[92vh] flex-col items-center justify-center text-center">
            @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'top-left'])
            @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'top-right'])
            @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'bottom-left'])
            @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'bottom-right'])

            <div class="relative">
                @if ($site->cover_image)
                    <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt=""
                         class="mx-auto mb-8 size-40 rounded-full object-cover shadow-xl ring-4 {{ $palette['ring'] }} sm:size-48">
                @endif

                <p class="text-[11px] tracking-[0.42em] uppercase {{ $palette['muted'] }}">{{ $palette['eyebrow'] }}</p>

                <p class="mt-7 leading-none" style="font-family: var(--font-script)">
                    <span class="block text-5xl sm:text-6xl {{ $palette['name'] }}">{{ $site->bride_name }}</span>
                    <span class="my-3 block text-3xl {{ $palette['amp'] }}">&amp;</span>
                    <span class="block text-5xl sm:text-6xl {{ $palette['name'] }}">{{ $site->groom_name }}</span>
                </p>

                <div class="mt-8">
                    @include('sites.partials.divider', ['tone' => $palette['floral'], 'accent' => $palette['accent']])
                </div>

                <p class="mt-6 text-lg tracking-[0.12em] {{ $palette['body'] }}">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
                @if ($site->startsAtLabel())
                    <p class="mt-1 text-sm {{ $palette['muted'] }}">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) &ndash; {{ $site->endsAtLabel() }}@endif</p>
                @endif

                <div class="mt-9">
                    @include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview, 'tone' => $tone])
                </div>
            </div>
        </section>

        {{-- Salutation --}}
        @if ($site->salutation || $site->bride_parents || $site->groom_parents)
            <section data-reveal class="py-16 text-center">
                @if ($site->salutation)
                    <p class="text-lg leading-relaxed {{ $palette['body'] }}">{{ $site->salutation }}</p>
                @endif

                @if ($site->bride_parents || $site->groom_parents)
                    <div class="mt-8 space-y-1">
                        @if ($site->bride_parents)<p class="font-display text-xl {{ $palette['name'] }}">{{ $site->bride_parents }}</p>@endif
                        @if ($site->bride_parents && $site->groom_parents)<p class="{{ $palette['muted'] }}">&amp;</p>@endif
                        @if ($site->groom_parents)<p class="font-display text-xl {{ $palette['name'] }}">{{ $site->groom_parents }}</p>@endif
                    </div>
                @endif

                @if ($site->invitation_note)
                    <p class="mt-8 leading-relaxed {{ $palette['body'] }}">{{ $site->invitation_note }}</p>
                @endif
            </section>
        @endif

        {{-- Countdown --}}
        <section data-reveal class="py-14 text-center">
            @include('sites.partials.divider', ['tone' => $palette['floral'], 'accent' => $palette['accent']])
            <p class="mt-6 mb-6 text-[11px] tracking-[0.35em] uppercase {{ $palette['muted'] }}">Menanti hari bahagia</p>
            @include('sites.partials.countdown', ['site' => $site, 'tone' => $tone])
        </section>

        {{-- Venue --}}
        @if ($site->venue_name || $site->venue_address)
            <section data-reveal class="py-14 text-center">
                <p class="text-[11px] tracking-[0.35em] uppercase {{ $palette['muted'] }}">Lokasi majlis</p>
                <div class="relative mt-6 overflow-hidden rounded-3xl border p-8 {{ $palette['panel'] }}">
                    @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'bottom-right'])
                    <div class="relative">
                        @if ($site->venue_name)<h2 class="font-display text-2xl {{ $palette['name'] }}">{{ $site->venue_name }}</h2>@endif
                        @if ($site->venue_address)<p class="mt-3 leading-relaxed {{ $palette['body'] }}">{{ $site->venue_address }}</p>@endif
                        @if ($site->map_url)
                            <a href="{{ $site->map_url }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-medium {{ $palette['button'] }}">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                Buka peta
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Itinerary --}}
        @if (filled($site->itinerary))
            <section id="atur-cara" data-reveal class="scroll-mt-8 py-14 text-center">
                <p class="text-[11px] tracking-[0.35em] uppercase {{ $palette['muted'] }}">Atur cara majlis</p>
                @include('sites.partials.divider', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'class' => 'mt-5'])

                <ol class="relative mt-8 space-y-6 border-l pl-8 text-left {{ $palette['rail'] }}">
                    @foreach ($site->itinerary as $row)
                        <li class="relative">
                            <span class="absolute top-1.5 -left-[2.15rem] size-3 rounded-full {{ $palette['dot'] }}"></span>
                            <p class="text-sm tracking-wide {{ $palette['muted'] }}">{{ $row['time'] }}</p>
                            <p class="font-display text-lg {{ $palette['name'] }}">{{ $row['label'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        {{-- RSVP --}}
        @if ($site->acceptsRsvps())
            <section id="rsvp" data-reveal class="scroll-mt-8 py-14 text-center">
                <p class="text-[11px] tracking-[0.35em] uppercase {{ $palette['muted'] }}">RSVP</p>
                <h2 class="mt-3 mb-2 font-display text-2xl {{ $palette['name'] }}">Sahkan kehadiran anda</h2>
                <p class="mb-7 text-sm {{ $palette['body'] }}">Maklum balas anda memudahkan kami menyediakan jamuan.</p>

                <div class="relative overflow-hidden rounded-3xl border p-7 {{ $palette['panel'] }}">
                    @include('sites.partials.florals', ['tone' => $palette['floral'], 'accent' => $palette['accent'], 'position' => 'top-right'])
                    <div class="relative">
                        @include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview, 'tone' => $tone])
                    </div>
                </div>
            </section>
        @endif

        {{-- Contacts --}}
        @if (filled($site->contacts))
            <section data-reveal class="py-14 text-center">
                <p class="text-[11px] tracking-[0.35em] uppercase {{ $palette['muted'] }}">Hubungi kami</p>
                <ul class="mt-6 flex flex-col gap-3">
                    @foreach ($site->contacts as $contact)
                        <li>
                            <a href="tel:{{ $contact['phone'] }}" class="flex items-center justify-between gap-4 rounded-2xl border px-5 py-3 text-left transition {{ $palette['panel'] }}">
                                <span class="{{ $palette['name'] }}">{{ $contact['name'] }}</span>
                                <span class="text-sm {{ $palette['muted'] }}">{{ $contact['phone'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Closing --}}
        <section data-reveal class="pt-14 pb-6 text-center">
            @include('sites.partials.divider', ['tone' => $palette['floral'], 'accent' => $palette['accent']])
            @if ($site->closing_note)
                <p class="mt-8 leading-relaxed {{ $palette['body'] }} italic">{{ $site->closing_note }}</p>
            @endif
            <p class="mt-8 leading-none {{ $palette['amp'] }}" style="font-family: var(--font-script)">
                <span class="text-3xl">{{ $site->bride_name }} &amp; {{ $site->groom_name }}</span>
            </p>
        </section>
    </div>
</div>
