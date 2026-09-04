<x-auth-card title="Daftar akaun" subtitle="Untuk pengantin. Vendor boleh mendaftar melalui halaman Jadi Vendor.">
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.field label="Nama" name="name" autocomplete="name" placeholder="Aina & Hakim" required />
        <x-form.field label="Emel" name="email" type="email" autocomplete="email" required />
        <x-form.field label="Nombor telefon" name="phone" type="tel" autocomplete="tel" placeholder="012-345 6789" />
        <x-form.field label="Kata laluan" name="password" type="password" autocomplete="new-password" help="Sekurang-kurangnya 8 aksara." required />
        <x-form.field label="Sahkan kata laluan" name="password_confirmation" type="password" autocomplete="new-password" required />

        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Sudah ada akaun? <a href="{{ route('login') }}" class="font-medium text-brand-600 underline underline-offset-4">Log masuk</a>
    </p>
</x-auth-card>
