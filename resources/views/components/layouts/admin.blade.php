@props(['title', 'heading' => null, 'subheading' => null])

@php
    $pendingVendors = \App\Models\Vendor::where('status', \App\Enums\VendorStatus::Pending)->count();
    $openViolations = \App\Models\VendorViolation::where('status', \App\Enums\ViolationStatus::Open)->count();
    $nav = [
        ['label' => 'Ringkasan', 'icon' => '📊', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Vendor', 'icon' => '🏪', 'href' => route('admin.vendors.index'), 'active' => request()->routeIs('admin.vendors.*'), 'badge' => $pendingVendors ?: null],
        ['label' => 'Pengguna', 'icon' => '👥', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
        ['label' => 'Kategori', 'icon' => '🗂️', 'href' => route('admin.categories.index'), 'active' => request()->routeIs('admin.categories.*')],
        ['label' => 'Tempahan', 'icon' => '🧾', 'href' => route('admin.bookings.index'), 'active' => request()->routeIs('admin.bookings.*')],
        ['label' => 'Laporan', 'icon' => '⚠️', 'href' => route('admin.violations.index'), 'active' => request()->routeIs('admin.violations.*'), 'badge' => $openViolations ?: null],
        ['label' => 'Kewangan', 'icon' => '💰', 'href' => route('admin.transactions.index'), 'active' => request()->routeIs('admin.transactions.*')],
    ];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
