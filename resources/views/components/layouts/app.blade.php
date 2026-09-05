<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description ?? 'Neekah — Semua Urusan Majlis, Satu Platform.' }}">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        <link rel="icon" type="image/png" href="{{ asset(config('neekah.brand.icon')) }}">
        <link rel="apple-touch-icon" href="{{ asset(config('neekah.brand.apple_icon')) }}">
        <meta name="theme-color" content="#7a263a">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-surface font-sans text-ink">
        @if ($preloader ?? true)
            <x-site.preloader />
        @endif

        <x-impersonation-banner />
        {{ $slot }}
    </body>
</html>
