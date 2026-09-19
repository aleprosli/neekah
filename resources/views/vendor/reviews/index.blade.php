<x-layouts.vendor title="Review" heading="Review" subheading="Apa yang pelanggan tulis tentang {{ $vendor->name }}.">
    @if (session('status'))
        <p class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <p class="mb-6 max-w-2xl text-sm text-ink-muted">
        Anda boleh menjawab mana-mana review di sini, dan melaporkan yang anda rasa tidak benar. Hanya admin yang boleh menariknya — supaya rating pada profil anda bermakna sesuatu kepada pengantin yang membacanya.
    </p>

    @if ($reviews->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Belum ada review.</p>
    @else
        <ul class="flex flex-col gap-4">
            @foreach ($reviews as $review)
                <li @class(['min-w-0 rounded-2xl border border-line bg-surface-raised p-4 break-words sm:p-6', 'opacity-60' => $review->isHidden()])>
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $review->authorName() }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ $review->created_at->translatedFormat('j F Y') }} ·
                                {{ $review->isVerified() ? '✓ Tempahan disahkan' : 'Review terbuka' }}
                            </p>
                        </div>
                        <p class="text-sm text-gold-500">{{ str_repeat('★', $review->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed">{{ $review->comment }}</p>

                    @if ($review->photos->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($review->photos as $photo)
                                <li>
                                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                        <img src="{{ $photo->thumbnailUrl() }}" alt="" loading="lazy" class="size-20 rounded-lg object-cover">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($review->isHidden())
                        <p class="mt-3 rounded-xl bg-surface-muted px-3 py-2 text-xs text-ink-muted">
                            Review ini sudah ditarik oleh admin{{ $review->hidden_reason ? ' — '.$review->hidden_reason : '' }}. Ia tidak dipaparkan pada profil anda.
                        </p>
                    @endif

                    @if ($review->hasReply())
                        <div class="mt-3 min-w-0 rounded-xl border-l-2 border-brand-200 bg-surface-muted/60 px-3 py-2">
                            <p class="text-xs font-semibold">Jawapan anda</p>
                            <p class="mt-1 text-sm leading-relaxed">{{ $review->reply }}</p>
                        </div>
                    @endif

                    @if ($review->isReported())
                        <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Dilaporkan pada {{ $review->reported_at->translatedFormat('j F Y') }}. Admin akan memeriksanya.
                        </p>
                    @endif

                    @can('reply', $review)
                        <div class="mt-4 flex flex-col gap-2 border-t border-line pt-4 sm:flex-row sm:gap-3">
                            <details class="min-w-0 flex-1">
                                <summary class="cursor-pointer text-xs font-semibold">{{ $review->hasReply() ? 'Kemas kini jawapan' : 'Jawab review ini' }}</summary>
                                <form method="POST" action="{{ route('vendor.reviews.reply', $review) }}" class="mt-2 flex flex-col gap-2">
                                    @csrf
                                    <textarea name="reply" rows="3" required minlength="5" maxlength="1000" placeholder="Jawapan anda dipaparkan di bawah review ini pada profil awam." class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">{{ old('reply', $review->reply) }}</textarea>
                                    <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 sm:self-start">Hantar jawapan</button>
                                </form>
                            </details>

                            @can('report', $review)
                                <details class="min-w-0 flex-1">
                                    <summary class="cursor-pointer text-xs font-semibold text-ink-muted">Laporkan kepada admin</summary>
                                    <form method="POST" action="{{ route('vendor.reviews.report', $review) }}" class="mt-2 flex flex-col gap-2">
                                        @csrf
                                        <textarea name="reason" rows="3" required minlength="10" maxlength="1000" placeholder="Terangkan kenapa review ini tidak benar. Review kekal dipaparkan sehingga admin memeriksanya." class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">{{ old('reason') }}</textarea>
                                        <button type="submit" class="rounded-full border border-line px-4 py-2 text-xs font-semibold transition hover:border-brand-400 sm:self-start">Hantar laporan</button>
                                    </form>
                                </details>
                            @endcan
                        </div>
                    @endcan
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
</x-layouts.vendor>
