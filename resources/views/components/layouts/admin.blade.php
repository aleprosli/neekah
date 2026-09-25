@props(['title', 'heading' => null, 'subheading' => null])

@php
    $pendingVendors = \App\Models\Vendor::where('status', \App\Enums\VendorStatus::Pending)->count();
    $openViolations = \App\Models\VendorViolation::where('status', \App\Enums\ViolationStatus::Open)->count();
    $reportedReviews = \App\Models\Review::whereNotNull('reported_at')->whereNull('hidden_at')->count();
    $item = fn (string $label, string $icon, string $route, string $pattern, ?int $badge = null): array => [
        'label' => $label, 'icon' => $icon, 'href' => route($route), 'active' => App\Support\Locales::routeIs($pattern), 'badge' => $badge ?: null,
    ];
    $nav = [
        ['label' => null, 'items' => [
            $item(__('pages.sidebar_admin.ringkasan'), 'chart', 'admin.dashboard', 'admin.dashboard'),
            $item(__('pages.sidebar_admin.analitik'), 'trending', 'admin.analytics', 'admin.analytics'),
        ]],
        ['label' => __('pages.sidebar_admin.marketplace'), 'items' => [
            $item(__('pages.sidebar_admin.vendor'), 'store', 'admin.vendors.index', 'admin.vendors.*', $pendingVendors),
            $item(__('pages.sidebar_admin.ciri_vendor'), 'crown', 'admin.vendor-features.index', 'admin.vendor-features.*'),
            $item(__('pages.sidebar_admin.kategori'), 'layers', 'admin.categories.index', 'admin.categories.*'),
            $item(__('pages.sidebar_admin.tempahan'), 'receipt', 'admin.bookings.index', 'admin.bookings.*'),
            $item(__('pages.sidebar_admin.kewangan'), 'wallet', 'admin.transactions.index', 'admin.transactions.*'),
            $item(__('pages.sidebar_admin.review'), 'star', 'admin.reviews.index', 'admin.reviews.*', $reportedReviews),
            $item(__('pages.sidebar_admin.laporan'), 'alert', 'admin.violations.index', 'admin.violations.*', $openViolations),
        ]],
        ['label' => __('pages.sidebar_admin.pengguna'), 'items' => [
            $item(__('pages.sidebar_admin.semua_pengguna'), 'users', 'admin.users.index', 'admin.users.*'),
            $item(__('pages.sidebar_admin.pengumuman'), 'mail', 'admin.announcements.index', 'admin.announcements.*'),
        ]],
        ['label' => __('pages.sidebar_admin.kandungan'), 'items' => [
            $item(__('pages.sidebar_admin.checklist_induk'), 'check', 'admin.checklist.index', 'admin.checklist.*'),
            $item(__('pages.sidebar_admin.blog'), 'pencil', 'admin.posts.index', 'admin.posts.*'),
            $item(__('pages.sidebar_admin.muzik_kad'), 'music', 'admin.card-music.index', 'admin.card-music.*'),
            $item(__('pages.sidebar_admin.kad_nfc'), 'tag', 'admin.card-nfc.index', 'admin.card-nfc.*'),
        ]],
        ['label' => __('pages.sidebar_admin.sistem'), 'items' => [
            $item(__('pages.sidebar_admin.tetapan'), 'settings', 'admin.settings.edit', 'admin.settings.*'),
            ['label' => __('pages.sidebar_admin.log_sistem'), 'icon' => 'pulse', 'href' => route('log-viewer.index'), 'active' => App\Support\Locales::routeIs('log-viewer.*'), 'badge' => null],
        ]],
    ];
    $context = ['title' => __('pages.sidebar_admin.panel_admin'), 'detail' => now()->translatedFormat('l, j F Y')];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :context="$context" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
