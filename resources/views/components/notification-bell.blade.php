@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->latest()->limit(6)->get();
    $unreadCount = $user->unreadNotifications()->count();
@endphp

<details data-popover class="relative">
    <summary class="relative flex size-9 cursor-pointer list-none items-center justify-center rounded-full transition select-none hover:bg-surface-muted [&::-webkit-details-marker]:hidden" aria-label="Notifikasi">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        @if ($unreadCount)
            <span class="absolute -top-0.5 -right-0.5 flex min-w-4 items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-semibold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </summary>

    <div class="absolute top-full right-0 z-20 mt-2 w-80 overflow-hidden rounded-2xl border border-line bg-surface-raised text-sm shadow-xl shadow-brand-900/10">
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <p class="font-semibold">Notifikasi</p>
            @if ($unreadCount)
                <form method="POST" action="{{ route('notifications.read') }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="text-xs font-medium text-brand-600 hover:underline">Tandakan dibaca</button>
                </form>
            @endif
        </div>

        @forelse ($unread as $notification)
            <a href="{{ route('notifications.show', $notification->id) }}" class="flex gap-3 border-b border-line px-4 py-3 transition last:border-b-0 hover:bg-surface-muted">
                <span class="text-lg leading-none">{{ $notification->data['icon'] ?? '🔔' }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium">{{ $notification->data['title'] ?? 'Notifikasi' }}</span>
                    <span class="block truncate text-xs text-ink-muted">{{ $notification->data['body'] ?? '' }}</span>
                    <span class="block text-xs text-ink-muted">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <p class="px-4 py-8 text-center text-sm text-ink-muted">Tiada notifikasi baharu.</p>
        @endforelse

        <a href="{{ route('notifications.index') }}" class="block border-t border-line px-4 py-3 text-center text-xs font-medium text-brand-600 hover:bg-surface-muted">Lihat semua</a>
    </div>
</details>
