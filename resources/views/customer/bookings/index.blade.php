<x-layouts.customer title="Tempahan saya" heading="Tempahan saya" subheading="Semua booking dan bayaran anda direkod di sini.">
    <x-slot:actions>
        <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
    </x-slot:actions>

    @if ($bookings->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
            <span class="text-4xl">🗓️</span>
            <h2 class="text-lg font-semibold">Belum ada tempahan</h2>
            <p class="max-w-sm text-sm text-ink-muted">Cari vendor, pilih pakej dan tempah terus dalam Neekah.</p>
            <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
        </div>
    @else
        <ul class="flex flex-col gap-4">
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
</x-layouts.customer>
