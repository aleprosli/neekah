@props([
    /** Fallback page title; the controller's Seo title wins. */
    'title',
    /** FeaturePages::available() without this page: the row at the foot. */
    'explore' => [],
])

{{-- The frame every feature page (/ciri/*) is built on: the site header, a
     feature hero, the page's own sections, then the other features. Pages
     differ only in what goes in the slots, so they read as one set. --}}
<x-layouts.app :title="$title">
    <x-site.header />

    <main class="bg-ivory">
        {{ $hero }}

        {{ $slot }}

        @if ($explore)
            <x-site.feature-explore :pages="$explore" :eyebrow="__('pages.features.explore.eyebrow_more')" :title="__('pages.features.explore.title_more')" />
        @endif
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
