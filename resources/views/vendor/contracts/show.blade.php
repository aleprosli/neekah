<x-layouts.vendor :title="__('pages.contracts.show_title', ['number' => $contract->number])" :heading="$contract->number" :subheading="$contract->client_name.($contract->event_date ? ' · '.$contract->event_date->translatedFormat('j F Y') : '')">
    <x-slot:actions>
        <x-booking-status :status="$contract->status" class="self-center" />
    </x-slot:actions>

    {{-- The contract exactly as the client reads, signs and prints it, with
         what the vendor can do beside it. --}}
    <div class="grid gap-6 break-words xl:grid-cols-[minmax(0,1fr)_300px]">
        <div class="min-w-0 overflow-x-auto rounded-2xl bg-ivory p-2 sm:p-4">
            <x-contract-sheet :contract="$contract" />
        </div>

        {{-- resources/js/components/vendor/VendorContractDetail.vue --}}
        <div class="min-w-0" data-vue="vendor-contract-detail" data-props="@vueProps($props)"></div>
    </div>
</x-layouts.vendor>
