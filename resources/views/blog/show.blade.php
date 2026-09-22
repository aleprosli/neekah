<x-layouts.app :title="$post->title">
    <x-site.header />

    {{-- The same paper as the rest of the site; the ornament stays faint and
         above the fold so the article itself is plain ink on paper. --}}
    <main class="relative bg-ivory">
        <x-site.florals corners="right" />

        <div class="relative px-4 pt-24 pb-20 sm:px-6 lg:pt-28">
            {{-- resources/js/components/public/BlogPostPage.vue --}}
            <div data-vue="blog-post-page" data-props="@vueProps($props)"></div>
        </div>
    </main>

    <x-site.footer />
</x-layouts.app>
