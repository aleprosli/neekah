{{-- The page a guest opens from the Neekah Kenangan QR. Its own shell: no
     marketplace header, and a small dictionary of its own. The Vue island
     does the work; without JavaScript a plain photo form still posts. --}}
<x-layouts.app :title="__('pages.camera.guest_title', ['wedding' => $album->displayTitle()])" shell="camera">
    <main class="min-h-screen bg-ivory">
        {{-- resources/js/components/camera/CameraGuestPage.vue --}}
        <div data-vue="camera-guest-page" data-props="@vueProps($props)">
            <div class="mx-auto flex max-w-lg flex-col items-center gap-4 px-4 py-10 text-center">
                <span class="text-4xl" aria-hidden="true">📸</span>
                <h1 class="font-display text-2xl font-semibold">{{ $album->displayTitle() }}</h1>
                @if ($closed)
                    <p class="text-sm text-ink-muted">{{ __('pages.camera.guest_closed') }}</p>
                @else
                    <p class="text-sm text-ink-muted">{{ __('pages.camera.guest_intro') }}</p>
                    @if (session('status'))
                        <p class="text-sm text-emerald-700">{{ session('status') }}</p>
                    @endif
                    <form method="POST" action="{{ route('camera.upload.fallback', $album) }}" enctype="multipart/form-data" class="flex w-full flex-col gap-3">
                        @csrf
                        <input type="file" name="photo" accept="image/*" required class="text-sm">
                        <x-form.image-hint />
                        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white">{{ __('pages.camera.fallback_upload') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
