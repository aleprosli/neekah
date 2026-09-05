@php use App\Enums\BookingStatus; @endphp

<x-layouts.admin title="Tempahan" heading="Tempahan" subheading="Semua transaksi yang melalui platform.">
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="mb-6 flex flex-col gap-3">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status->value }}">
        @endif
        <div class="flex flex-wrap gap-2">
            <input type="search" size="1" name="q" value="{{ request('q') }}" placeholder="Cari rujukan, vendor atau pengantin…" class="flex-1 rounded-full border border-line bg-surface px-5 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Cari</button>
        </div>
        <div class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:px-0">
            <a href="{{ route('admin.bookings.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua ({{ $counts->sum() }})</a>
            @foreach (BookingStatus::cases() as $case)
                <a href="{{ route('admin.bookings.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
            @endforeach
        </div>
    </form>

    @if ($bookings->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Tiada tempahan sepadan.</p>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Rujukan</th>
                        <th class="px-4 py-3 font-semibold">Majlis</th>
                        <th class="px-4 py-3 font-semibold">Vendor</th>
                        <th class="px-4 py-3 font-semibold">Pengantin</th>
                        <th class="px-4 py-3 text-right font-semibold">Jumlah</th>
                        <th class="px-4 py-3 text-right font-semibold">Dibayar</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($bookings as $booking)
                        <tr class="transition hover:bg-surface-muted/60">
                            <td class="px-4 py-3"><a href="{{ route('admin.bookings.show', $booking) }}" class="font-medium hover:text-brand-700">{{ $booking->reference }}</a></td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $booking->event_date->translatedFormat('j M Y') }}</td>
                            <td class="px-4 py-3">{{ $booking->vendor->name }}</td>
                            <td class="px-4 py-3">{{ $booking->user->name }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">RM{{ number_format((float) $booking->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">RM{{ number_format($booking->paidAmount(), 2) }}</td>
                            <td class="px-4 py-3"><x-booking-status :status="$booking->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $bookings->links() }}</div>
    @endif
</x-layouts.admin>
