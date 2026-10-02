<x-layouts.vendor :title="__('pages.quotations.show_title', ['number' => $quotation->number])" :heading="$quotation->number" :subheading="$quotation->client_name.($quotation->event_date ? ' · '.$quotation->event_date->translatedFormat('j F Y') : '')">
    <x-slot:actions>
        <x-quotation-status :quotation="$quotation" class="self-center" />
    </x-slot:actions>

    {{-- resources/js/components/vendor/VendorQuotationDetail.vue --}}
    <div data-vue="vendor-quotation-detail" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
