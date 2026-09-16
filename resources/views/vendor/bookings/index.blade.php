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

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="{{ json_encode([
            'dataUrl' => route('vendor.bookings.data', ['status' => $status?->value]),
            'columns' => $columns,
            'searchPlaceholder' => 'Cari rujukan, pakej atau pelanggan…',
            'emptyTitle' => 'Tiada tempahan dalam kategori ini',
            'emptyMessage' => 'Booking yang dibuat pengantin atau yang anda rekod akan muncul di sini.',
            'initialSort' => 'event_date',
        ]) }}"
    ></div>

</x-layouts.vendor>
