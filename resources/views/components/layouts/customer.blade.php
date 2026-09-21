@props(['title', 'heading' => null, 'subheading' => null])

@php
    $user = auth()->user();
    $repliedEnquiries = $user->enquiries()->where('status', \App\Enums\EnquiryStatus::Replied)->count();
    $wedding = $user->weddings()->latest('event_date')->first();
    $outstandingTasks = $wedding?->tasks()->outstanding()->whereNotNull('due_date')->whereDate('due_date', '<=', today())->count();
    $item = fn (string $label, string $icon, string $route, string|array $pattern, ?int $badge = null): array => [
        'label' => $label, 'icon' => $icon, 'href' => route($route), 'active' => App\Support\Locales::routeIs($pattern), 'badge' => $badge ?: null,
    ];
    $nav = [
        ['label' => null, 'items' => [
            $item(__('pages.sidebar_couple.majlis_saya'), 'rings', 'dashboard', ['dashboard', 'weddings.*']),
        ]],
        ['label' => __('pages.sidebar_couple.perancangan'), 'items' => [
            $item(__('pages.sidebar_couple.checklist'), 'check', 'checklist.index', 'checklist.*', $outstandingTasks),
            $item(__('pages.sidebar_couple.timeline'), 'calendar', 'timeline.index', 'timeline.*'),
            $item(__('pages.sidebar_couple.bajet'), 'wallet', 'budget.index', 'budget.*'),
        ]],
        ['label' => __('pages.sidebar_couple.tetamu'), 'items' => [
            $item(__('pages.sidebar_couple.senarai_tetamu'), 'users', 'guests.index', 'guests.*'),
            $item(__('pages.sidebar_couple.kad_jemputan'), 'mail', 'site.edit', 'site.*'),
        ]],
        ['label' => __('pages.sidebar_couple.vendor'), 'items' => [
            ['label' => __('pages.sidebar_couple.cari_vendor'), 'icon' => 'search', 'href' => route('vendors.index'), 'active' => false, 'badge' => null],
            $item(__('pages.sidebar_couple.tempahan'), 'receipt', 'bookings.index', 'bookings.*'),
            $item(__('pages.sidebar_couple.enquiry'), 'chat', 'enquiries.index', 'enquiries.*', $repliedEnquiries),
        ]],
    ];

    // The one number every couple wants to see on every page.
    $daysLeft = $wedding ? (int) today()->diffInDays($wedding->event_date, false) : null;
    $context = $wedding
        ? [
            'title' => $wedding->title,
            'detail' => $wedding->event_date->translatedFormat('j F Y').' · '.match (true) {
                $daysLeft > 0 => $daysLeft.' hari lagi',
                $daysLeft === 0 => 'Hari ini!',
                default => __('pages.sidebar_couple.selamat_pengantin_baru'),
            },
            'wedding' => true,
        ]
        : ['title' => __('pages.sidebar_couple.perancang_majlis'), 'detail' => __('pages.sidebar_couple.cipta_majlis_anda_untuk'), 'wedding' => true];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :context="$context" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
