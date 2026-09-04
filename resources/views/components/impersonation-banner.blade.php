@if (auth()->check() && auth()->user()->isImpersonated())
    <div class="sticky top-0 z-50 bg-amber-400 text-amber-950">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-2 text-sm sm:flex-row sm:px-6 lg:px-10">
            <p class="flex items-center gap-2 text-center sm:text-left">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                <span>Mod impersonate. Anda melihat Neekah sebagai <strong>{{ auth()->user()->name }}</strong>. Pembayaran dimatikan.</span>
            </p>
            <form method="POST" action="{{ route('impersonate.stop') }}" class="shrink-0">
                @csrf
                <button type="submit" class="rounded-full bg-amber-950 px-4 py-1.5 text-xs font-semibold text-amber-50 transition hover:bg-amber-900">Kembali sebagai admin</button>
            </form>
        </div>
    </div>
@endif
