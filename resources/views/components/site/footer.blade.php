<footer class="border-t border-line pb-16 md:pb-0">
    <div class="mx-auto flex max-w-[1760px] flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-ink-muted sm:flex-row sm:px-6 lg:px-10">
        <p>© {{ now()->year }} Neekah · Semua Urusan Majlis, Satu Platform.</p>
        <div class="flex gap-6">
            <a href="{{ route('landing') }}" class="transition hover:text-ink">Tentang</a>
            <a href="{{ route('vendors.index') }}" class="transition hover:text-ink">Cari Vendor</a>
            <a href="{{ route('vendor.register') }}" class="transition hover:text-ink">Jadi Vendor</a>
        </div>
    </div>
</footer>
