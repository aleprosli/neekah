@props(['album'])

<div class="flex min-w-0 flex-col">
    <span class="truncate font-medium">{{ $album->wedding->title }}</span>
    <span class="truncate text-xs text-ink-muted">
        <a href="{{ route('admin.users.show', $album->wedding->user) }}" class="hover:text-brand-700">{{ $album->wedding->user->email }}</a>
        · {{ $album->wedding->event_date->translatedFormat('j M Y') }}
    </span>
</div>
