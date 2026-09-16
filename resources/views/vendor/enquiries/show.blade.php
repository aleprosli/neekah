<x-layouts.vendor title="Enquiry" :heading="'Enquiry daripada '.$enquiry->user->name" :subheading="$enquiry->created_at->translatedFormat('j M Y, g:i A')">
    {{-- resources/js/components/vendor/VendorEnquiryDetail.vue --}}
    <div data-vue="vendor-enquiry-detail" data-props="{{ json_encode($props) }}"></div>
</x-layouts.vendor>
