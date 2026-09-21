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
            $item('Majlis saya', 'rings', 'dashboard', ['dashboard', 'weddings.*']),
        ]],
        ['label' => 'Perancangan', 'items' => [
            $item('Checklist', 'check', 'checklist.index', 'checklist.*', $outstandingTasks),
            $item('Timeline', 'calendar', 'timeline.index', 'timeline.*'),
            $item('Bajet', 'wallet', 'budget.index', 'budget.*'),
        ]],
        ['label' => 'Tetamu', 'items' => [
            $item('Senarai tetamu', 'users', 'guests.index', 'guests.*'),
            $item('Kad jemputan', 'mail', 'site.edit', 'site.*'),
        ]],
        ['label' => 'Vendor', 'items' => [
            ['label' => 'Cari vendor', 'icon' => 'search', 'href' => route('vendors.index'), 'active' => false, 'badge' => null],
            $item('Tempahan', 'receipt', 'bookings.index', 'bookings.*'),
            $item('Enquiry', 'chat', 'enquiries.index', 'enquiries.*', $repliedEnquiries),
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
                default => 'Selamat pengantin baru',
            },
            'wedding' => true,
        ]
        : ['title' => 'Perancang majlis', 'detail' => 'Cipta majlis anda untuk bermula', 'wedding' => true];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :context="$context" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
