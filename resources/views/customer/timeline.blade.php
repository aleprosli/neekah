<x-layouts.customer title="Timeline" heading="Wedding timeline" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y')">
    {{-- resources/js/components/customer/CustomerTimelinePage.vue --}}
    <div data-vue="customer-timeline-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
