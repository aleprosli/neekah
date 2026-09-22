<x-layouts.app :title="$post->title">
    <x-site.header />

    {{-- The same paper as the rest of the site; the ornament stays faint and
         above the fold so the article itself is plain ink on paper. --}}
    <main class="relative overflow-hidden bg-ivory">
        <x-site.ornament name="corner-peony" class="absolute -top-16 -right-20 size-[18rem] rotate-90 opacity-30 sm:size-[24rem]" color="var(--color-brand-200)" color2="var(--color-gold-300)" />

        <div class="relative px-4 pt-24 pb-20 sm:px-6 lg:pt-28">
            {{-- resources/js/components/public/BlogPostPage.vue --}}
            <div data-vue="blog-post-page" data-props="@vueProps($props)"></div>
        </div>
    </main>

    <x-site.footer />
</x-layouts.app>
