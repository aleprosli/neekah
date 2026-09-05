@props(['site', 'template', 'preview' => false, 'guest' => null])

@php
    $ornament = $template->ornament();
    $eyebrow = $template->eyebrow();
    $tone = $template->isDark() ? 'dark' : 'light';
@endphp

<div class="nk-card relative min-h-screen overflow-hidden" style="{{ $template->cssVariables() }}">
    @include('sites.partials.motion', ['template' => $template])

    @unless ($preview)
        @include('sites.partials.gate', ['site' => $site, 'template' => $template, 'eyebrow' => $eyebrow, 'guest' => $guest])
    @endunless

    <div data-card class="relative z-10 mx-auto max-w-lg pb-20">

        @include('sites.layouts.'.$template->layout(), ['site' => $site, 'preview' => $preview, 'ornament' => $ornament, 'eyebrow' => $eyebrow])

        <div class="px-6">
            {{-- Salutation --}}
            @if ($site->salutation || $site->bride_parents || $site->groom_parents || $site->invitation_note)
                <section data-reveal class="py-16 text-center">
                    @if ($site->salutation)<p class="nk-body text-lg leading-relaxed">{{ $site->salutation }}</p>@endif
                    @if ($site->bride_parents || $site->groom_parents)
                        <div class="mt-8 space-y-1">
                            @if ($site->bride_parents)<p class="nk-name text-xl">{{ $site->bride_parents }}</p>@endif
                            @if ($site->bride_parents && $site->groom_parents)<p class="nk-muted">&amp;</p>@endif
                            @if ($site->groom_parents)<p class="nk-name text-xl">{{ $site->groom_parents }}</p>@endif
                        </div>
                    @endif
                    @if ($site->invitation_note)<p class="nk-body mt-8 leading-relaxed">{{ $site->invitation_note }}</p>@endif
                </section>
            @endif

            {{-- Countdown --}}
            <section data-reveal class="py-14 text-center">
                @include('sites.partials.divider-css')
                <p class="nk-eyebrow mt-6 mb-6">Menanti hari bahagia</p>
                @include('sites.partials.countdown', ['site' => $site])
            </section>

            {{-- Venue --}}
            @if ($site->venue_name || $site->venue_address)
                <section data-reveal class="py-14 text-center">
                    <p class="nk-eyebrow">Lokasi majlis</p>
                    <div class="nk-panel relative mt-6 overflow-hidden rounded-3xl p-8">
                        @include('sites.ornaments.'.$ornament, ['position' => 'bottom-right'])
                        <div class="relative">
                            @if ($site->venue_name)<h2 class="nk-name text-2xl">{{ $site->venue_name }}</h2>@endif
                            @if ($site->venue_address)<p class="nk-body mt-3 leading-relaxed">{{ $site->venue_address }}</p>@endif
                            @if ($site->map_url)
                                <a href="{{ $site->map_url }}" target="_blank" rel="noopener" class="nk-button mt-6 inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-medium">
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
                    <p class="nk-eyebrow">Atur cara majlis</p>
                    @include('sites.partials.divider-css', ['class' => 'mt-5'])
                    <ol class="nk-hairline relative mt-8 space-y-6 border-l pl-8 text-left">
                        @foreach ($site->itinerary as $row)
                            <li class="relative">
                                <span class="nk-dot absolute top-1.5 -left-[2.15rem] size-3 rounded-full"></span>
                                <p class="nk-muted text-sm tracking-wide">{{ $row['time'] }}</p>
                                <p class="nk-name text-lg">{{ $row['label'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- RSVP --}}
            @if ($site->acceptsRsvps())
                <section id="rsvp" data-reveal class="scroll-mt-8 py-14 text-center">
                    <p class="nk-eyebrow">RSVP</p>
                    <h2 class="nk-name mt-3 mb-2 text-2xl">Sahkan kehadiran anda</h2>
                    <p class="nk-body mb-7 text-sm">Maklum balas anda memudahkan kami menyediakan jamuan.</p>
                    <div class="nk-panel relative overflow-hidden rounded-3xl p-7">
                        @include('sites.ornaments.'.$ornament, ['position' => 'top-right'])
                        <div class="relative">@include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview, 'guest' => $guest])</div>
                    </div>
                </section>
            @endif

            {{-- Money gift --}}
            @if ($site->showsGift())
                <section id="hadiah" data-reveal class="scroll-mt-8 py-14 text-center">
                    <p class="nk-eyebrow">Salam kaut</p>
                    <h2 class="nk-name mt-3 mb-2 text-2xl">Hadiah untuk pengantin</h2>
                    @if ($site->gift_note)
                        <p class="nk-body mb-7 text-sm">{{ $site->gift_note }}</p>
                    @endif
                    <div class="nk-panel relative overflow-hidden rounded-3xl p-7">
                        @include('sites.ornaments.'.$ornament, ['position' => 'top-right'])
                        <div class="relative flex flex-col items-center gap-6">
                            @if ($site->giftQrUrl())
                                <img src="{{ $site->giftQrUrl() }}" alt="Kod QR DuitNow" class="w-48 rounded-2xl bg-white p-3">
                            @endif
                            @foreach ($site->gift_accounts ?? [] as $account)
                                <div class="w-full">
                                    <p class="nk-muted text-xs tracking-wide uppercase">{{ $account['bank'] }}</p>
                                    <p class="nk-name mt-1 text-lg">{{ $account['number'] }}</p>
                                    <p class="nk-body text-sm">{{ $account['holder'] }}</p>
                                    <button type="button" data-copy="{{ $account['number'] }}" class="nk-button mt-3 rounded-full px-6 py-2 text-xs font-semibold">Salin nombor akaun</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- Photo gallery --}}
            @if ($site->relationLoaded('photos') ? $site->photos->isNotEmpty() : $site->photos()->exists())
                <section id="galeri" data-reveal class="scroll-mt-8 py-14 text-center">
                    <p class="nk-eyebrow">Galeri</p>
                    @include('sites.partials.divider-css', ['class' => 'mt-5'])
                    <div class="mt-8 grid grid-cols-2 gap-3">
                        @foreach ($site->photos as $photo)
                            <figure class="overflow-hidden rounded-2xl">
                                <img src="{{ $photo->url() }}" alt="{{ $photo->caption ?? 'Gambar pengantin' }}" loading="lazy" class="h-40 w-full object-cover sm:h-56">
                                @if ($photo->caption)<figcaption class="nk-muted mt-1.5 text-xs">{{ $photo->caption }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Wishes --}}
            @if ($site->wishes_enabled && $site->approvedWishes()->exists())
                <section id="ucapan" data-reveal class="scroll-mt-8 py-14 text-center">
                    <p class="nk-eyebrow">Ucapan</p>
                    <h2 class="nk-name mt-3 mb-7 text-2xl">Doa dan restu</h2>
                    <ul class="flex flex-col gap-3 text-left">
                        @foreach ($site->approvedWishes as $wish)
                            <li class="nk-panel rounded-2xl px-5 py-4">
                                <p class="nk-body text-sm leading-relaxed">{{ $wish->message }}</p>
                                <p class="nk-muted mt-2 text-xs">— {{ $wish->name }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Contacts --}}
            @if (filled($site->contacts))
                <section data-reveal class="py-14 text-center">
                    <p class="nk-eyebrow">Hubungi kami</p>
                    <ul class="mt-6 flex flex-col gap-3">
                        @foreach ($site->contacts as $contact)
                            <li>
                                <a href="tel:{{ $contact['phone'] }}" class="nk-panel flex items-center justify-between gap-4 rounded-2xl px-5 py-3 text-left">
                                    <span class="nk-name">{{ $contact['name'] }}</span>
                                    <span class="nk-muted text-sm">{{ $contact['phone'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Closing --}}
            <section data-reveal class="pt-14 pb-6 text-center">
                @include('sites.partials.divider-css')
                @if ($site->closing_note)<p class="nk-body mt-8 leading-relaxed italic">{{ $site->closing_note }}</p>@endif
                <p class="nk-script nk-accent mt-8 text-3xl">{{ $site->bride_name }} &amp; {{ $site->groom_name }}</p>
            </section>
        </div>
    </div>
</div>
