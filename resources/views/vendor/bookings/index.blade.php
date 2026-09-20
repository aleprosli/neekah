<x-layouts.vendor title="Tempahan" heading="Tempahan" subheading="Semua booking melalui Neekah, termasuk yang anda rekod sendiri.">
    <x-slot:actions>
        <a href="{{ route('vendor.bookings.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ Rekod booking</a>
    </x-slot:actions>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('vendor.bookings.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => 'Cari rujukan, pakej atau pelanggan…',
            'emptyTitle' => 'Tiada tempahan dalam kategori ini',
            'emptyMessage' => 'Booking yang dibuat pengantin atau yang anda rekod akan muncul di sini.',
            'initialSort' => 'event_date',
        ])"
    ></div>

</x-layouts.vendor>
