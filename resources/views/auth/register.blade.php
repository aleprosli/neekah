@php $invitation = $invitation ?? null; @endphp

<x-auth-card :title="$invitation ? 'Terima jemputan' : 'Daftar akaun'" :subtitle="$invitation ? 'Daftar akaun untuk menyertai majlis ini. Anda akan terus dihubungkan selepas mendaftar.' : 'Untuk pengantin. Vendor boleh mendaftar melalui halaman Jadi Vendor.'">
    @if ($invitation)
        <div class="mb-6 flex items-center gap-4 rounded-2xl border border-brand-200 bg-brand-50/60 p-4">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full border-2 border-brand-500 bg-surface font-display font-semibold text-brand-700">{{ mb_substr($invitation->inviter->name, 0, 1) }}</span>
            <div class="min-w-0 text-sm">
                <p class="font-semibold">{{ $invitation->inviter->name }} menjemput anda</p>
                <p class="truncate text-ink-muted">{{ $invitation->wedding->title }} · {{ $invitation->wedding->event_date->translatedFormat('j F Y') }}</p>
            </div>
        </div>
    @endif
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.field label="Nama" name="name" autocomplete="name" placeholder="Aina & Hakim" required />
        <x-form.field label="Emel" name="email" type="email" :value="$invitation?->email" autocomplete="email" required />
        <x-form.field label="Nombor telefon" name="phone" type="tel" autocomplete="tel" placeholder="012-345 6789" />
        <x-form.field label="Kata laluan" name="password" type="password" autocomplete="new-password" help="Sekurang-kurangnya 8 aksara." required />
        <x-form.field label="Sahkan kata laluan" name="password_confirmation" type="password" autocomplete="new-password" required />

        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Daftar</button>
    </form>

    <div class="mt-6"><x-google-button /></div>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Sudah ada akaun? <a href="{{ route('login') }}" class="font-medium text-brand-600 underline underline-offset-4">Log masuk</a>
    </p>
</x-auth-card>
