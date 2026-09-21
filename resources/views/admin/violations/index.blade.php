<x-layouts.admin :title="__('pages.dash.laporan_vendor')" :heading="__('pages.dash.laporan_vendor_2')" :subheading="__('pages.dash.setiap_laporan_disemak_sebelum_tindakan')">
    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.violations.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => __('pages.tables.cari_vendor_atau_isi_aduan'),
            'emptyTitle' => __('pages.tables.tiada_laporan'),
            'emptyMessage' => __('pages.tables.laporan_daripada_pengantin_akan_muncul'),
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.admin>
