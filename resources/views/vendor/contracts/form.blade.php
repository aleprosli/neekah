<x-layouts.vendor :title="$heading" :heading="$heading" :subheading="__('pages.contracts.form_subheading')" :back="['url' => $props['cancelUrl'], 'label' => __('pages.quotations.back')]">
    {{-- resources/js/components/vendor/VendorContractForm.vue --}}
    <div data-vue="vendor-contract-form" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
