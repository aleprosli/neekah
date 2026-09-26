<x-layouts.admin :title="__('pages.payments.title')" :heading="__('pages.payments.title')" :subheading="__('pages.payments.subheading')">
    {{-- resources/js/components/admin/AdminStatRow.vue --}}
    <div data-vue="admin-stat-row" data-props="@vueProps(['stats' => $props['stats']])" class="mb-8"></div>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            ...$props['table'],
            'searchPlaceholder' => __('pages.payments.search'),
            'emptyTitle' => __('pages.payments.empty_title'),
            'emptyMessage' => __('pages.payments.empty_message'),
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.admin>
