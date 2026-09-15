<x-layouts.app :title="$post->title">
    <x-site.header />

    <main class="px-4 pt-24 pb-20 sm:px-6 lg:pt-28">
        <article class="mx-auto max-w-3xl">
            @unless ($post->isPublished())
                <p class="mb-6 rounded-2xl border border-gold-400 bg-gold-300/40 px-5 py-3 text-sm text-brand-900">
                    Pratonton draf. Hanya admin boleh melihat halaman ini sehingga artikel disiarkan.
                </p>
            @endunless

            <nav aria-label="Breadcrumb" class="text-xs font-semibold tracking-wide text-brand-600 uppercase">
                <a href="{{ route('blog.index') }}" class="hover:text-brand-800">Blog</a>
            </nav>

            <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl lg:text-5xl">{{ $post->title }}</h1>

            <p class="mt-4 text-sm text-ink-muted">
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->localPublishedAt()->translatedFormat('j F Y') }}</time> ·
                @endif
                {{ $post->readingMinutes() }} min bacaan
            </p>

            @if ($post->cover_image)
                <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" fetchpriority="high" decoding="async" class="mt-8 aspect-[16/9] w-full rounded-3xl object-cover">
            @endif

            {{-- Cleaned by App\Support\HtmlSanitizer when the article was saved. --}}
            <div class="nk-prose mt-10">{!! $post->body !!}</div>
        </article>

        @if ($related->isNotEmpty())
            <section class="mx-auto mt-20 max-w-6xl border-t border-line pt-10" aria-labelledby="baca-juga">
                <h2 id="baca-juga" class="font-display text-2xl font-semibold">Baca juga</h2>
                <div class="mt-6 grid gap-8 sm:grid-cols-3">
                    @foreach ($related as $other)
                        <a href="{{ $other->url() }}" class="group flex flex-col gap-3">
                            <div class="aspect-[16/10] overflow-hidden rounded-2xl bg-linear-to-br from-brand-100 to-gold-300">
                                @if ($other->cover_image)
                                    <img src="{{ $other->coverThumbnailUrl() }}" alt="{{ $other->title }}" loading="lazy" decoding="async" class="size-full object-cover">
                                @endif
                            </div>
                            <h3 class="font-display text-lg leading-snug font-semibold group-hover:text-brand-700">{{ $other->title }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <x-site.footer />
</x-layouts.app>
