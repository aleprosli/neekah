<x-layouts.vendor :title="__('pages.quotations.show_title', ['number' => $quotation->number])" :heading="$quotation->number" :subheading="$quotation->client_name.($quotation->event_date ? ' · '.$quotation->event_date->translatedFormat('j F Y') : '')">
    <x-slot:actions>
        <x-quotation-status :quotation="$quotation" class="self-center" />
    </x-slot:actions>

    {{-- The sheet exactly as the client sees and prints it. What the vendor
         can do sits above it, and beside it once the screen is wide enough
         to keep the sheet at full A4 width. --}}
    <div class="grid gap-6 break-words min-[1400px]:grid-cols-[minmax(0,1fr)_300px]">
        <div class="min-w-0 rounded-2xl bg-ivory p-1 sm:p-4">
            <x-quotation-sheet :quotation="$quotation" :as-invoice="$quotation->isInvoiced()" />
        </div>

        {{-- resources/js/components/vendor/VendorQuotationDetail.vue --}}
        <div class="order-first min-w-0 min-[1400px]:order-none" data-vue="vendor-quotation-detail" data-props="@vueProps($props)"></div>
    </div>
</x-layouts.vendor>
