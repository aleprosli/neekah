{{-- A photo grid cover, like an album spread. --}}
<section class="relative flex min-h-[92vh] flex-col justify-center px-6 py-14">
    <div class="mx-auto w-full max-w-sm">
        <div class="grid grid-cols-3 grid-rows-3 gap-2">
            @for ($tile = 0; $tile < 6; $tile++)
                <div @class(['overflow-hidden rounded-lg', 'col-span-2 row-span-2' => $tile === 0]) style="background: var(--nk-panel); border: 1px solid var(--nk-line)">
                    @if ($site->cover_image)
                        <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="size-full object-cover" style="opacity: {{ $tile === 0 ? 1 : 0.55 + $tile * 0.06 }}">
                    @endif
                </div>
            @endfor
        </div>
        <div class="mt-9 text-center">
            <p class="nk-eyebrow">{{ $eyebrow }}</p>
            <p class="nk-script nk-name mt-4 text-5xl">{{ $site->bride_name }} &amp; {{ $site->groom_name }}</p>
            <div class="nk-hairline mx-auto my-7 w-20 border-t"></div>
            <p class="nk-body tracking-[0.2em] uppercase">{{ $site->event_date->translatedFormat('j F Y') }}</p>
            <div class="mt-8">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
        </div>
    </div>
</section>
