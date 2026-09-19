{{-- A tall arched window, the shape of a gateway or a mosque's mihrab. --}}
<section class="relative flex min-h-[100svh] flex-col items-center justify-center px-8 py-16 text-center">
    @include('sites.ornaments.'.$ornament, ['position' => 'top-left'])
    @include('sites.ornaments.'.$ornament, ['position' => 'top-right'])
    <div class="relative flex w-full flex-col items-center">
        @include('sites.partials.bismillah', ['template' => $template, 'class' => 'mb-6'])
        <div class="nk-arch relative flex aspect-[3/4] w-56 items-center justify-center overflow-hidden" style="border: 1px solid var(--nk-accent); box-shadow: 0 0 0 7px var(--nk-page), 0 0 0 8px color-mix(in oklab, var(--nk-accent) 45%, transparent)">
            @if ($site->cover_image)
                <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="absolute inset-0 size-full object-cover">
            @else
                <div class="nk-photo-fallback absolute inset-0"></div>
                <div class="relative">@include('sites.partials.monogram', ['site' => $site])</div>
            @endif
        </div>
        <p class="nk-eyebrow mt-10">{{ $eyebrow }}</p>
        <h1 class="nk-script mt-5">
            <span class="nk-name block text-5xl">{{ $site->bride_name }}</span>
            <span class="nk-accent block text-3xl">&amp;</span>
            <span class="nk-name block text-5xl">{{ $site->groom_name }}</span>
        </h1>
        @include('sites.partials.divider-css', ['class' => 'my-7'])
        @include('sites.partials.date-block', ['site' => $site])
    </div>
</section>
