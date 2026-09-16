@props(['vendor'])

<span class="font-medium">{{ $vendor->name }}</span>
<span class="block text-xs text-ink-muted">{{ $vendor->user->email }}</span>
