@props(['site', 'preview' => false])
@php
    $icons = [
        'map' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'calendar' => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M16 2.5v4M8 2.5v4M3 10h18"/>',
        'rsvp' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
    ];

    $links = collect([
        $site->map_url
            ? ['href' => $site->map_url, 'label' => 'Lokasi', 'icon' => 'map', 'external' => true]
            : (($site->venue_name || $site->venue_address) ? ['href' => '#lokasi', 'label' => 'Lokasi', 'icon' => 'map', 'external' => false] : null),
        ['href' => $preview ? '#' : route('sites.calendar', ['subdomain' => $site->subdomain]), 'label' => 'Kalendar', 'icon' => 'calendar', 'external' => false],
        $site->acceptsRsvps() ? ['href' => '#rsvp', 'label' => 'RSVP', 'icon' => 'rsvp', 'external' => false] : null,
        filled($site->contacts) ? ['href' => '#hubungi', 'label' => 'Hubungi', 'icon' => 'phone', 'external' => false] : null,
        $site->showsGift() ? ['href' => '#hadiah', 'label' => 'Hadiah', 'icon' => 'gift', 'external' => false] : null,
    ])->filter();
@endphp
{{-- The guest's toolbar, fixed to the foot of the card like on every
     printed-card site guests already know: map, calendar, RSVP, call, gift. --}}
<nav data-dock class="nk-dock fixed inset-x-0 bottom-0 z-40 mx-auto max-w-md pb-[env(safe-area-inset-bottom)] sm:bottom-4 sm:rounded-2xl sm:border" aria-label="Pintasan kad">
    <ul class="flex">
        @foreach ($links as $link)
            <li class="flex-1">
                <a href="{{ $link['href'] }}" @if ($link['external']) target="_blank" rel="noopener" @endif class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium tracking-wide transition">
                    <svg class="nk-dock-icon size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$link['icon']] !!}</svg>
                    {{ $link['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
