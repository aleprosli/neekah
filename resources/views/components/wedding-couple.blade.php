@props(['wedding'])

@php
    $owner = $wedding->members->firstWhere('id', $wedding->user_id);
    $partner = $wedding->partner();
    $pendingInvite = $wedding->invitations->first();
    $isOwner = $wedding->isOwnedBy(auth()->user());
    $state = $partner ? 'connected' : ($pendingInvite ? 'pending' : 'alone');
@endphp

<section {{ $attributes->class([
    'overflow-hidden rounded-3xl border',
    'border-emerald-200 bg-emerald-50/40' => $state === 'connected',
    'border-gold-400/60 bg-gold-300/10' => $state === 'pending',
    'border-dashed border-line bg-surface-raised' => $state === 'alone',
]) }}>
    <div class="flex flex-col gap-6 p-6 sm:p-7 lg:flex-row lg:items-center lg:gap-8">
        {{-- Rings --}}
        <div class="flex shrink-0 items-center justify-center gap-0 lg:w-44" aria-hidden="true">
            @if ($state === 'connected')
                <span class="flex size-16 items-center justify-center rounded-full border-4 border-brand-500 bg-surface font-display text-lg font-semibold text-brand-700">{{ mb_substr($owner->name, 0, 1) }}</span>
                <span class="-ml-5 flex size-16 items-center justify-center rounded-full border-4 border-gold-500 bg-surface font-display text-lg font-semibold text-brand-900">{{ mb_substr($partner->name, 0, 1) }}</span>
            @else
                <span class="flex size-16 items-center justify-center rounded-full border-4 border-brand-500 bg-surface font-display text-lg font-semibold text-brand-700">{{ mb_substr($owner->name, 0, 1) }}</span>
                <span @class([
                    '-ml-5 flex size-16 items-center justify-center rounded-full border-4 border-dashed bg-surface',
                    'border-gold-500 text-gold-600' => $state === 'pending',
                    'border-line text-ink-muted' => $state === 'alone',
                ])>
                    @if ($state === 'pending')
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    @else
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v3M12 16v3M8 12H5M19 12h-3"/><circle cx="12" cy="12" r="2.5"/></svg>
                    @endif
                </span>
            @endif
        </div>

        {{-- Copy --}}
        <div class="min-w-0 flex-1">
            @if ($state === 'connected')
                <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-emerald-700 uppercase">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Terhubung
                </p>
                <h2 class="mt-1 font-display text-xl font-semibold">{{ $owner->name }} &amp; {{ $partner->name }}</h2>
                <p class="mt-1 text-sm text-ink-muted">Anda berdua menguruskan majlis ini bersama. Setiap tempahan, bayaran dan perubahan bajet dikongsi serta-merta.</p>
            @elseif ($state === 'pending')
                <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-gold-600 uppercase">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 2"/><circle cx="12" cy="12" r="9"/></svg>
                    Menunggu jawapan
                </p>
                <h2 class="mt-1 font-display text-xl font-semibold">Jemputan dihantar</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $pendingInvite->email }} belum menerima jemputan. Pautan ini sah sehingga {{ $pendingInvite->expires_at->translatedFormat('j F Y') }}.</p>
            @else
                <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-ink-muted uppercase">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 15 6-6M6.5 10.5 5 12a4 4 0 0 0 5.7 5.7l1.3-1.5M17.5 13.5 19 12a4 4 0 0 0-5.7-5.7L12 7.8"/></svg>
                    Belum terhubung
                </p>
                <h2 class="mt-1 font-display text-xl font-semibold">Uruskan majlis berdua</h2>
                <p class="mt-1 text-sm text-ink-muted">Jemput bakal pasangan anda supaya kedua-dua boleh menempah vendor, membayar deposit dan melihat bajet yang sama.</p>
            @endif
        </div>

        {{-- Action --}}
        <div class="shrink-0 lg:w-72">
            @if ($state === 'connected')
                <dl class="flex flex-col gap-1.5 rounded-2xl bg-surface-raised/70 p-4 text-sm">
                    <div class="flex items-center justify-between gap-3"><dt class="text-ink-muted">Pemilik</dt><dd class="truncate font-medium">{{ $owner->name }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-ink-muted">Pasangan</dt><dd class="truncate font-medium">{{ $partner->name }}</dd></div>
                </dl>
                @if ($isOwner)
                    <form method="POST" action="{{ route('weddings.members.destroy', [$wedding, $partner]) }}" class="mt-2 text-center" onsubmit="return confirm('Buang {{ $partner->name }} daripada majlis ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-medium text-ink-muted underline underline-offset-4 hover:text-brand-700">Buang pasangan</button>
                    </form>
                @endif
            @elseif ($state === 'pending')
                @if ($isOwner)
                    <x-invite-link :invitation="$pendingInvite" />
                    <form method="POST" action="{{ route('weddings.invitations.destroy', [$wedding, $pendingInvite]) }}" class="mt-2 text-center">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-medium text-ink-muted underline underline-offset-4 hover:text-brand-700">Batalkan jemputan</button>
                    </form>
                @endif
            @elseif ($isOwner)
                <form method="POST" action="{{ route('weddings.invitations.store', $wedding) }}" class="flex flex-col gap-2">
                    @csrf
                    <label class="sr-only" for="partner-email">Emel pasangan</label>
                    <input id="partner-email" type="email" name="email" value="{{ old('email') }}" placeholder="emel pasangan anda" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Jemput pasangan</button>
                </form>
            @else
                <p class="rounded-2xl bg-surface-raised/70 p-4 text-xs text-ink-muted">Hanya pemilik majlis boleh menjemput pasangan.</p>
            @endif
        </div>
    </div>
</section>
