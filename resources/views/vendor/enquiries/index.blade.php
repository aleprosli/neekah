<x-layouts.vendor :title="__('pages.dash.enquiry')" :heading="__('pages.dash.enquiry_2')" :subheading="__('pages.dash.balas_cepat_untuk_kekalkan_response')">
    {{-- resources/js/components/vendor/VendorEnquiriesPage.vue --}}
    <div
        data-vue="vendor-enquiries-page"
        data-props="@vueProps([
            'enquiries' => $enquiries->items(),
            'pagination' => $enquiries->hasPages() ? (string) $enquiries->onEachSide(1)->links() : '',
        ])"
    ></div>
</x-layouts.vendor>
