@props(['title', 'heading' => null, 'subheading' => null])

@php
    $vendor = auth()->user()->vendor;
    $openEnquiries = $vendor?->enquiries()->where('status', \App\Enums\EnquiryStatus::Open)->count();
    $item = fn (string $label, string $icon, string $route, string $pattern, ?int $badge = null): array => [
        'label' => $label, 'icon' => $icon, 'href' => route($route), 'active' => App\Support\Locales::routeIs($pattern), 'badge' => $badge ?: null,
    ];

    // A feature this vendor has is a normal link. A Pro feature seen from
    // Basic stays in the menu, locked and leading to the Pro page, except
    // enquiries, whose own page says how many are waiting. The routes are
    // guarded as well (EnsureVendorHasFeature).
    $feature = function (App\Enums\VendorFeature $feature, string $label, ?int $badge = null) use ($vendor, $item): array {
        if ($vendor->hasFeature($feature)) {
            return $item($label, $feature->icon(), $feature->route(), $feature->routePattern(), $badge);
        }

        $href = $feature === App\Enums\VendorFeature::Enquiries ? route('vendor.enquiries.index') : route('vendor.pro.index');

        return ['label' => $label, 'icon' => $feature->icon(), 'href' => $href, 'active' => App\Support\Locales::routeIs($feature->routePattern()), 'badge' => null, 'locked' => true];
    };
    $isPro = $vendor?->isPro();

    // Waiting for approval there is nothing to navigate to: the dashboard is
    // the setup guide, and the layout drops the sidebar entirely. Otherwise
    // what every vendor has comes first, then Neekah Pro, both folding away.
    $nav = ! $vendor || $vendor->isAwaitingApproval() ? [] : [
        ['label' => null, 'items' => [
            $item(__('pages.sidebar_vendor.ringkasan'), 'chart', 'vendor.dashboard', 'vendor.dashboard'),
        ]],
        ['key' => 'vendor-basic', 'label' => __('pages.sidebar_vendor.perniagaan'), 'items' => [
            $item(__('pages.sidebar_vendor.profil'), 'store', 'vendor.profile.edit', 'vendor.profile.*'),
            $feature(App\Enums\VendorFeature::Packages, __('pages.sidebar_vendor.pakej')),
            $feature(App\Enums\VendorFeature::Portfolio, __('pages.sidebar_vendor.portfolio')),
            $feature(App\Enums\VendorFeature::Reviews, __('pages.sidebar_vendor.review')),
            $feature(App\Enums\VendorFeature::Boost, __('pages.sidebar_vendor.boost')),
        ]],
        [
            'key' => 'vendor-pro',
            'label' => __('pages.sidebar_vendor.pro'),
            'tag' => $isPro ? null : 'Pro',
            'items' => [
                $feature(App\Enums\VendorFeature::Calendar, __('pages.sidebar_vendor.kalendar_tempahan')),
                $feature(App\Enums\VendorFeature::Bookings, __('pages.sidebar_vendor.tempahan')),
                $feature(App\Enums\VendorFeature::Enquiries, __('pages.sidebar_vendor.enquiry'), $isPro ? $openEnquiries : null),
                $feature(App\Enums\VendorFeature::Points, __('pages.dash.point_ranking')),
                ...($isPro ? [$item(__('pages.sidebar_vendor.langganan_pro'), 'crown', 'vendor.pro.index', 'vendor.pro.*')] : []),
            ],
            'footer' => $isPro ? null : ['label' => __('pages.sidebar_vendor.upgrade_pro'), 'href' => route('vendor.pro.index')],
        ],
    ];
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
