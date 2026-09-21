<x-layouts.admin :title="__('pages.dash.tempahan')" :heading="__('pages.dash.tempahan_2')" :subheading="__('pages.dash.semua_transaksi_yang_melalui_platform')">
    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.bookings.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => __('pages.tables.cari_rujukan_vendor_atau_pengantin'),
            'emptyTitle' => __('pages.tables.tiada_tempahan_sepadan'),
            'emptyMessage' => __('pages.tables.cuba_tapisan_lain_atau_kosongkan'),
            'initialSort' => 'event_date',
        ])"
    >
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">{{ __('pages.dash.memuatkan_senarai_tempahan') }}</p>
    </div>

</x-layouts.admin>
