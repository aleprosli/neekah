@props(['title', 'heading' => null, 'subheading' => null])

@php
    $pendingVendors = \App\Models\Vendor::where('status', \App\Enums\VendorStatus::Pending)->count();
    $openViolations = \App\Models\VendorViolation::where('status', \App\Enums\ViolationStatus::Open)->count();
    $nav = [
        ['label' => 'Ringkasan', 'icon' => 'chart', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Analitik', 'icon' => 'trending', 'href' => route('admin.analytics'), 'active' => request()->routeIs('admin.analytics')],
        ['label' => 'Vendor', 'icon' => 'store', 'href' => route('admin.vendors.index'), 'active' => request()->routeIs('admin.vendors.*'), 'badge' => $pendingVendors ?: null],
        ['label' => 'Pengguna', 'icon' => 'users', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
        ['label' => 'Kategori', 'icon' => 'layers', 'href' => route('admin.categories.index'), 'active' => request()->routeIs('admin.categories.*')],
        ['label' => 'Tempahan', 'icon' => 'receipt', 'href' => route('admin.bookings.index'), 'active' => request()->routeIs('admin.bookings.*')],
        ['label' => 'Laporan', 'icon' => 'alert', 'href' => route('admin.violations.index'), 'active' => request()->routeIs('admin.violations.*'), 'badge' => $openViolations ?: null],
        ['label' => 'Kewangan', 'icon' => 'wallet', 'href' => route('admin.transactions.index'), 'active' => request()->routeIs('admin.transactions.*')],
        ['label' => 'Blog', 'icon' => 'pencil', 'href' => route('admin.posts.index'), 'active' => request()->routeIs('admin.posts.*')],
        ['label' => 'Tetapan', 'icon' => 'settings', 'href' => route('admin.settings.edit'), 'active' => request()->routeIs('admin.settings.*')],
        ['label' => 'Log sistem', 'icon' => 'pulse', 'href' => route('log-viewer.index'), 'active' => request()->routeIs('log-viewer.*')],
    ];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
