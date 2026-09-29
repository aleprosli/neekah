<x-layouts.vendor :title="__('pages.dash.enquiry')" :heading="__('pages.dash.enquiry_2')" :subheading="__('pages.enquiries_locked.subheading')">
    {{-- resources/js/components/vendor/VendorEnquiriesLocked.vue --}}
    <div data-vue="vendor-enquiries-locked" data-props="@vueProps($props)">
        <p class="text-sm text-ink-muted">{{ __('pages.enquiries_locked.waiting', ['count' => $props['waiting']]) }}</p>
    </div>
</x-layouts.vendor>
