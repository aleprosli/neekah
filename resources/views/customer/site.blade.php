@php
    $exists = $site->exists;
    $itinerary = old('itinerary', $site->itinerary ?? []);
    $contacts = old('contacts', $site->contacts ?? []);
    $giftAccounts = old('gift_accounts', $site->gift_accounts ?? []);
@endphp

<x-layouts.customer title="Kad jemputan" heading="Kad jemputan digital" subheading="Isi maklumat majlis, pilih template dan siarkan pada alamat web anda sendiri.">
    <x-slot:actions>
        <a href="{{ route('sites.templates') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat semua template</a>
        <a href="{{ route('site.preview') }}" target="_blank" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Pratonton</a>
    </x-slot:actions>

    {{-- Status --}}
    @if ($exists)
        <div @class([
            'mb-6 flex flex-col gap-3 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between',
            'border-emerald-200 bg-emerald-50' => $site->is_published,
            'border-line bg-surface-raised' => ! $site->is_published,
        ])>
            <div class="min-w-0">
                <p class="flex items-center gap-2 text-sm font-semibold">
                    <span @class(['size-2 rounded-full', 'bg-emerald-500' => $site->is_published, 'bg-ink-muted' => ! $site->is_published])></span>
                    {{ $site->is_published ? 'Tersiar' : 'Draf, belum tersiar' }}
                </p>
                @if ($site->is_published)
                    <a href="{{ $site->url() }}" target="_blank" class="mt-1 block truncate text-sm text-brand-700 underline underline-offset-4">{{ $site->url() }}</a>
                    <p class="mt-1 text-xs text-ink-muted">{{ number_format($site->views) }} tontonan · <a href="{{ route('guests.index') }}" class="hover:text-ink">{{ $rsvpCount }} tetamu mengesahkan kehadiran</a></p>
                @else
                    <p class="mt-1 text-sm break-words text-ink-muted">Kad anda akan berada di {{ $site->subdomain }}.{{ $domain }} selepas disiarkan.</p>
                @endif
            </div>
            <form method="POST" action="{{ route('weddings.site.publish', $wedding) }}" class="shrink-0">
                @csrf
                @method('PUT')
                <input type="hidden" name="published" value="{{ $site->is_published ? 0 : 1 }}">
                <button type="submit" @class([
                    'rounded-full px-5 py-2.5 text-sm font-semibold transition',
                    'border border-line hover:border-brand-400' => $site->is_published,
                    'bg-brand-600 text-white hover:bg-brand-700' => ! $site->is_published,
                ])>{{ $site->is_published ? 'Tarik balik' : 'Siarkan kad' }}</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('weddings.site.update', $wedding) }}" enctype="multipart/form-data" class="flex flex-col gap-8">
        @csrf
        @method('PUT')

        {{-- Template --}}
        <section class="flex flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Template</h2>
                    <p class="text-sm text-ink-muted">{{ $templates->flatten()->count() }} reka bentuk. Tekan "Lihat contoh" untuk membuka satu kad penuh.</p>
                </div>
                <a href="{{ route('sites.templates') }}" target="_blank" class="text-sm font-medium text-brand-600 underline underline-offset-4">Layari galeri</a>
            </div>

            @foreach ($templates as $style => $group)
                <div>
                    <p class="mb-3 text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $style }}</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                        @foreach ($group as $template)
                            <label class="cursor-pointer">
                                <input type="radio" name="template" value="{{ $template->slug }}" class="peer sr-only" @checked(old('template', $site->template) === $template->slug)>
                                <span class="flex flex-col gap-1.5 rounded-2xl border border-line p-1.5 transition peer-checked:border-brand-600 peer-checked:ring-2 peer-checked:ring-brand-400/40 hover:border-brand-300">
                                    @include('sites.partials.thumbnail', ['template' => $template])
                                    <span class="px-1 pb-0.5">
                                        <span class="block truncate text-xs font-semibold">{{ $template->name }}</span>
                                        <a href="{{ route('sites.templates.show', $template) }}" target="_blank" class="text-[11px] text-brand-600 underline underline-offset-2">Lihat contoh</a>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>

        {{-- Address --}}
        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Alamat web</h2>
                <p class="text-sm text-ink-muted">Inilah pautan yang anda kongsi dengan tetamu.</p>
            </div>
            <label class="flex flex-col gap-1.5">
                <span class="sr-only">Alamat web</span>
                <span class="flex items-center overflow-hidden rounded-xl border border-line bg-surface focus-within:border-brand-400">
                    <input type="text" name="subdomain" value="{{ old('subdomain', $site->subdomain) }}" required class="min-w-0 flex-1 bg-transparent px-4 py-2.5 text-sm focus:outline-none" placeholder="ainapilihhakim">
                    <span class="shrink-0 border-l border-line bg-surface-muted px-3 py-2.5 text-sm text-ink-muted">.{{ $domain }}</span>
                </span>
                <span class="text-xs text-ink-muted">Huruf kecil, nombor dan sengkang sahaja. Contoh: ainapilihhakim</span>
            </label>
        </section>

        {{-- Couple --}}
        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Pengantin</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field label="Nama pengantin perempuan" name="bride_name" :value="$site->bride_name" required />
                <x-form.field label="Nama pengantin lelaki" name="groom_name" :value="$site->groom_name" required />
                <x-form.field label="Ibu bapa pengantin perempuan" name="bride_parents" :value="$site->bride_parents" placeholder="Zulkifli bin Hassan & Rohana binti Ahmad" />
                <x-form.field label="Ibu bapa pengantin lelaki" name="groom_parents" :value="$site->groom_parents" placeholder="Ismail bin Yusof & Salmah binti Osman" />
            </div>
            <x-form.textarea label="Kata aluan" name="salutation" :value="$site->salutation" rows="3" />
            <x-form.textarea label="Nota jemputan (pilihan)" name="invitation_note" :value="$site->invitation_note" rows="3" placeholder="Doa dan restu daripada tuan/puan amat bermakna." />
        </section>

        {{-- Event --}}
        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Majlis</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.field label="Tarikh" name="event_date" type="date" :value="$site->event_date?->toDateString()" required />
                <x-form.field label="Mula" name="starts_at" type="time" :value="$site->starts_at ? \Illuminate\Support\Carbon::parse($site->starts_at)->format('H:i') : null" />
                <x-form.field label="Tamat" name="ends_at" type="time" :value="$site->ends_at ? \Illuminate\Support\Carbon::parse($site->ends_at)->format('H:i') : null" />
            </div>
            <x-form.field label="Nama tempat" name="venue_name" :value="$site->venue_name" placeholder="Dewan Seri Melati" />
            <x-form.textarea label="Alamat penuh" name="venue_address" :value="$site->venue_address" rows="2" />
            <x-form.field label="Pautan peta" name="map_url" type="url" :value="$site->map_url" placeholder="https://maps.google.com/..." />
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Gambar utama (pilihan)</span>
                @if ($site->cover_image)
                    <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="h-32 w-full max-w-xs rounded-xl object-cover">
                @endif
                <input type="file" name="cover_image" accept="image/*" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700">
            </div>
        </section>

        {{-- Itinerary --}}
        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Atur cara</h2>
                <p class="text-sm text-ink-muted">Biarkan kosong untuk baris yang tidak diperlukan.</p>
            </div>
            @for ($i = 0; $i < 6; $i++)
                <div class="grid gap-3 sm:grid-cols-[10rem_1fr]">
                    <input type="text" name="itinerary[{{ $i }}][time]" value="{{ $itinerary[$i]['time'] ?? '' }}" placeholder="11:00 pagi" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input type="text" name="itinerary[{{ $i }}][label]" value="{{ $itinerary[$i]['label'] ?? '' }}" placeholder="Ketibaan tetamu" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </div>
            @endfor
        </section>

        {{-- Contacts + RSVP --}}
        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Hubungi &amp; RSVP</h2>
            @for ($i = 0; $i < 3; $i++)
                <div class="grid gap-3 sm:grid-cols-2">
                    <input type="text" name="contacts[{{ $i }}][name]" value="{{ $contacts[$i]['name'] ?? '' }}" placeholder="Puan Rohana" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input type="tel" name="contacts[{{ $i }}][phone]" value="{{ $contacts[$i]['phone'] ?? '' }}" placeholder="012-345 6789" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </div>
            @endfor

            <label class="flex items-center gap-2 border-t border-line pt-4 text-sm">
                <input type="hidden" name="rsvp_enabled" value="0">
                <input type="checkbox" name="rsvp_enabled" value="1" class="accent-brand-600" @checked(old('rsvp_enabled', $site->rsvp_enabled))>
                Benarkan tetamu mengesahkan kehadiran (RSVP)
            </label>
            <x-form.field label="Tarikh akhir RSVP (pilihan)" name="rsvp_deadline" type="date" :value="$site->rsvp_deadline?->toDateString()" class="sm:w-56" />
            <x-form.textarea label="Nota penutup" name="closing_note" :value="$site->closing_note" rows="2" />
        </section>

        {{-- Money gift --}}
        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Salam kaut</h2>
                <p class="text-sm text-ink-muted">Kod QR DuitNow dan nombor akaun untuk tetamu yang ingin memberi hadiah.</p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="gift_enabled" value="0">
                <input type="checkbox" name="gift_enabled" value="1" class="accent-brand-600" @checked(old('gift_enabled', $site->gift_enabled))>
                Papar bahagian hadiah pada kad
            </label>

            <x-form.textarea label="Nota hadiah (pilihan)" name="gift_note" :value="$site->gift_note" rows="2" />

            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Kod QR DuitNow</span>
                <input type="file" name="gift_qr_image" accept="image/*" class="text-sm">
                @if ($site->giftQrUrl())
                    <img src="{{ $site->giftQrUrl() }}" alt="Kod QR DuitNow" class="mt-2 w-32 rounded-xl border border-line">
                @endif
            </label>

            @for ($i = 0; $i < 3; $i++)
                <div class="grid gap-3 sm:grid-cols-3">
                    <input type="text" name="gift_accounts[{{ $i }}][bank]" value="{{ $giftAccounts[$i]['bank'] ?? '' }}" placeholder="Maybank" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input type="text" name="gift_accounts[{{ $i }}][holder]" value="{{ $giftAccounts[$i]['holder'] ?? '' }}" placeholder="Nama pemegang akaun" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input type="text" name="gift_accounts[{{ $i }}][number]" value="{{ $giftAccounts[$i]['number'] ?? '' }}" placeholder="1234 5678 9012" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </div>
            @endfor

            <label class="flex items-center gap-2 border-t border-line pt-4 text-sm">
                <input type="hidden" name="wishes_enabled" value="0">
                <input type="checkbox" name="wishes_enabled" value="1" class="accent-brand-600" @checked(old('wishes_enabled', $site->wishes_enabled ?? true))>
                Papar ucapan tetamu yang anda luluskan pada kad
            </label>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $exists ? 'Simpan kad' : 'Cipta kad jemputan' }}</button>
            <a href="{{ route('site.preview') }}" target="_blank" class="rounded-full border border-line px-6 py-3 text-sm font-medium transition hover:border-brand-400">Pratonton</a>
        </div>
    </form>

    {{-- Gallery. Its own form because it uploads files independently of the
         card fields above. --}}
    @if ($exists)
        <section class="mt-10 flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Galeri gambar</h2>
            <form method="POST" action="{{ route('weddings.site.photos.store', $wedding) }}" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                @csrf
                <input type="file" name="images[]" accept="image/*" multiple required class="text-sm">
                <input type="text" name="caption" placeholder="Kapsyen (pilihan)" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Muat naik gambar</button>
            </form>

            @if ($site->photos->isNotEmpty())
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($site->photos as $photo)
                        <li class="flex flex-col gap-1.5">
                            <img src="{{ $photo->url() }}" alt="{{ $photo->caption ?? '' }}" class="h-32 w-full rounded-xl object-cover">
                            <x-confirm-action
                                :action="route('weddings.site.photos.destroy', [$wedding, $photo])"
                                method="DELETE"
                                tone="danger"
                                title="Padam gambar ini?"
                                message="Gambar akan dibuang dari galeri kad jemputan."
                                confirm="Padam"
                                trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                            >Padam</x-confirm-action>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    {{-- Wishes. Guests write these in the RSVP form, so nothing appears on the
         card until the couple approves it. --}}
    @if ($exists && $wishes->isNotEmpty())
        <section class="mt-10 flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Ucapan tetamu</h2>
            <p class="text-sm text-ink-muted">Hanya ucapan yang anda luluskan akan dipaparkan pada kad jemputan.</p>
            <ul class="flex flex-col gap-3">
                @foreach ($wishes as $wish)
                    <li class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm italic text-ink-muted">“{{ $wish->message }}”</p>
                            <p class="mt-1 text-sm font-medium">{{ $wish->name }}</p>
                        </div>
                        <form method="POST" action="{{ route('weddings.rsvps.update', [$wedding, $wish]) }}" class="shrink-0">
                            @csrf @method('PUT')
                            <input type="hidden" name="approve_message" value="{{ $wish->wishIsPublic() ? 0 : 1 }}">
                            <button type="submit" @class(['rounded-full px-5 py-2 text-xs font-semibold transition', 'border border-line hover:border-brand-400' => $wish->wishIsPublic(), 'bg-brand-600 text-white hover:bg-brand-700' => ! $wish->wishIsPublic()])>{{ $wish->wishIsPublic() ? 'Sembunyikan' : 'Luluskan' }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Replies live on the guest page, so there is only one place the
         headcount can be read and only one number to trust. --}}
    @if ($exists && $site->rsvps()->exists())
        <section class="mt-10 flex flex-col items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-display text-xl font-semibold">{{ $site->rsvps()->count() }} jawapan RSVP diterima</h2>
            <p class="text-sm text-ink-muted">Lihat siapa yang menjawab, siapa yang belum, dan jumlah kehadiran yang disahkan di halaman tetamu.</p>
            <a href="{{ route('guests.index') }}" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Buka senarai tetamu</a>
        </section>
    @endif
</x-layouts.customer>
