@props(['title', 'heading' => null, 'subheading' => null])

@php
    $user = auth()->user();
    $repliedEnquiries = $user->enquiries()->where('status', \App\Enums\EnquiryStatus::Replied)->count();
    $nav = [
        ['label' => 'Majlis saya', 'icon' => '💍', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard', 'weddings.*')],
        ['label' => 'Tempahan', 'icon' => '🧾', 'href' => route('bookings.index'), 'active' => request()->routeIs('bookings.*')],
        ['label' => 'Enquiry', 'icon' => '💬', 'href' => route('enquiries.index'), 'active' => request()->routeIs('enquiries.*'), 'badge' => $repliedEnquiries ?: null],
        ['label' => 'Cari vendor', 'icon' => '🔍', 'href' => route('vendors.index'), 'active' => false],
    ];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :heading="$heading" :subheading="$subheading">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-layouts.dashboard>
