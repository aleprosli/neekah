<x-layouts.vendor
    :title="$editing ? __('pages.dash.edit_pakej') : __('pages.dash.tambah_pakej')"
    :heading="$editing ? __('pages.dash.edit_pakej') : __('pages.dash.tambah_pakej')"
    :subheading="__('pages.dash.pakej_yang_jelas_memudahkan_pengantin')"
>
    {{-- resources/js/components/vendor/VendorPackageForm.vue --}}
    <div data-vue="vendor-package-form" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
