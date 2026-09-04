<x-auth-card title="Lupa kata laluan" subtitle="Masukkan emel anda dan kami akan hantar pautan untuk set semula kata laluan.">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.field label="Emel" name="email" type="email" autocomplete="email" required />
        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Hantar pautan</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Ingat kata laluan anda? <a href="{{ route('login') }}" class="font-medium text-brand-600 underline underline-offset-4">Log masuk</a>
    </p>
</x-auth-card>
