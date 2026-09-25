@props(['title', 'heading' => null, 'subheading' => null])

@php
    $vendor = auth()->user()->vendor;
    $openEnquiries = $vendor?->enquiries()->where('status', \App\Enums\EnquiryStatus::Open)->count();
    $item = fn (string $label, string $icon, string $route, string $pattern, ?int $badge = null): array => [
        'label' => $label, 'icon' => $icon, 'href' => route($route), 'active' => App\Support\Locales::routeIs($pattern), 'badge' => $badge ?: null,
    ];
    // Waiting for approval there is nothing to navigate to: the dashboard is
    // the setup guide, and the layout drops the sidebar entirely.
    $nav = $vendor?->isAwaitingApproval() ? [] : [
        ['label' => null, 'items' => [
            $item(__('pages.sidebar_vendor.ringkasan'), 'chart', 'vendor.dashboard', 'vendor.dashboard'),
        ]],
        ['label' => __('pages.sidebar_vendor.perniagaan'), 'items' => [
            $item(__('pages.sidebar_vendor.profil'), 'store', 'vendor.profile.edit', 'vendor.profile.*'),
            $item(__('pages.sidebar_vendor.pakej'), 'box', 'vendor.packages.index', 'vendor.packages.*'),
            $item(__('pages.sidebar_vendor.portfolio'), 'image', 'vendor.portfolio.index', 'vendor.portfolio.*'),
            $item(__('pages.sidebar_vendor.kalendar'), 'calendar', 'vendor.availability.index', 'vendor.availability.*'),
        ]],
        ['label' => __('pages.sidebar_vendor.pelanggan'), 'items' => [
            $item(__('pages.sidebar_vendor.tempahan'), 'receipt', 'vendor.bookings.index', 'vendor.bookings.*'),
            $item(__('pages.sidebar_vendor.enquiry'), 'chat', 'vendor.enquiries.index', 'vendor.enquiries.*', $openEnquiries),
            $item(__('pages.sidebar_vendor.review'), 'star', 'vendor.reviews.index', 'vendor.reviews.*'),
        ]],
        ['label' => __('pages.sidebar_vendor.prestasi'), 'items' => [
            $item(__('pages.dash.point_ranking'), 'trophy', 'vendor.points.index', 'vendor.points.*'),
            $item(__('pages.sidebar_vendor.pro'), 'crown', 'vendor.pro.index', 'vendor.pro.*'),
        ]],
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
