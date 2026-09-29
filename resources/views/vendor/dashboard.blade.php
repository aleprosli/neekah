<x-layouts.vendor :title="__('pages.dash.dashboard_vendor')">
    {{-- resources/js/components/vendor/VendorDashboardPage.vue --}}
    <div data-vue="vendor-dashboard-page" data-props="@vueProps($props)">
        <h1 class="font-display text-2xl font-semibold">{{ $vendor->name }}</h1>
    </div>
</x-layouts.vendor>
