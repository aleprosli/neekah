@props(['title', 'heading' => null, 'subheading' => null])

@php
    $user = auth()->user();
    $repliedEnquiries = $user->enquiries()->where('status', \App\Enums\EnquiryStatus::Replied)->count();
    $wedding = $user->weddings()->latest('event_date')->first();
    $outstandingTasks = $wedding?->tasks()->outstanding()->whereNotNull('due_date')->whereDate('due_date', '<=', today())->count();
    $nav = [
        ['label' => 'Majlis saya', 'icon' => 'rings', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard', 'weddings.*')],
        ['label' => 'Checklist', 'icon' => 'check', 'href' => route('checklist.index'), 'active' => request()->routeIs('checklist.*'), 'badge' => $outstandingTasks ?: null],
        ['label' => 'Timeline', 'icon' => 'calendar', 'href' => route('timeline.index'), 'active' => request()->routeIs('timeline.*')],
        ['label' => 'Bajet', 'icon' => 'wallet', 'href' => route('budget.index'), 'active' => request()->routeIs('budget.*')],
        ['label' => 'Tetamu', 'icon' => 'users', 'href' => route('guests.index'), 'active' => request()->routeIs('guests.*')],
        ['label' => 'Kad jemputan', 'icon' => 'mail', 'href' => route('site.edit'), 'active' => request()->routeIs('site.*')],
        ['label' => 'Tempahan', 'icon' => 'receipt', 'href' => route('bookings.index'), 'active' => request()->routeIs('bookings.*')],
        ['label' => 'Enquiry', 'icon' => 'chat', 'href' => route('enquiries.index'), 'active' => request()->routeIs('enquiries.*'), 'badge' => $repliedEnquiries ?: null],
        ['label' => 'Cari vendor', 'icon' => 'search', 'href' => route('vendors.index'), 'active' => false],
        ['label' => 'Akaun', 'icon' => 'user', 'href' => route('account.edit'), 'active' => request()->routeIs('account.*')],
    ];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
