{{-- The page a guest opens from the Kamera Majlis QR. Its own shell: no
     marketplace header, and a small dictionary of its own. --}}
<x-layouts.app :title="__('pages.camera.guest_title', ['wedding' => $album->wedding->title])" shell="camera">
    <main class="mx-auto flex min-h-screen max-w-lg flex-col items-center justify-center gap-4 px-4 py-10 text-center">
        <span class="text-4xl" aria-hidden="true">📸</span>
        <h1 class="font-display text-2xl font-semibold">{{ $album->title ?: $album->wedding->title }}</h1>
        <p class="text-sm text-ink-muted">{{ $closed ? __('pages.camera.guest_closed') : __('pages.camera.guest_intro') }}</p>
    </main>
</x-layouts.app>
