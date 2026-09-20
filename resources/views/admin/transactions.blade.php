<x-layouts.admin title="Kewangan" heading="Kewangan" subheading="Nilai transaksi kasar, komisen platform dan payout vendor.">
    {{-- resources/js/components/admin/AdminStatRow.vue --}}
    <div data-vue="admin-stat-row" data-props="@vueProps(['stats' => $stats])"></div>


    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.transactions.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => 'Cari rujukan, booking atau vendor…',
            'emptyTitle' => 'Tiada transaksi',
            'emptyMessage' => 'Bayaran yang diterima akan muncul di sini.',
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.admin>
