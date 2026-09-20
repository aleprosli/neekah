<x-layouts.admin title="Vendor" heading="Vendor" subheading="Luluskan, gantung dan pantau semua vendor platform.">
    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.vendors.data'),
            'columns' => $columns,
            'filters' => $filters,
            'bulkActions' => $bulkActions,
            'exportUrl' => route('admin.vendors.export'),
            'searchPlaceholder' => 'Cari nama vendor atau bandar…',
            'emptyTitle' => 'Tiada vendor sepadan',
            'emptyMessage' => 'Cuba status lain, atau kosongkan carian.',
            'csrf' => csrf_token(),
            'rowAction' => ['inline' => true],
        ])"
    ></div>
</x-layouts.admin>
