<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
    @php
        // Pages that only pass a title and description keep working; the
        // controller has already had its say by the time this runs.
        app(App\Support\Seo::class)->fallbackTitle($title ?? null)->fallbackDescription($description ?? null);
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <x-seo.tags />

        <link rel="icon" type="image/png" href="{{ asset(config('neekah.brand.icon')) }}">
        <link rel="apple-touch-icon" href="{{ asset(config('neekah.brand.apple_icon')) }}">
        <meta name="theme-color" content="#7a263a">

        {{-- Which shell this page is built in. resources/js/navigation.js swaps
             regions only between pages of the same shell; a dashboard and a
             public page hold their <main> in completely different furniture. --}}
        <meta name="page-shell" content="{{ $shell ?? 'site' }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Read once by resources/js/i18n.js. Data, not a script that runs,
             so the CSP has nothing to object to. --}}
        <script type="application/json" id="translations">@json(App\Support\Translations::forClient($shell ?? 'site'))</script>

        <x-analytics />
    </head>
    <body class="min-h-screen bg-surface font-sans text-ink">
        @if ($preloader ?? true)
            <x-site.preloader />
        @endif

        <x-impersonation-banner />
        {{ $slot }}

        {{-- Third-party scripts a page pushed here, loaded after its markup. --}}
        @stack('scripts')
    </body>
</html>
