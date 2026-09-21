<x-layouts.vendor
    :title="$editing ? 'Edit pakej' : 'Tambah pakej'"
    :heading="$editing ? 'Edit pakej' : 'Tambah pakej'"
    :subheading="__('pages.dash.pakej_yang_jelas_memudahkan_pengantin')"
>
    {{-- resources/js/components/vendor/VendorPackageForm.vue --}}
    <div data-vue="vendor-package-form" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
