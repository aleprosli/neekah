<x-layouts.admin title="Tempahan" heading="Tempahan" subheading="Semua transaksi yang melalui platform.">
    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.bookings.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => 'Cari rujukan, vendor atau pengantin…',
            'emptyTitle' => 'Tiada tempahan sepadan',
            'emptyMessage' => 'Cuba tapisan lain, atau kosongkan carian.',
            'initialSort' => 'event_date',
        ])"
    >
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Memuatkan senarai tempahan…</p>
    </div>

</x-layouts.admin>
