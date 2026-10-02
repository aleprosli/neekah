<x-layouts.vendor :title="__('pages.contracts.show_title', ['number' => $contract->number])" :heading="$contract->number" :subheading="$contract->client_name.($contract->event_date ? ' · '.$contract->event_date->translatedFormat('j F Y') : '')" :back="['url' => route('vendor.contracts.index'), 'label' => __('pages.contracts.back_to_list')]">
    <x-slot:actions>
        <x-booking-status :status="$contract->status" class="self-center" />
    </x-slot:actions>

    {{-- The sheet exactly as the client sees and prints it, at the full
         width of its column (8 of 12), with the vendor's panel beside it (4 of 12)
         from lg, as the calendar page does. Below
         that the panel comes first, so sending is the first thing on a phone. --}}
    <div class="grid gap-6 break-words lg:grid-cols-12 lg:items-start">
        <div class="min-w-0 lg:col-span-8">
            <x-contract-sheet :contract="$contract" fluid />
        </div>

        {{-- resources/js/components/vendor/VendorContractDetail.vue --}}
        <div class="order-first min-w-0 lg:sticky lg:top-24 lg:order-none lg:col-span-4" data-vue="vendor-contract-detail" data-props="@vueProps($props)"></div>
    </div>
</x-layouts.vendor>
