<div class="sticky top-3 z-40 h-0 px-3 sm:px-6">
    <header class="mx-auto flex max-w-6xl items-center justify-between gap-4 rounded-full border border-line/70 bg-surface/85 py-2 pr-2 pl-4 shadow-lg shadow-black/5 backdrop-blur-md">
        <a href="{{ route('vendors.index') }}" class="flex items-center gap-2">
            <span class="flex size-8 items-center justify-center rounded-full bg-brand-600 font-display text-base font-semibold text-white">N</span>
            <span class="font-display text-lg font-semibold tracking-tight">neekah</span>
        </a>

        <nav class="hidden items-center gap-1 text-sm font-medium md:flex" aria-label="Utama">
            <a href="{{ route('vendors.index') }}" @class(['rounded-full px-4 py-2 transition hover:bg-surface-muted', 'bg-surface-muted text-brand-700' => request()->routeIs('vendors.*')])>Cari Vendor</a>
            <a href="{{ route('landing') }}#cara" class="rounded-full px-4 py-2 transition hover:bg-surface-muted">Cara Ia Berfungsi</a>
            <a href="{{ route('landing') }}#vendor" class="rounded-full px-4 py-2 transition hover:bg-surface-muted">Untuk Vendor</a>
        </nav>

        <div class="flex items-center gap-1">
            <a href="{{ route('landing') }}" class="hidden rounded-full px-4 py-2 text-sm font-medium transition hover:bg-surface-muted sm:inline">Tentang</a>
            <a href="{{ route('landing') }}#cta" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Mula percuma</a>
        </div>
    </header>
</div>
