{{-- Full-bleed photograph with the names laid over it. --}}
<section class="relative flex min-h-[94vh] flex-col justify-end overflow-hidden">
    <div class="absolute inset-0">
        @if ($site->cover_image)
            <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="size-full object-cover">
        @else
            <div class="size-full" style="background: radial-gradient(ellipse at 50% 20%, var(--nk-panel), var(--nk-page))"></div>
        @endif
        <div class="absolute inset-0" style="background: linear-gradient(to top, var(--nk-page) 6%, transparent 62%)"></div>
    </div>
    @include('sites.ornaments.'.$ornament, ['position' => 'top-right'])
    <div class="relative px-6 pb-16 text-center">
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        <p class="nk-script nk-name mt-5 text-5xl sm:text-6xl">{{ $site->bride_name }}</p>
        <p class="nk-accent my-2 text-2xl">&amp;</p>
        <p class="nk-script nk-name text-5xl sm:text-6xl">{{ $site->groom_name }}</p>
        @include('sites.partials.divider-css', ['class' => 'mt-7'])
        <p class="nk-body mt-6 text-lg">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
        <div class="mt-8">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
    </div>
</section>
