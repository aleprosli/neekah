<x-auth-card title="Log masuk" subtitle="Selamat kembali. Urus tempahan dan majlis anda.">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.field label="Emel" name="email" type="email" autocomplete="email" required />
        <x-form.field label="Kata laluan" name="password" type="password" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-3 text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="accent-brand-600">
                Ingat saya
            </label>
            <a href="{{ route('password.request') }}" class="font-medium text-brand-600 underline underline-offset-4">Lupa kata laluan?</a>
        </div>

        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Log masuk</button>
    </form>

    @if (session('status'))
        <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</p>
    @endif

    <div class="mt-6"><x-google-button /></div>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Belum ada akaun? <a href="{{ route('register') }}" class="font-medium text-brand-600 underline underline-offset-4">Daftar percuma</a>
    </p>
</x-auth-card>
