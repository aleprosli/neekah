<x-layouts.customer :title="$album->displayTitle().' · '.__('pages.camera.title')" :heading="__('pages.camera.title')" :subheading="__('pages.camera.album_subheading')">
    {{-- resources/js/components/customer/CustomerCameraAlbum.vue --}}
    <div data-vue="customer-camera-album" data-props="@vueProps($props)">
        <p class="text-sm text-ink-muted">{{ __('pages.camera.album_subheading') }}</p>
    </div>
</x-layouts.customer>
