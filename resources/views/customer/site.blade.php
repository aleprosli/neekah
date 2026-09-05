@php
    $exists = $site->exists;
    $itinerary = old('itinerary', $site->itinerary ?? []);
    $contacts = old('contacts', $site->contacts ?? []);
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
                    <p class="mt-1 text-xs text-ink-muted">{{ number_format($site->views) }} tontonan · {{ $rsvpCount }} tetamu mengesahkan kehadiran</p>
                @else
                    <p class="mt-1 text-sm text-ink-muted">Kad anda akan berada di {{ $site->subdomain }}.{{ $domain }} selepas disiarkan.</p>
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
        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Template</h2>
                <p class="text-sm text-ink-muted">Tekan nama template untuk melihat contoh penuh dalam tab baharu.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($templates as $slug => $template)
                    <label class="cursor-pointer">
                        <input type="radio" name="template" value="{{ $slug }}" class="peer sr-only" @checked(old('template', $site->template) === $slug)>
                        <span class="flex flex-col gap-2 rounded-2xl border border-line p-2 transition peer-checked:border-brand-600 peer-checked:ring-2 peer-checked:ring-brand-400/40 hover:border-brand-300">
                            <span class="flex aspect-[4/3] items-center justify-center rounded-xl bg-linear-to-br text-center text-white {{ $template['palette'] }}">
                                <span class="font-display text-sm font-semibold">Aina &amp; Hakim</span>
                            </span>
                            <span class="px-1 pb-1">
                                <span class="block text-sm font-semibold">{{ $template['name'] }}</span>
                                <span class="block text-xs text-ink-muted">{{ $template['description'] }}</span>
                                <a href="{{ route('sites.templates.show', $slug) }}" target="_blank" class="mt-1 inline-block text-xs font-medium text-brand-600 underline underline-offset-4">Lihat contoh</a>
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
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

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $exists ? 'Simpan kad' : 'Cipta kad jemputan' }}</button>
            <a href="{{ route('site.preview') }}" target="_blank" class="rounded-full border border-line px-6 py-3 text-sm font-medium transition hover:border-brand-400">Pratonton</a>
        </div>
    </form>

    {{-- RSVP list --}}
    @if ($exists && $site->rsvps()->exists())
        <section class="mt-10 flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Senarai RSVP ({{ $site->rsvps()->count() }})</h2>
            <div class="overflow-x-auto rounded-2xl border border-line">
                <table class="w-full text-sm">
                    <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Nama</th>
                            <th class="px-4 py-3 font-semibold">Telefon</th>
                            <th class="px-4 py-3 font-semibold">Kehadiran</th>
                            <th class="px-4 py-3 text-right font-semibold">Pax</th>
                            <th class="px-4 py-3 font-semibold">Ucapan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($site->rsvps as $rsvp)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $rsvp->name }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ $rsvp->phone }}</td>
                                <td class="px-4 py-3">
                                    <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $rsvp->attending, 'bg-surface-muted text-ink-muted' => ! $rsvp->attending])>{{ $rsvp->attending ? 'Hadir' : 'Tidak hadir' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">{{ $rsvp->attending ? $rsvp->pax : '—' }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ $rsvp->message }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.customer>
