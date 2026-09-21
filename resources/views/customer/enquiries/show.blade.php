<x-layouts.customer :title="__('pages.dash.enquiry')" :heading="$enquiry->vendor->name" :subheading="$enquiry->created_at->translatedFormat('j M Y, g:i A')">
    <x-slot:actions>
        <a href="{{ route('vendors.show', $enquiry->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.lihat_vendor') }}</a>
    </x-slot:actions>

    {{-- resources/js/components/customer/CustomerEnquiryDetail.vue --}}
    <div data-vue="customer-enquiry-detail" data-props="@vueProps($props)"></div>
</x-layouts.customer>
