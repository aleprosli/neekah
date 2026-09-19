{{-- A photograph across the top of the sheet, the names laid under its fade. --}}
<section class="relative flex min-h-[100svh] flex-col">
    <div class="relative h-[58svh] min-h-80 overflow-hidden">
        @if ($site->cover_image)
            <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="size-full object-cover">
        @else
            <div class="nk-photo-fallback flex size-full items-center justify-center">@include('sites.partials.monogram', ['site' => $site, 'size' => 'size-24 text-4xl'])</div>
        @endif
        <div class="absolute inset-0" style="background: linear-gradient(to top, var(--nk-page) 2%, transparent 45%)"></div>
    </div>
    @include('sites.ornaments.'.$ornament, ['position' => 'bottom-right'])
    <div class="relative -mt-10 flex flex-1 flex-col items-center justify-center px-8 pb-14 text-center">
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        <h1 class="nk-script mt-4">
            <span class="nk-name block text-5xl">{{ $site->bride_name }}</span>
            <span class="nk-accent block text-3xl">&amp;</span>
            <span class="nk-name block text-5xl">{{ $site->groom_name }}</span>
        </h1>
        @include('sites.partials.divider-css', ['class' => 'my-7'])
        @include('sites.partials.date-block', ['site' => $site])
    </div>
</section>
