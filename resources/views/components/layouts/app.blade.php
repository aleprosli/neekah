<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description ?? 'Neekah — Semua Urusan Majlis, Satu Platform.' }}">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-surface font-sans text-ink">
        <x-impersonation-banner />
        {{ $slot }}
    </body>
</html>
