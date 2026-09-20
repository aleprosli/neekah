@props(['vendor'])

@php
    // What an admin looks for before approving: a filled-in profile, at least
    // one live package and three portfolio pictures — the same two tests
    // Vendor::hasCompleteProfile() and hasCompleteCatalogue() apply.
    $packages = $vendor->active_packages_count ?? 0;
    $pictures = $vendor->portfolio_items_count ?? 0;

    $pieces = [
        ['label' => 'Profil', 'done' => $vendor->hasCompleteProfile(), 'missing' => 'Tiada tagline, perihal, telefon atau harga'],
        ['label' => $packages ? $packages.' pakej' : 'Pakej', 'done' => $packages > 0, 'missing' => 'Tiada pakej aktif'],
        ['label' => $pictures ? $pictures.' gambar' : 'Gambar', 'done' => $pictures >= 3, 'missing' => 'Perlu sekurang-kurangnya 3 gambar portfolio'],
        ['label' => 'Logo', 'done' => filled($vendor->logo), 'missing' => 'Tiada logo'],
    ];
    $done = collect($pieces)->where('done', true)->count();
@endphp

<span class="flex min-w-0 flex-col gap-1">
    <span class="text-xs font-semibold {{ $done === count($pieces) ? 'text-emerald-700' : 'text-ink-muted' }}">
        {{ $done === count($pieces) ? 'Lengkap' : $done.' / '.count($pieces).' siap' }}
    </span>
    <span class="flex flex-wrap gap-1">
        @foreach ($pieces as $piece)
            <span
                @class([
                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium whitespace-nowrap',
                    'bg-emerald-50 text-emerald-800' => $piece['done'],
                    'bg-surface-muted text-ink-muted line-through decoration-ink-muted/40' => ! $piece['done'],
                ])
                @unless ($piece['done']) title="{{ $piece['missing'] }}" @endunless
            >{{ $piece['label'] }}</span>
        @endforeach
    </span>
</span>
