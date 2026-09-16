<x-layouts.app title="Blog">
    <x-site.header />

    <main class="mx-auto max-w-6xl px-4 pt-24 pb-20 sm:px-6 lg:px-10 lg:pt-28">
        <header class="max-w-2xl">
            <p class="text-[11px] font-semibold tracking-wide text-brand-600 uppercase">Blog Neekah</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight lg:text-4xl">Panduan &amp; idea majlis perkahwinan</h1>
            <p class="mt-3 text-ink-muted">Tip merancang majlis, memilih vendor, menyusun bajet dan menjemput tetamu, daripada pasukan Neekah.</p>
        </header>

        {{-- resources/js/components/public/BlogIndexPage.vue --}}
        <div data-vue="blog-index-page" data-props="@vueProps($props)"></div>
    </main>

    <x-site.footer />
</x-layouts.app>
