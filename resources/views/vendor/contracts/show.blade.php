<x-layouts.vendor :title="__('pages.contracts.show_title', ['number' => $contract->number])" :heading="$contract->number" :subheading="$contract->client_name.($contract->event_date ? ' · '.$contract->event_date->translatedFormat('j F Y') : '')">
    <x-slot:actions>
        <x-booking-status :status="$contract->status" class="self-center" />
    </x-slot:actions>

    {{-- The contract exactly as the client reads, signs and prints it. What
         the vendor can do sits above it, and beside it once the screen is
         wide enough to keep the sheet at full A4 width. --}}
    <div class="grid gap-6 break-words min-[1400px]:grid-cols-[minmax(0,1fr)_300px]">
        <div class="min-w-0 rounded-2xl bg-ivory p-1 sm:p-4">
            <x-contract-sheet :contract="$contract" />
        </div>

        {{-- resources/js/components/vendor/VendorContractDetail.vue --}}
        <div class="order-first min-w-0 min-[1400px]:order-none" data-vue="vendor-contract-detail" data-props="@vueProps($props)"></div>
    </div>
</x-layouts.vendor>
