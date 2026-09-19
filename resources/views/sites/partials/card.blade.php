@props(['site', 'template', 'preview' => false, 'guest' => null])

@php
    $ornament = $template->ornament();
    $eyebrow = $template->eyebrow();
@endphp

{{-- One sheet of card stock with a double rule round its edge. On a phone the
     sheet is the screen; from sm up it lies on a darker "table" like a real kad. --}}
<div class="nk-card nk-table relative min-h-screen overflow-hidden" style="{{ $template->cssVariables() }}">
    @include('sites.partials.motion', ['template' => $template])

    @unless ($preview)
        @include('sites.partials.gate', ['site' => $site, 'template' => $template, 'eyebrow' => $eyebrow, 'guest' => $guest])
    @endunless

    <div data-card class="nk-sheet nk-paper relative z-10 mx-auto max-w-md overflow-hidden pb-28">
        <div class="nk-sheet-frame"></div>

        @include('sites.layouts.'.$template->layout(), ['site' => $site, 'template' => $template, 'preview' => $preview, 'ornament' => $ornament, 'eyebrow' => $eyebrow])

        <div class="relative z-[2] px-8 sm:px-10">
            {{-- Salutation and parents --}}
            @if ($site->salutation || $site->bride_parents || $site->groom_parents || $site->invitation_note)
                <section data-reveal class="py-12 text-center">
                    @include('sites.partials.divider-css')
                    @if ($site->salutation)<p class="nk-body mt-8 text-lg leading-relaxed">{{ $site->salutation }}</p>@endif
                    @if ($site->bride_parents || $site->groom_parents)
                        <div class="mt-8 flex flex-col gap-2">
                            @if ($site->bride_parents)<p class="nk-name text-xl leading-snug font-medium">{{ $site->bride_parents }}</p>@endif
                            @if ($site->bride_parents && $site->groom_parents)<p class="nk-script nk-accent text-3xl">&amp;</p>@endif
                            @if ($site->groom_parents)<p class="nk-name text-xl leading-snug font-medium">{{ $site->groom_parents }}</p>@endif
                        </div>
                    @endif
                    @if ($site->invitation_note)<p class="nk-body mt-8 leading-relaxed">{{ $site->invitation_note }}</p>@endif
                </section>
            @endif

            {{-- Countdown --}}
            <section data-reveal class="py-12 text-center">
                @include('sites.partials.section-heading', ['eyebrow' => 'Menanti hari bahagia', 'title' => 'Menghitung Hari'])
                <div class="mt-8">@include('sites.partials.countdown', ['site' => $site])</div>
            </section>

            {{-- Venue --}}
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

            {{-- Itinerary, centred the way it is printed on the back of a kad --}}
            @if (filled($site->itinerary))
                <section id="atur-cara" data-reveal class="scroll-mt-8 py-12 text-center">
                    @include('sites.partials.section-heading', ['eyebrow' => 'Aturcara', 'title' => 'Atur Cara Majlis'])
                    <ol class="mt-8 flex flex-col items-center gap-5">
                        @foreach ($site->itinerary as $row)
                            <li class="flex flex-col items-center">
                                @unless ($loop->first)<span class="nk-dot mb-5 size-1.5 rotate-45" aria-hidden="true"></span>@endunless
                                <p class="nk-muted text-xs tracking-[0.25em] uppercase">{{ $row['time'] }}</p>
                                <p class="nk-name mt-1 text-xl">{{ $row['label'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- RSVP --}}
            @if ($site->acceptsRsvps())
                <section id="rsvp" data-reveal class="scroll-mt-8 py-12 text-center">
                    @include('sites.partials.section-heading', ['eyebrow' => 'RSVP', 'title' => 'Kehadiran'])
                    <p class="nk-body mt-5 mb-7 text-sm">Sahkan kehadiran anda &mdash; maklum balas anda memudahkan kami menyediakan jamuan.</p>
                    <div class="nk-plaque px-5 py-7">
                        @include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview, 'guest' => $guest])
                    </div>
                </section>
            @endif

            {{-- Money gift --}}
            @if ($site->showsGift())
                <section id="hadiah" data-reveal class="scroll-mt-8 py-12 text-center">
                    @include('sites.partials.section-heading', ['eyebrow' => 'Salam kaut', 'title' => 'Hadiah'])
                    @if ($site->gift_note)
                        <p class="nk-body mt-5 text-sm">{{ $site->gift_note }}</p>
                    @endif
                    <div class="nk-plaque mt-7 flex flex-col items-center gap-6 px-5 py-7">
                        @if ($site->giftQrUrl())
                            <img src="{{ $site->giftQrUrl() }}" alt="Kod QR DuitNow" class="w-48 rounded-lg bg-white p-3">
                        @endif
                        @foreach ($site->gift_accounts ?? [] as $account)
                            <div class="w-full">
                                <p class="nk-muted text-xs tracking-[0.2em] uppercase">{{ $account['bank'] }}</p>
                                <p class="nk-name mt-1 text-xl tabular-nums">{{ $account['number'] }}</p>
                                <p class="nk-body text-sm">{{ $account['holder'] }}</p>
                                <button type="button" data-copy="{{ $account['number'] }}" class="nk-button mt-3 rounded-full px-6 py-2 text-xs font-semibold">Salin nombor akaun</button>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Photo gallery --}}
            @if ($site->relationLoaded('photos') ? $site->photos->isNotEmpty() : $site->photos()->exists())
                <section id="galeri" data-reveal class="scroll-mt-8 py-12 text-center">
                    @include('sites.partials.section-heading', ['eyebrow' => 'Galeri', 'title' => 'Kenangan'])
                    <div class="mt-8 grid grid-cols-2 gap-2">
                        @foreach ($site->photos as $photo)
                            <figure @class(['overflow-hidden', 'col-span-2' => $loop->first && $site->photos->count() % 2 === 1])>
                                <img src="{{ \App\Actions\StoreOptimizedImage::thumbnailUrl($photo->path) }}" alt="{{ $photo->caption ?? 'Gambar pengantin' }}" loading="lazy" decoding="async" @class(['w-full object-cover', 'h-56' => $loop->first && $site->photos->count() % 2 === 1, 'h-40 sm:h-48' => ! ($loop->first && $site->photos->count() % 2 === 1)])>
                                @if ($photo->caption)<figcaption class="nk-muted mt-1.5 text-xs">{{ $photo->caption }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Wishes --}}
            @if ($site->wishes_enabled && $site->approvedWishes()->exists())
                <section id="ucapan" data-reveal class="scroll-mt-8 py-12 text-center">
                    @include('sites.partials.section-heading', ['eyebrow' => 'Ucapan', 'title' => 'Doa & Restu'])
                    <ul class="mt-8 flex flex-col gap-6">
                        @foreach ($site->approvedWishes as $wish)
                            <li>
                                <p class="nk-body leading-relaxed italic">&ldquo;{{ $wish->message }}&rdquo;</p>
                                <p class="nk-muted mt-2 text-xs tracking-[0.2em] uppercase">{{ $wish->name }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Contacts --}}
            @if (filled($site->contacts))
                <section id="hubungi" data-reveal class="scroll-mt-8 py-12 text-center">
                    @include('sites.partials.section-heading', ['eyebrow' => 'Untuk pertanyaan', 'title' => 'Hubungi'])
                    <ul class="mt-8 flex flex-col gap-3">
                        @foreach ($site->contacts as $contact)
                            <li>
                                <a href="tel:{{ $contact['phone'] }}" class="nk-plaque flex items-center justify-between gap-4 px-5 py-3.5 text-left">
                                    <span class="nk-name text-lg">{{ $contact['name'] }}</span>
                                    <span class="nk-muted text-sm tabular-nums">{{ $contact['phone'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Closing --}}
            <section data-reveal class="pt-12 pb-6 text-center">
                @include('sites.partials.divider-css')
                @if ($site->closing_note)<p class="nk-body mt-8 leading-relaxed italic">{{ $site->closing_note }}</p>@endif
                <div class="mt-10">@include('sites.partials.monogram', ['site' => $site, 'size' => 'size-16 text-2xl'])</div>
                <p class="nk-script nk-name mt-6 text-4xl">{{ $site->bride_name }} <span class="nk-accent">&amp;</span> {{ $site->groom_name }}</p>
            </section>
        </div>
    </div>

    @include('sites.partials.dock', ['site' => $site, 'preview' => $preview])
</div>
