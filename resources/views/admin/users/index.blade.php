<x-layouts.admin title="Pengguna" heading="Pengguna" subheading="Semua akaun pengantin, vendor dan admin.">
    {{-- resources/js/components/ui/DataTable.vue — the role and "Perlu diikuti"
         chips are the table's own filters, so choosing one swaps the rows
         (and the segment columns) in place rather than reloading the page. --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.users.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => 'Cari nama atau emel…',
            'emptyTitle' => 'Tiada akaun sepadan',
            'emptyMessage' => 'Cuba peranan lain, atau kosongkan carian.',
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
