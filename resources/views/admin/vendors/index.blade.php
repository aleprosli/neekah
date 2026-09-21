<x-layouts.admin :title="__('pages.dash.vendor')" :heading="__('pages.dash.vendor_2')" :subheading="__('pages.dash.luluskan_gantung_dan_pantau_semua')">
    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.vendors.data'),
            'columns' => $columns,
            'filters' => $filters,
            'bulkActions' => $bulkActions,
            'exportUrl' => route('admin.vendors.export'),
            'searchPlaceholder' => __('pages.tables.cari_nama_vendor_atau_bandar'),
            'emptyTitle' => __('pages.tables.tiada_vendor_sepadan'),
            'emptyMessage' => __('pages.tables.cuba_status_lain_atau_kosongkan'),
            'csrf' => csrf_token(),
            'rowAction' => ['inline' => true],
        ])"
    ></div>
</x-layouts.admin>
