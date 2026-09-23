<x-layouts.vendor :title="__('pages.dash.dashboard_vendor')" :heading="$vendor->name" :subheading="$vendor->category->name.' · '.$vendor->city.', '.$vendor->state.' · '.$vendor->tier->label().' Vendor'">
    <x-slot:actions>
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.lihat_profil_awam') }}</a>
        @endif
        <a href="{{ route('vendor.bookings.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ {{ __('pages.dash.rekod_booking') }}</a>
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

    {{-- Last week's reach, for every vendor; the trend is on the Pro page. --}}
    <a href="{{ route('vendor.pro.index') }}" class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-line bg-surface-raised p-5 transition hover:border-brand-300">
        <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm">
            <span><span class="font-display text-xl font-semibold">{{ number_format($reach['profile_views']) }}</span> {{ __('pages.pro.stats.profile_views_short') }}</span>
            <span><span class="font-display text-xl font-semibold">{{ number_format($reach['whatsapp_clicks']) }}</span> {{ __('pages.pro.stats.whatsapp_clicks_short') }}</span>
            <span><span class="font-display text-xl font-semibold">{{ number_format($reach['phone_clicks']) }}</span> {{ __('pages.pro.stats.phone_clicks_short') }}</span>
        </div>
        <span class="text-sm font-medium text-brand-700">{{ $vendor->isPro() ? __('pages.pro.see_trend') : __('pages.pro.see_trend_upgrade') }} →</span>
    </a>

    {{-- resources/js/components/vendor/VendorDashboardPage.vue --}}
    <div data-vue="vendor-dashboard-page" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
