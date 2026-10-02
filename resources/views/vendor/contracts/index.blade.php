<x-layouts.vendor :title="__('pages.contracts.title')" :heading="__('pages.contracts.title')" :subheading="__('pages.contracts.subheading')">
    <x-slot:actions>
        <a href="{{ route('vendor.contracts.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ {{ __('pages.contracts.new') }}</a>
    </x-slot:actions>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('vendor.contracts.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => __('pages.contracts.search'),
            'emptyTitle' => __('pages.contracts.empty_title'),
            'emptyMessage' => __('pages.contracts.empty_message'),
            'initialSort' => 'id',
        ])"
    ></div>
</x-layouts.vendor>
