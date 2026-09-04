<x-auth-card title="Log masuk" subtitle="Selamat kembali. Urus tempahan dan majlis anda.">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.field label="Emel" name="email" type="email" autocomplete="email" required />
        <x-form.field label="Kata laluan" name="password" type="password" autocomplete="current-password" required />

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="accent-brand-600">
            Ingat saya
        </label>

        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Log masuk</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Belum ada akaun? <a href="{{ route('register') }}" class="font-medium text-brand-600 underline underline-offset-4">Daftar percuma</a>
    </p>
</x-auth-card>
