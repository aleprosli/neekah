<x-layouts.app title="Notifikasi">
    <x-site.header />

    <main class="mx-auto max-w-3xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-display text-3xl font-semibold tracking-tight">Notifikasi</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ $unreadCount ? $unreadCount.' belum dibaca' : 'Semua telah dibaca' }}</p>
            </div>
            @if ($unreadCount)
                <form method="POST" action="{{ route('notifications.read') }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Tandakan semua dibaca</button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif

        @if ($notifications->isEmpty())
            <div class="mt-8 flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
                <span class="text-4xl">🔔</span>
                <h2 class="font-display text-xl font-semibold">Belum ada notifikasi</h2>
                <p class="max-w-sm text-sm text-ink-muted">Tempahan, pembayaran dan balasan enquiry akan muncul di sini.</p>
            </div>
        @else
            <ul class="mt-8 divide-y divide-line rounded-2xl border border-line">
                @foreach ($notifications as $notification)
                    <li @class(['transition', 'bg-brand-50/40' => $notification->unread()])>
                        <a href="{{ route('notifications.show', $notification->id) }}" class="flex gap-4 p-4 hover:bg-surface-muted">
                            <span class="text-xl leading-none">{{ $notification->data['icon'] ?? '🔔' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                                <p class="text-sm text-ink-muted">{{ $notification->data['body'] ?? '' }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $notification->created_at->translatedFormat('j M Y, g:i A') }}</p>
                            </div>
                            @if ($notification->unread())
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600" aria-label="Belum dibaca"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
