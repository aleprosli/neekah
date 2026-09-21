<x-layouts.admin :title="__('pages.dash.kewangan')" :heading="__('pages.dash.kewangan_2')" :subheading="__('pages.dash.nilai_transaksi_kasar_komisen_platform')">
    {{-- resources/js/components/admin/AdminStatRow.vue --}}
    <div data-vue="admin-stat-row" data-props="@vueProps(['stats' => $stats])"></div>


    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.transactions.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => __('pages.tables.cari_rujukan_booking_atau_vendor'),
            'emptyTitle' => __('pages.tables.tiada_transaksi'),
            'emptyMessage' => __('pages.tables.bayaran_yang_diterima_akan_muncul'),
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.admin>
