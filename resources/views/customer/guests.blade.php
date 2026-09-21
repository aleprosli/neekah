<x-layouts.customer :title="__('pages.dash.tetamu')" :heading="__('pages.dash.senarai_tetamu')" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y')">
    {{-- resources/js/components/customer/CustomerGuestsPage.vue --}}
    <div data-vue="customer-guests-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
