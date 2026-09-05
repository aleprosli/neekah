<x-layouts.customer title="Tetamu" heading="Senarai tetamu" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y')">
    {{-- Two numbers, never one. The confirmed sum is what the caterer is told;
         the outstanding invitations are a ceiling, not an expectation. --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Sah hadir" :value="$confirmedPax.' orang'" hint="Dijumlahkan dari jawapan RSVP sahaja" />
        <x-stat-card label="Belum jawab" :value="'sehingga '.$awaitingPax.' orang'" hint="Had atas jemputan yang belum dijawab" />
        <x-stat-card label="Tidak hadir" :value="$declinedCount.' jawapan'" hint="{{ $guests->count() }} tetamu dalam senarai" />
    </div>

    @if (! $site?->is_published)
        <p class="mt-4 rounded-2xl border border-line bg-surface-muted p-4 text-sm text-ink-muted">
            Kad jemputan belum diterbitkan, jadi pautan peribadi belum boleh dikongsi.
            <a href="{{ route('site.edit') }}" class="font-medium text-brand-700 hover:underline">Sediakan kad jemputan</a>.
        </p>
    @endif

    @if (session('importErrors'))
        <ul class="mt-4 flex flex-col gap-1 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            @foreach (session('importErrors') as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        {{-- Add one guest --}}
        <form method="POST" action="{{ route('weddings.guests.store', $wedding) }}" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            @csrf
            <h2 class="font-display text-lg font-semibold">Tambah tetamu</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Nama</span>
                    <input type="text" name="name" value="{{ old('name') }}" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Nombor telefon</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="012-345 6789" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Pihak</span>
                    <select name="side" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        @foreach ($sides as $case)<option value="{{ $case->value }}" @selected(old('side') === $case->value)>{{ $case->label() }}</option>@endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Kumpulan</span>
                    <select name="group" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        @foreach ($groups as $case)<option value="{{ $case->value }}" @selected(old('group') === $case->value)>{{ $case->label() }}</option>@endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Jemputan (pax)</span>
                    <input type="number" name="pax_invited" value="{{ old('pax_invited', 1) }}" min="1" max="20" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>
            <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah tetamu</button>
        </form>

        {{-- Paste the list that already exists somewhere else --}}
        <form method="POST" action="{{ route('weddings.guests.import', $wedding) }}" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            @csrf
            <h2 class="font-display text-lg font-semibold">Tampal senarai sedia ada</h2>
            <p class="text-sm text-ink-muted">Satu tetamu satu baris: <code class="text-xs">nama, telefon, pihak, kumpulan, pax</code>. Medan selepas nama boleh dikosongkan.</p>
            <textarea name="rows" rows="6" placeholder="Aina Sofea, 0123456789, bride, family, 2&#10;Pak Long Rahim, , groom, family, 4" class="rounded-xl border border-line bg-surface px-4 py-2.5 font-mono text-xs focus:border-brand-400 focus:outline-none">{{ old('rows') }}</textarea>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Pihak (jika kosong)</span>
                    <select name="side" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        @foreach ($sides as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">Kumpulan (jika kosong)</span>
                    <select name="group" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        @foreach ($groups as $case)<option value="{{ $case->value }}" @selected($case->value === 'other')>{{ $case->label() }}</option>@endforeach
                    </select>
                </label>
            </div>
            <button type="submit" class="w-fit rounded-full border border-line px-6 py-2.5 text-sm font-semibold transition hover:border-brand-400">Import senarai</button>
        </form>
    </div>

    {{-- The list --}}
    <section class="mt-8">
        @if ($guests->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
                <span class="text-4xl">🧑‍🤝‍🧑</span>
                <h2 class="font-display text-xl font-semibold">Senarai tetamu masih kosong</h2>
                <p class="max-w-sm text-sm text-ink-muted">Tambah tetamu satu per satu, atau tampal senarai dari Excel atau WhatsApp. Setiap tetamu dapat pautan kad peribadi mereka sendiri.</p>
            </div>
        @else
            <ul class="flex flex-col gap-3">
                @foreach ($guests as $guest)
                    @php $rsvp = $guest->rsvp; $status = $guest->status(); @endphp
                    <li class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-start">
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-medium">
                                {{ $guest->name }}
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $status === \App\Enums\GuestStatus::Attending,
                                    'bg-red-100 text-red-800' => $status === \App\Enums\GuestStatus::Declined,
                                    'bg-surface-muted text-ink-muted' => ! in_array($status, [\App\Enums\GuestStatus::Attending, \App\Enums\GuestStatus::Declined], true),
                                ])>{{ $status->label() }}</span>
                                @if ($duplicateNames->contains(mb_strtolower($guest->name)))
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">Kemungkinan duplikasi</span>
                                @endif
                            </p>
                            <p class="flex flex-wrap items-center gap-x-3 text-sm text-ink-muted">
                                <span>{{ $guest->side->label() }} · {{ $guest->group->label() }}</span>
                                <span>Jemputan {{ $guest->pax_invited }} orang</span>
                                @if ($guest->phone)<span>{{ $guest->phone }}</span>@endif
                            </p>
                            @if ($rsvp)
                                <p class="mt-1 text-sm text-ink-muted">
                                    Jawapan: {{ $rsvp->attending ? $rsvp->pax.' orang hadir' : 'tidak hadir' }} · dikemas kini {{ $rsvp->updated_at->translatedFormat('j M, g:i A') }}
                                    @if ($rsvp->isSoftMatched())
                                        <span class="text-amber-700">· dipadan melalui nombor telefon</span>
                                    @endif
                                </p>
                                @if ($rsvp->isSoftMatched())
                                    <form method="POST" action="{{ route('weddings.rsvps.update', [$wedding, $rsvp]) }}" class="mt-1">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="detach" value="1">
                                        <button type="submit" class="text-xs font-medium text-ink-muted underline hover:text-brand-700">Bukan orang ini? Buang padanan</button>
                                    </form>
                                @endif
                                @if ($rsvp->message)
                                    <p class="mt-1 text-sm italic text-ink-muted">“{{ $rsvp->message }}”</p>
                                @endif
                            @elseif ($guest->shared_at)
                                <p class="mt-1 text-xs text-ink-muted">Anda kongsi pada {{ $guest->shared_at->translatedFormat('j M Y') }}. WhatsApp tidak memberitahu kami sama ada mesej sampai atau dibaca.</p>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2 self-start">
                            @if ($guest->inviteUrl())
                                <button type="button" data-copy="{{ $guest->inviteUrl() }}" class="rounded-full border border-line px-3 py-1.5 text-xs font-semibold transition hover:border-brand-400">Salin pautan</button>
                                <form method="POST" action="{{ route('weddings.guests.share', [$wedding, $guest]) }}">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">WhatsApp</button>
                                </form>
                            @endif
                            @if ($guest->shared_at)
                                <form method="POST" action="{{ route('weddings.guests.share.destroy', [$wedding, $guest]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-ink-muted hover:text-brand-700">Buang tanda hantar</button>
                                </form>
                            @endif
                            <x-confirm-action
                                :action="route('weddings.guests.destroy', [$wedding, $guest])"
                                method="DELETE"
                                tone="danger"
                                title="Padam tetamu ini?"
                                :message="$guest->name.' akan dibuang dari senarai. Jawapan RSVP mereka kekal.'"
                                confirm="Padam"
                                trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                            >Padam</x-confirm-action>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Replies we could not attach to anyone on the list --}}
    @if ($walkIns->isNotEmpty())
        <section class="mt-8">
            <h2 class="font-display text-lg font-semibold">Jawapan tanpa nama dalam senarai</h2>
            <p class="mt-1 text-sm text-ink-muted">Jawapan ini datang tanpa pautan peribadi dan kami tidak dapat memadankannya dengan sesiapa. Ia tetap dikira melainkan anda keluarkan.</p>
            <ul class="mt-3 flex flex-col gap-3">
                @foreach ($walkIns as $rsvp)
                    <li class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $rsvp->name }} @unless ($rsvp->counted)<span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">Tidak dikira</span>@endunless</p>
                            <p class="text-sm text-ink-muted">{{ $rsvp->attending ? $rsvp->pax.' orang hadir' : 'Tidak hadir' }}@if ($rsvp->phone) · {{ $rsvp->phone }}@endif</p>
                            @if ($rsvp->message)<p class="mt-1 text-sm italic text-ink-muted">“{{ $rsvp->message }}”</p>@endif
                        </div>
                        <form method="POST" action="{{ route('weddings.rsvps.update', [$wedding, $rsvp]) }}" class="shrink-0">
                            @csrf @method('PUT')
                            <input type="hidden" name="counted" value="{{ $rsvp->counted ? 0 : 1 }}">
                            <button type="submit" class="rounded-full border border-line px-4 py-1.5 text-xs font-semibold transition hover:border-brand-400">{{ $rsvp->counted ? 'Keluarkan dari kiraan' : 'Kira semula' }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.customer>
