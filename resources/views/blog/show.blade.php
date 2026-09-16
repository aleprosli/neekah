<x-layouts.app :title="$post->title">
    <x-site.header />

    <main class="px-4 pt-24 pb-20 sm:px-6 lg:pt-28">
        {{-- resources/js/components/public/BlogPostPage.vue --}}
        <div data-vue="blog-post-page" data-props="@vueProps($props)"></div>
    </main>

    <x-site.footer />
</x-layouts.app>
