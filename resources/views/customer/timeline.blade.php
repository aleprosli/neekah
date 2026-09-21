<x-layouts.customer :title="__('pages.dash.timeline')" :heading="__('pages.dash.wedding_timeline')" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y')">
    {{-- resources/js/components/customer/CustomerTimelinePage.vue --}}
    <div data-vue="customer-timeline-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
