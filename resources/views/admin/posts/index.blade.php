<x-layouts.admin title="Blog" heading="Blog" subheading="Artikel yang tersiar muncul di neekah.my/blog dan dalam sitemap untuk Google.">
    <x-slot:actions>
        <a href="{{ route('admin.posts.create') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tulis artikel</a>
    </x-slot:actions>

    @if ($posts->isEmpty())
        <div class="rounded-2xl border border-dashed border-line p-10 text-center text-sm text-ink-muted">
            Belum ada artikel. <a href="{{ route('admin.posts.create') }}" class="font-medium text-brand-700 underline underline-offset-4">Tulis yang pertama</a>.
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Artikel</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Dikemas kini</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($posts as $post)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.posts.edit', $post) }}" class="font-medium hover:text-brand-700">{{ $post->title }}</a>
                                <p class="text-xs text-ink-muted">/blog/{{ $post->slug }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if ($post->isPublished())
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Tersiar</span>
                                @elseif ($post->isScheduled())
                                    <span class="inline-flex rounded-full bg-gold-300/50 px-2.5 py-1 text-xs font-semibold text-brand-900">Dijadualkan {{ $post->localPublishedAt()->translatedFormat('j M, g:i A') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-surface-muted px-2.5 py-1 text-xs font-semibold text-ink-muted">Draf</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-muted">{{ $post->updated_at->diffForHumans() }}</td>
                            <td class="space-x-3 px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ $post->url() }}" target="_blank" class="text-xs font-medium text-ink-muted hover:text-brand-700">{{ $post->isPublished() ? 'Lihat' : 'Pratonton' }}</a>
                                <x-confirm-action
                                    :action="route('admin.posts.destroy', $post)"
                                    method="DELETE"
                                    tone="danger"
                                    :title="'Padam artikel '.$post->title.'?'"
                                    message="Artikel dan gambar utamanya akan dipadam. Tindakan ini tidak boleh dibatalkan."
                                    confirm="Padam artikel"
                                    trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                                >Padam</x-confirm-action>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $posts->links() }}</div>
    @endif
</x-layouts.admin>
