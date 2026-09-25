@props(['title', 'heading' => null, 'subheading' => null])

@php
    $vendor = auth()->user()->vendor;
    $openEnquiries = $vendor?->enquiries()->where('status', \App\Enums\EnquiryStatus::Open)->count();
    $item = fn (string $label, string $icon, string $route, string $pattern, ?int $badge = null): array => [
        'label' => $label, 'icon' => $icon, 'href' => route($route), 'active' => App\Support\Locales::routeIs($pattern), 'badge' => $badge ?: null,
    ];

    // A feature this vendor has is a normal link. One that Pro would open is
    // still shown, marked Pro and pointing at the upgrade; anything else closed
    // is left out. The routes are guarded as well (EnsureVendorHasFeature).
    $feature = function (App\Enums\VendorFeature $feature, string $label, ?int $badge = null) use ($vendor, $item): ?array {
        if ($vendor->hasFeature($feature)) {
            return $item($label, $feature->icon(), $feature->route(), $feature->routePattern(), $badge);
        }

        return $vendor->unlocksWithPro($feature)
            ? ['label' => $label, 'icon' => $feature->icon(), 'href' => route('vendor.pro.index'), 'active' => false, 'badge' => 'Pro']
            : null;
    };
    $group = fn (?string $label, array $items): ?array => ($items = array_values(array_filter($items))) === [] ? null : ['label' => $label, 'items' => $items];

    // Waiting for approval there is nothing to navigate to: the dashboard is
    // the setup guide, and the layout drops the sidebar entirely.
    $nav = ! $vendor || $vendor->isAwaitingApproval() ? [] : array_values(array_filter([
        $group(null, [
            $item(__('pages.sidebar_vendor.ringkasan'), 'chart', 'vendor.dashboard', 'vendor.dashboard'),
        ]),
        $group(__('pages.sidebar_vendor.perniagaan'), [
            $item(__('pages.sidebar_vendor.profil'), 'store', 'vendor.profile.edit', 'vendor.profile.*'),
            $feature(App\Enums\VendorFeature::Packages, __('pages.sidebar_vendor.pakej')),
            $feature(App\Enums\VendorFeature::Portfolio, __('pages.sidebar_vendor.portfolio')),
            $feature(App\Enums\VendorFeature::Calendar, __('pages.sidebar_vendor.kalendar')),
        ]),
        $group(__('pages.sidebar_vendor.pelanggan'), [
            $feature(App\Enums\VendorFeature::Bookings, __('pages.sidebar_vendor.tempahan')),
            $feature(App\Enums\VendorFeature::Enquiries, __('pages.sidebar_vendor.enquiry'), $openEnquiries),
            $feature(App\Enums\VendorFeature::Reviews, __('pages.sidebar_vendor.review')),
            $feature(App\Enums\VendorFeature::OnlineBooking, __('pages.sidebar_vendor.tempahan_online')),
        ]),
        $group(__('pages.sidebar_vendor.prestasi'), [
            $feature(App\Enums\VendorFeature::Points, __('pages.dash.point_ranking')),
            $feature(App\Enums\VendorFeature::Boost, __('pages.sidebar_vendor.boost')),
            $item(__('pages.sidebar_vendor.pro'), 'crown', 'vendor.pro.index', 'vendor.pro.*'),
        ]),
    ]));
    $context = $vendor
        ? ['title' => $vendor->name, 'detail' => $vendor->status->label().' · '.$vendor->tier->label().' Vendor']
        : ['title' => __('pages.sidebar_vendor.dashboard_vendor'), 'detail' => null];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :context="$context" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    @if ($vendor && ! $vendor->isApproved() && ! $vendor->isAwaitingApproval())
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-semibold">Status: {{ $vendor->status->label() }}</p>
            <p class="mt-1">{{ __('props.vendor_onboarding.belum_dipaparkan') }}</p>
        </div>
    @endif

    {{ $slot }}
</x-layouts.dashboard>
