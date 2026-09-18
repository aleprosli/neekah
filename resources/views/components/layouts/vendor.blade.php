@props(['title', 'heading' => null, 'subheading' => null])

@php
    $vendor = auth()->user()->vendor;
    $openEnquiries = $vendor?->enquiries()->where('status', \App\Enums\EnquiryStatus::Open)->count();
    $item = fn (string $label, string $icon, string $route, string $pattern, ?int $badge = null): array => [
        'label' => $label, 'icon' => $icon, 'href' => route($route), 'active' => request()->routeIs($pattern), 'badge' => $badge ?: null,
    ];
    $nav = [
        ['label' => null, 'items' => [
            $item('Ringkasan', 'chart', 'vendor.dashboard', 'vendor.dashboard'),
        ]],
        ['label' => 'Perniagaan', 'items' => [
            $item('Profil', 'store', 'vendor.profile.edit', 'vendor.profile.*'),
            $item('Pakej', 'box', 'vendor.packages.index', 'vendor.packages.*'),
            $item('Portfolio', 'image', 'vendor.portfolio.index', 'vendor.portfolio.*'),
            $item('Kalendar', 'calendar', 'vendor.availability.index', 'vendor.availability.*'),
        ]],
        ['label' => 'Pelanggan', 'items' => [
            $item('Tempahan', 'receipt', 'vendor.bookings.index', 'vendor.bookings.*'),
            $item('Enquiry', 'chat', 'vendor.enquiries.index', 'vendor.enquiries.*', $openEnquiries),
        ]],
        ['label' => 'Prestasi', 'items' => [
            $item('Point & Ranking', 'trophy', 'vendor.points.index', 'vendor.points.*'),
        ]],
    ];
    $context = $vendor
        ? ['title' => $vendor->name, 'detail' => $vendor->status->label().' · '.$vendor->tier->label().' Vendor']
        : ['title' => 'Dashboard vendor', 'detail' => null];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :context="$context" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    @if ($vendor && ! $vendor->isApproved())
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-semibold">Status: {{ $vendor->status->label() }}</p>
            <p class="mt-1">Profil anda belum dipaparkan di marketplace. Lengkapkan profil, pakej dan portfolio sementara menunggu semakan admin.</p>
        </div>
    @endif

    {{ $slot }}
</x-layouts.dashboard>
