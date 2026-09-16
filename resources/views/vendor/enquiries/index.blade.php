<x-layouts.vendor title="Enquiry" heading="Enquiry" subheading="Balas cepat untuk kekalkan response rate yang tinggi.">
    {{-- resources/js/components/vendor/VendorEnquiriesPage.vue --}}
    <div
        data-vue="vendor-enquiries-page"
        data-props="{{ json_encode([
            'enquiries' => $enquiries->items(),
            'pagination' => $enquiries->hasPages() ? (string) $enquiries->links() : '',
        ]) }}"
    ></div>
</x-layouts.vendor>
