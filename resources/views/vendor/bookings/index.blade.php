@php use App\Enums\BookingStatus; @endphp

<x-layouts.vendor title="Tempahan" heading="Tempahan" subheading="Semua booking melalui Neekah, termasuk yang anda rekod sendiri.">
    <x-slot:actions>
        <a href="{{ route('vendor.bookings.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ Rekod booking</a>
    </x-slot:actions>

    <div class="no-scrollbar -mx-4 mb-6 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:px-0">
        <a href="{{ route('vendor.bookings.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua ({{ $counts->sum() }})</a>
        @foreach (BookingStatus::cases() as $case)
            <a href="{{ route('vendor.bookings.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </div>

    @if ($bookings->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Tiada tempahan dalam kategori ini.</p>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Tarikh</th>
                        <th class="px-4 py-3 font-semibold">Pelanggan</th>
                        <th class="px-4 py-3 font-semibold">Pakej</th>
                        <th class="px-4 py-3 text-right font-semibold">Jumlah</th>
                        <th class="px-4 py-3 text-right font-semibold">Dibayar</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($bookings as $booking)
                        <tr class="transition hover:bg-surface-muted/60">
                            <td class="px-4 py-3 whitespace-nowrap"><a href="{{ route('vendor.bookings.show', $booking) }}" class="font-medium hover:text-brand-700">{{ $booking->event_date->translatedFormat('j M Y') }}</a><p class="text-xs text-ink-muted">{{ $booking->reference }}</p></td>
                            <td class="px-4 py-3">{{ $booking->user->name }}</td>
                            <td class="px-4 py-3">{{ $booking->package_name }}</td>
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
</x-layouts.vendor>
