<x-layouts.app :title="__('pages.blog_page.blog')">
    <x-site.header />

    {{-- The same paper and florals as the About page and the card gallery. --}}
    <main class="relative overflow-hidden bg-ivory">
        <x-site.ornament name="corner-peony" class="absolute -top-16 -right-20 size-[20rem] rotate-90 opacity-40 sm:size-[28rem]" color="var(--color-brand-200)" color2="var(--color-gold-300)" />
        <x-site.ornament name="leaf-sprig" class="absolute top-[28rem] -left-8 h-64 w-40 opacity-30 sm:h-80 sm:w-52" color="var(--color-brand-200)" />

        <div class="relative mx-auto max-w-6xl px-4 pt-24 pb-20 sm:px-6 lg:px-10 lg:pt-28">
            <header class="max-w-2xl">
                <p class="font-script text-3xl text-brand-600 sm:text-4xl">{{ __('pages.blog_page.blog_neekah') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight lg:text-4xl">{{ __('pages.blog_page.panduan_idea_majlis_perkahwinan') }}</h1>
                <x-site.ornament name="divider-floral" class="mt-4 h-6 w-44" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
                <p class="mt-4 text-ink-muted">{{ __('pages.blog_page.tip_merancang_majlis_memilih_vendor') }}</p>
            </header>

            {{-- resources/js/components/public/BlogIndexPage.vue --}}
            <div data-vue="blog-index-page" data-props="@vueProps($props)"></div>
        </div>
    </main>

    <x-site.footer />
</x-layouts.app>
