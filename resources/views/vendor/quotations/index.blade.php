<x-layouts.vendor :title="__('pages.quotations.title')" :heading="__('pages.quotations.title')" :subheading="__('pages.quotations.subheading')">
    <x-slot:actions>
        <a href="{{ route('vendor.quotations.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ {{ __('pages.quotations.new') }}</a>
    </x-slot:actions>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('vendor.quotations.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => __('pages.quotations.search'),
            'emptyTitle' => __('pages.quotations.empty_title'),
            'emptyMessage' => __('pages.quotations.empty_message'),
            'initialSort' => 'id',
        ])"
    ></div>
</x-layouts.vendor>
