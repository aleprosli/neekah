<x-layouts.vendor
    :title="$editing ? 'Edit pakej' : 'Tambah pakej'"
    :heading="$editing ? 'Edit pakej' : 'Tambah pakej'"
    subheading="Pakej yang jelas memudahkan pengantin membandingkan dan terus menempah."
>
    {{-- resources/js/components/vendor/VendorPackageForm.vue --}}
    <div data-vue="vendor-package-form" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
