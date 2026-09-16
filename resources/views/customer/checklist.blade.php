<x-layouts.customer title="Checklist" heading="Checklist majlis" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('j F Y').' · '.$wedding->event_date->diffForHumans()">
    {{-- resources/js/components/customer/CustomerChecklistPage.vue --}}
    <div data-vue="customer-checklist-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
