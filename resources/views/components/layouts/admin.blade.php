@props(['title', 'heading' => null, 'subheading' => null])

@php
    $pendingVendors = \App\Models\Vendor::where('status', \App\Enums\VendorStatus::Pending)->count();
    $openViolations = \App\Models\VendorViolation::where('status', \App\Enums\ViolationStatus::Open)->count();
    $reportedReviews = \App\Models\Review::whereNotNull('reported_at')->whereNull('hidden_at')->count();
    $item = fn (string $label, string $icon, string $route, string $pattern, ?int $badge = null): array => [
        'label' => $label, 'icon' => $icon, 'href' => route($route), 'active' => request()->routeIs($pattern), 'badge' => $badge ?: null,
    ];
    $nav = [
        ['label' => null, 'items' => [
            $item('Ringkasan', 'chart', 'admin.dashboard', 'admin.dashboard'),
            $item('Analitik', 'trending', 'admin.analytics', 'admin.analytics'),
        ]],
        ['label' => 'Marketplace', 'items' => [
            $item('Vendor', 'store', 'admin.vendors.index', 'admin.vendors.*', $pendingVendors),
            $item('Kategori', 'layers', 'admin.categories.index', 'admin.categories.*'),
            $item('Tempahan', 'receipt', 'admin.bookings.index', 'admin.bookings.*'),
            $item('Kewangan', 'wallet', 'admin.transactions.index', 'admin.transactions.*'),
            $item('Review', 'star', 'admin.reviews.index', 'admin.reviews.*', $reportedReviews),
            $item('Laporan', 'alert', 'admin.violations.index', 'admin.violations.*', $openViolations),
        ]],
        ['label' => 'Pengguna', 'items' => [
            $item('Semua pengguna', 'users', 'admin.users.index', 'admin.users.*'),
            $item('Pengumuman', 'mail', 'admin.announcements.index', 'admin.announcements.*'),
        ]],
        ['label' => 'Kandungan', 'items' => [
            $item('Checklist induk', 'check', 'admin.checklist.index', 'admin.checklist.*'),
            $item('Blog', 'pencil', 'admin.posts.index', 'admin.posts.*'),
        ]],
        ['label' => 'Sistem', 'items' => [
            $item('Tetapan', 'settings', 'admin.settings.edit', 'admin.settings.*'),
            ['label' => 'Log sistem', 'icon' => 'pulse', 'href' => route('log-viewer.index'), 'active' => request()->routeIs('log-viewer.*'), 'badge' => null],
        ]],
    ];
    $context = ['title' => 'Panel admin', 'detail' => now()->translatedFormat('l, j F Y')];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :context="$context" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
