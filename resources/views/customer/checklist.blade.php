<x-layouts.customer :title="__('pages.dash.checklist')" :heading="__('pages.dash.checklist_majlis')" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('j F Y').' · '.$wedding->event_date->diffForHumans()">
    {{-- resources/js/components/customer/CustomerChecklistPage.vue --}}
    <div data-vue="customer-checklist-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
