<x-layouts.customer :title="__('pages.dash.playlist')" :heading="__('pages.dash.playlist_majlis')" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y')">
    {{-- resources/js/components/customer/CustomerPlaylistPage.vue --}}
    <div data-vue="customer-playlist-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
