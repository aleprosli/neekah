@props(['site', 'preview' => false, 'tone' => 'light'])

@php
    $chip = $tone === 'dark'
        ? 'border-white/20 bg-white/5 text-white/80 hover:bg-white/10'
        : 'border-black/10 bg-white/70 text-neutral-700 hover:bg-white';

    $links = collect([
        $site->map_url ? ['href' => $site->map_url, 'label' => 'Lokasi', 'icon' => '📍', 'external' => true] : null,
        ['href' => '#atur-cara', 'label' => 'Atur cara', 'icon' => '🕰️', 'external' => false],
        $site->acceptsRsvps() ? ['href' => '#rsvp', 'label' => 'RSVP', 'icon' => '💌', 'external' => false] : null,
        ['href' => $preview ? '#' : route('sites.calendar', ['subdomain' => $site->subdomain]), 'label' => 'Kalendar', 'icon' => '🗓️', 'external' => false],
    ])->filter();
@endphp

<nav class="flex flex-wrap justify-center gap-2" aria-label="Pintasan">
    @foreach ($links as $link)
        <a href="{{ $link['href'] }}" @if ($link['external']) target="_blank" rel="noopener" @endif
           class="flex items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-medium transition {{ $chip }}">
            <span aria-hidden="true">{{ $link['icon'] }}</span>{{ $link['label'] }}
        </a>
    @endforeach
</nav>
