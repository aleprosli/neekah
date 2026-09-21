<x-layouts.admin :title="__('pages.dash.pengguna')" :heading="__('pages.dash.pengguna_2')" :subheading="__('pages.dash.semua_akaun_pengantin_vendor_dan')">
    {{-- resources/js/components/ui/DataTable.vue — the role and "Perlu diikuti"
         chips are the table's own filters, so choosing one swaps the rows
         (and the segment columns) in place rather than reloading the page. --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.users.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => __('pages.tables.cari_nama_atau_emel'),
            'emptyTitle' => __('pages.tables.tiada_akaun_sepadan'),
            'emptyMessage' => __('pages.tables.cuba_peranan_lain_atau_kosongkan'),
            'csrf' => csrf_token(),
            'rowAction' => [
                'urlKey' => 'impersonate_url',
                'labelKey' => 'name',
                'label' => 'Impersonate',
                'title' => 'Log masuk sebagai __ROW__?',
                'message' => 'Anda akan melihat Neekah persis seperti pengguna ini. Pembayaran dimatikan, dan tindakan ini direkod dalam log sistem.',
                'confirmLabel' => 'Ya, impersonate',
            ],
        ])"
    ></div>
</x-layouts.admin>
