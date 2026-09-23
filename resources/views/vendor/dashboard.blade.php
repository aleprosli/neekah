<x-layouts.vendor :title="__('pages.dash.dashboard_vendor')" :heading="$vendor->name" :subheading="$vendor->category->name.' · '.$vendor->city.', '.$vendor->state.' · '.$vendor->tier->label().' Vendor'">
    <x-slot:actions>
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.lihat_profil_awam') }}</a>
        @endif
        <a href="{{ route('vendor.bookings.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ {{ __('pages.dash.rekod_booking') }}</a>
    </x-slot:actions>

    <x-vendor-ranking :vendor="$vendor" />

    {{-- resources/js/components/vendor/VendorOnboarding.vue --}}
    <div
        class="mb-8"
        data-vue="vendor-onboarding"
        data-props="@vueProps([
            'steps' => $onboarding,
            'publicUrl' => $vendor->isApproved() ? route('vendors.show', $vendor) : null,
        ])"
    ></div>

    {{-- resources/js/components/vendor/VendorDashboardPage.vue --}}
    <div data-vue="vendor-dashboard-page" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
