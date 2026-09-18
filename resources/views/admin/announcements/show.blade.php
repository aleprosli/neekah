<x-layouts.admin
    title="Pengumuman"
    :heading="$announcement->subject"
    :subheading="$announcement->audience->label().' · '.$announcement->status->label().($announcement->sent_at ? ' '.$announcement->sent_at->translatedFormat('j M Y, g:i A') : '')"
>
    <x-slot:actions>
        <a href="{{ route('admin.announcements.index') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Kembali</a>
    </x-slot:actions>

    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
        <article class="min-w-0 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-display text-xl font-semibold">{{ $announcement->subject }}</h2>

            @foreach ($announcement->paragraphs() as $paragraph)
                <p class="mt-3 text-sm whitespace-pre-line">{{ $paragraph }}</p>
            @endforeach

            @if ($announcement->hasAction())
                <a href="{{ $announcement->action_url }}" class="mt-5 inline-flex rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                    {{ $announcement->action_label }}
                </a>
            @endif
        </article>

        <dl class="flex h-fit min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-6 text-sm">
            <div>
                <dt class="text-ink-muted">Penerima</dt>
                <dd class="font-medium">{{ $announcement->audience->label() }}</dd>
            </div>
            <div>
                <dt class="text-ink-muted">Dihantar kepada</dt>
                <dd class="font-medium">{{ $announcement->status === \App\Enums\AnnouncementStatus::Sent ? $announcement->recipients_count.' penerima' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-ink-muted">Status</dt>
                <dd class="font-medium">{{ $announcement->status->label() }}</dd>
            </div>
            <div>
                <dt class="text-ink-muted">Tarikh hantar</dt>
                <dd class="font-medium">{{ $announcement->sent_at?->translatedFormat('j M Y, g:i A') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-ink-muted">Ditulis oleh</dt>
                <dd class="font-medium">{{ $announcement->author?->name ?? 'Admin' }}</dd>
            </div>
        </dl>
    </div>
</x-layouts.admin>
