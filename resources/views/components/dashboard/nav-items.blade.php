{{-- One group's links in the dashboard sidebar. A `locked` item is a Pro
     feature seen from Basic: dimmed, with a lock, and leading to the Pro
     page. --}}
@props(['items'])

<ul class="flex flex-col gap-0.5">
    @foreach ($items as $item)
        <li>
            <a href="{{ $item['href'] }}" @class([
                'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition',
                'bg-surface-raised text-brand-700 shadow-sm shadow-brand-900/5 ring-1 ring-brand-100 [&_svg]:text-brand-600' => $item['active'],
                'text-ink-muted/70 hover:bg-surface-raised/70 hover:text-ink' => ! $item['active'] && ! empty($item['locked']),
                'text-ink-muted hover:bg-surface-raised/70 hover:text-ink' => ! $item['active'] && empty($item['locked']),
            ]) @if ($item['active']) aria-current="page" @endif>
                <x-nav-icon :name="$item['icon']" />
                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                @if (! empty($item['locked']))
                    <x-nav-icon name="lock" class="size-3.5 shrink-0 text-gold-500" />
                    <span class="sr-only">{{ __('nav.pro_locked') }}</span>
                @elseif (! empty($item['badge']))
                    <span class="rounded-full bg-brand-600 px-1.5 py-px text-[11px] font-semibold text-white">{{ $item['badge'] }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
