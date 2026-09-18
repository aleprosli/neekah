@props(['title', 'heading' => null, 'subheading' => null])

@php
    $vendor = auth()->user()->vendor;
    $openEnquiries = $vendor?->enquiries()->where('status', \App\Enums\EnquiryStatus::Open)->count();
    $nav = [
        ['label' => 'Ringkasan', 'icon' => 'chart', 'href' => route('vendor.dashboard'), 'active' => request()->routeIs('vendor.dashboard')],
        ['label' => 'Profil', 'icon' => 'store', 'href' => route('vendor.profile.edit'), 'active' => request()->routeIs('vendor.profile.*')],
        ['label' => 'Pakej', 'icon' => 'box', 'href' => route('vendor.packages.index'), 'active' => request()->routeIs('vendor.packages.*')],
        ['label' => 'Portfolio', 'icon' => 'image', 'href' => route('vendor.portfolio.index'), 'active' => request()->routeIs('vendor.portfolio.*')],
        ['label' => 'Kalendar', 'icon' => 'calendar', 'href' => route('vendor.availability.index'), 'active' => request()->routeIs('vendor.availability.*')],
        ['label' => 'Tempahan', 'icon' => 'receipt', 'href' => route('vendor.bookings.index'), 'active' => request()->routeIs('vendor.bookings.*')],
        ['label' => 'Enquiry', 'icon' => 'chat', 'href' => route('vendor.enquiries.index'), 'active' => request()->routeIs('vendor.enquiries.*'), 'badge' => $openEnquiries ?: null],
        ['label' => 'Point & Ranking', 'icon' => 'trophy', 'href' => route('vendor.points.index'), 'active' => request()->routeIs('vendor.points.*')],
        ['label' => 'Akaun', 'icon' => 'user', 'href' => route('account.edit'), 'active' => request()->routeIs('account.*')],
    ];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" area="Dashboard vendor" :heading="$heading" :subheading="$subheading">
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
