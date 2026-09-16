@php use App\Enums\BookingStatus; @endphp

<x-layouts.admin title="Tempahan" heading="Tempahan" subheading="Semua transaksi yang melalui platform.">
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="mb-6 flex flex-col gap-3">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status->value }}">
        @endif
        <div class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:px-0">
            <a href="{{ route('admin.bookings.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua ({{ $counts->sum() }})</a>
            @foreach (BookingStatus::cases() as $case)
                <a href="{{ route('admin.bookings.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
            @endforeach
        </div>
    </form>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="{{ json_encode([
            'dataUrl' => route('admin.bookings.data', ['status' => $status?->value]),
            'columns' => $columns,
            'searchPlaceholder' => 'Cari rujukan, vendor atau pengantin…',
            'emptyTitle' => 'Tiada tempahan sepadan',
            'emptyMessage' => 'Cuba tapisan lain, atau kosongkan carian.',
            'initialSort' => 'event_date',
        ]) }}"
    >
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Memuatkan senarai tempahan…</p>
    </div>

</x-layouts.admin>
