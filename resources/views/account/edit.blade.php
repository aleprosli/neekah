@php
    $layout = 'layouts.'.match (true) {
        $user->isAdmin() => 'admin',
        $user->isVendor() => 'vendor',
        default => 'customer',
    };
    $card = 'flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6';
    $button = 'self-start rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700';
@endphp

<x-dynamic-component :component="$layout" title="Akaun saya" heading="Akaun saya" subheading="Maklumat peribadi dan kata laluan anda.">
    <div class="grid gap-6 break-words lg:grid-cols-2">
        <form method="POST" action="{{ route('account.update') }}" class="{{ $card }}">
            @csrf
            @method('PUT')

            <h2 class="font-semibold">Maklumat peribadi</h2>

            @if ($user->isVendor())
                <p class="text-sm text-ink-muted">Ini maklumat anda sebagai pemilik. Nama, logo dan maklumat perniagaan diuruskan di <a href="{{ route('vendor.profile.edit') }}" class="font-medium text-brand-600 underline underline-offset-4">Profil</a>.</p>
            @endif

            <x-form.field label="Nama penuh" name="name" :value="$user->name" autocomplete="name" required />
            <x-form.field label="Nombor telefon" name="phone" type="tel" :value="$user->phone" autocomplete="tel" placeholder="012-345 6789" />
            <x-form.field label="Emel" name="email" type="email" :value="$user->email" autocomplete="email" required
                :help="$user->hasPassword() ? 'Menukar emel memerlukan kata laluan semasa anda.' : null" />

            @if ($user->hasPassword())
                <x-form.field label="Kata laluan semasa" name="current_password" type="password" autocomplete="current-password"
                    help="Isi hanya jika anda menukar emel." />
            @endif

            <button type="submit" class="{{ $button }}">Simpan maklumat</button>
        </form>

        <form method="POST" action="{{ route('account.password') }}" class="{{ $card }}">
            @csrf
            @method('PUT')

            <h2 class="font-semibold">{{ $user->hasPassword() ? 'Tukar kata laluan' : 'Tetapkan kata laluan' }}</h2>

            @if ($user->hasPassword())
                <p class="text-sm text-ink-muted">Selepas ditukar, anda akan dilog keluar daripada peranti lain.</p>
                <x-form.field label="Kata laluan semasa" name="current_password" type="password" autocomplete="current-password" required />
            @else
                <p class="text-sm text-ink-muted">Anda log masuk dengan Google, jadi akaun ini belum ada kata laluan. Tetapkan satu supaya anda boleh log masuk tanpa Google.</p>
            @endif

            <x-form.field label="Kata laluan baru" name="password" type="password" autocomplete="new-password" required />
            <x-form.field label="Sahkan kata laluan baru" name="password_confirmation" type="password" autocomplete="new-password" required />

            <button type="submit" class="{{ $button }}">{{ $user->hasPassword() ? 'Tukar kata laluan' : 'Tetapkan kata laluan' }}</button>
        </form>
    </div>
</x-dynamic-component>
