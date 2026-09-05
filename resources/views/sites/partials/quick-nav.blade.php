@props(['site', 'preview' => false])
@php
    $links = collect([
        $site->map_url ? ['href' => $site->map_url, 'label' => 'Lokasi', 'icon' => '📍', 'external' => true] : null,
        ['href' => '#atur-cara', 'label' => 'Atur cara', 'icon' => '🕰️', 'external' => false],
        $site->acceptsRsvps() ? ['href' => '#rsvp', 'label' => 'RSVP', 'icon' => '💌', 'external' => false] : null,
        $site->showsGift() ? ['href' => '#hadiah', 'label' => 'Hadiah', 'icon' => '🎁', 'external' => false] : null,
        ['href' => $preview ? '#' : route('sites.calendar', ['subdomain' => $site->subdomain]), 'label' => 'Kalendar', 'icon' => '🗓️', 'external' => false],
    ])->filter();
@endphp
<nav class="flex flex-wrap justify-center gap-2" aria-label="Pintasan">
    @foreach ($links as $link)
        <a href="{{ $link['href'] }}" @if ($link['external']) target="_blank" rel="noopener" @endif class="nk-chip flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-medium">
            <span aria-hidden="true">{{ $link['icon'] }}</span>{{ $link['label'] }}
        </a>
    @endforeach
</nav>
