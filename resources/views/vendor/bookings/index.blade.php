<x-layouts.vendor :title="__('pages.dash.tempahan')" :heading="__('pages.dash.tempahan_2')" :subheading="__('pages.dash.semua_booking_melalui_neekah_termasuk')">
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
            'searchPlaceholder' => __('pages.tables.cari_rujukan_pakej_atau_pelanggan'),
            'emptyTitle' => __('pages.tables.tiada_tempahan_dalam_kategori_ini'),
            'emptyMessage' => __('pages.tables.booking_yang_dibuat_pengantin_atau'),
            'initialSort' => 'event_date',
        ])"
    ></div>

</x-layouts.vendor>
