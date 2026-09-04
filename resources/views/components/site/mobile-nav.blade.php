<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 backdrop-blur md:hidden" aria-label="Navigasi mudah alih">
    <ul class="grid grid-cols-3 text-[11px] font-medium">
        <li>
            <a href="{{ route('vendors.index') }}" @class(['flex flex-col items-center gap-1 py-2', 'text-brand-600' => request()->routeIs('vendors.*'), 'text-ink-muted' => ! request()->routeIs('vendors.*')])>
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                Cari
            </a>
        </li>
        <li>
            <a href="{{ route('landing') }}" @class(['flex flex-col items-center gap-1 py-2', 'text-brand-600' => request()->routeIs('landing'), 'text-ink-muted' => ! request()->routeIs('landing')])>
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 21-7.5-7.5a5 5 0 0 1 7.5-6.6 5 5 0 0 1 7.5 6.6Z"/></svg>
                Tentang
            </a>
        </li>
        <li>
            <a href="{{ route('landing') }}#vendor" class="flex flex-col items-center gap-1 py-2 text-ink-muted">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                Vendor
            </a>
        </li>
    </ul>
</nav>
