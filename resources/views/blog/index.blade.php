<x-layouts.app title="Blog">
    <x-site.header />

    <main class="mx-auto max-w-6xl px-4 pt-24 pb-20 sm:px-6 lg:px-10 lg:pt-28">
        <header class="max-w-2xl">
            <p class="text-[11px] font-semibold tracking-wide text-brand-600 uppercase">Blog Neekah</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight lg:text-4xl">Panduan &amp; idea majlis perkahwinan</h1>
            <p class="mt-3 text-ink-muted">Tip merancang majlis, memilih vendor, menyusun bajet dan menjemput tetamu, daripada pasukan Neekah.</p>
        </header>

        @if ($posts->isEmpty())
            <p class="mt-10 rounded-2xl border border-dashed border-line p-10 text-center text-ink-muted">Artikel pertama akan tersiar tidak lama lagi.</p>
        @else
            <div class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="group">
                        <a href="{{ $post->url() }}" class="flex flex-col gap-3">
                            <div class="aspect-[16/10] overflow-hidden rounded-2xl bg-linear-to-br from-brand-100 to-gold-300">
                                @if ($post->cover_image)
                                    <img src="{{ $post->coverThumbnailUrl() }}" alt="{{ $post->title }}" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 group-hover:scale-[1.03]">
                                @endif
                            </div>
                            <p class="text-xs text-ink-muted">
                                <time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->localPublishedAt()->translatedFormat('j F Y') }}</time>
                                · {{ $post->readingMinutes() }} min bacaan
                            </p>
                            <h2 class="font-display text-xl leading-snug font-semibold transition group-hover:text-brand-700">{{ $post->title }}</h2>
                            <p class="line-clamp-3 text-sm text-ink-muted">{{ $post->summary() }}</p>
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="mt-12">{{ $posts->links() }}</div>
        @endif
    </main>

    <x-site.footer />
</x-layouts.app>
