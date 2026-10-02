<x-layouts.vendor :title="__('pages.quotations.show_title', ['number' => $quotation->number])" :heading="$quotation->number" :subheading="$quotation->client_name.($quotation->event_date ? ' · '.$quotation->event_date->translatedFormat('j F Y') : '')" :back="['url' => route('vendor.quotations.index'), 'label' => __('pages.quotations.back_to_list')]">
    <x-slot:actions>
        <x-quotation-status :quotation="$quotation" class="self-center" />
    </x-slot:actions>

    {{-- The sheet exactly as the client sees and prints it, at the full
         width of its column (8 of 12), with the vendor's panel beside it (4 of 12)
         from lg, as the calendar page does. Below
         that the panel comes first, so sending is the first thing on a phone. --}}
    <div class="grid gap-6 break-words lg:grid-cols-12 lg:items-start">
        <div class="min-w-0 lg:col-span-8">
            <x-quotation-sheet :quotation="$quotation" :as-invoice="$quotation->isInvoiced()" fluid />
        </div>

        {{-- resources/js/components/vendor/VendorQuotationDetail.vue --}}
        <div class="order-first min-w-0 lg:sticky lg:top-24 lg:order-none lg:col-span-4" data-vue="vendor-quotation-detail" data-props="@vueProps($props)"></div>
    </div>
</x-layouts.vendor>
