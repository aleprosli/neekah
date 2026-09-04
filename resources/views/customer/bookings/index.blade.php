<x-layouts.app title="Tempahan saya">
    <x-site.header />

    <main class="mx-auto max-w-5xl px-4 pt-24 pb-24 sm:px-6 lg:px-10 lg:pt-28">
        <div class="flex flex-col gap-1">
            <h1 class="font-display text-3xl font-semibold tracking-tight">Tempahan saya</h1>
            <p class="text-sm text-ink-muted">Semua booking dan bayaran anda direkod di sini.</p>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-100">{{ session('status') }}</div>
        @endif

        @if ($bookings->isEmpty())
            <div class="mt-10 flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
                <span class="text-4xl">🗓️</span>
                <h2 class="text-lg font-semibold">Belum ada tempahan</h2>
                <p class="max-w-sm text-sm text-ink-muted">Cari vendor, pilih pakej dan tempah terus dalam Neekah.</p>
                <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
            </div>
        @else
            <ul class="mt-8 flex flex-col gap-4">
                @foreach ($bookings as $booking)
                    <li>
                        <a href="{{ route('bookings.show', $booking) }}" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5 sm:flex-row sm:items-center">
                            <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br text-2xl {{ $booking->vendor->cover_tone }}">{{ $booking->vendor->category->icon }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-semibold">{{ $booking->vendor->name }}</h2>
                                    <x-booking-status :status="$booking->status" />
                                </div>
                                <p class="mt-0.5 text-sm text-ink-muted">{{ $booking->package_name }} · {{ $booking->event_date->translatedFormat('j M Y') }} · {{ $booking->reference }}</p>
                            </div>
                            <div class="text-sm sm:text-right">
                                <p class="font-semibold">RM{{ number_format((float) $booking->total_amount, 2) }}</p>
                                <p class="text-xs text-ink-muted">Dibayar RM{{ number_format($booking->paidAmount(), 2) }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $bookings->links() }}</div>
        @endif
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
