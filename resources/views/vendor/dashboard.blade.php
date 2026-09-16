<x-layouts.vendor title="Dashboard vendor" :heading="$vendor->name" :subheading="$vendor->category->name.' · '.$vendor->city.', '.$vendor->state.' · '.$vendor->tier->label().' Vendor'">
    <x-slot:actions>
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat profil awam</a>
        @endif
        <a href="{{ route('vendor.bookings.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ Rekod booking</a>
    </x-slot:actions>

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
