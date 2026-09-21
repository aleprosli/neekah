<x-layouts.vendor :title="__('pages.dash.enquiry')" :heading="'Enquiry daripada '.$enquiry->user->name" :subheading="$enquiry->created_at->translatedFormat('j M Y, g:i A')">
    {{-- resources/js/components/vendor/VendorEnquiryDetail.vue --}}
    <div data-vue="vendor-enquiry-detail" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
