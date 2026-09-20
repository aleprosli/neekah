<x-layouts.admin title="Laporan vendor" heading="Laporan vendor" subheading="Setiap laporan disemak sebelum tindakan dikenakan.">
    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.violations.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => 'Cari vendor atau isi aduan…',
            'emptyTitle' => 'Tiada laporan',
            'emptyMessage' => 'Laporan daripada pengantin akan muncul di sini.',
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.admin>
