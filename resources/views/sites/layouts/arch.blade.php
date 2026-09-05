{{-- A tall arched window, the shape used on gate and mosque motifs. --}}
<section class="relative flex min-h-[92vh] flex-col items-center justify-center px-6 text-center">
    @include('sites.ornaments.'.$ornament, ['position' => 'top-left'])
    @include('sites.ornaments.'.$ornament, ['position' => 'top-right'])
    <div class="relative w-full max-w-xs">
        <div class="nk-arch relative mx-auto flex aspect-[3/4.4] w-full items-end justify-center overflow-hidden" style="background: var(--nk-panel); border: 1px solid var(--nk-line); box-shadow: 0 0 0 8px var(--nk-page), 0 0 0 9px var(--nk-accent)">
            @if ($site->cover_image)
                <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="absolute inset-0 size-full object-cover">
                <div class="absolute inset-0" style="background: linear-gradient(to top, var(--nk-page), transparent 55%)"></div>
            @endif
            <div class="relative px-6 pb-8">
                <p class="nk-eyebrow">{{ $eyebrow }}</p>
                <p class="nk-script nk-name mt-4 text-4xl">{{ $site->bride_name }}</p>
                <p class="nk-accent text-xl">&amp;</p>
                <p class="nk-script nk-name text-4xl">{{ $site->groom_name }}</p>
            </div>
        </div>
        @include('sites.partials.divider-css', ['class' => 'mt-8'])
        <p class="nk-body mt-6 text-lg tracking-[0.12em]">{{ $site->event_date->translatedFormat('j F Y') }}</p>
        @if ($site->startsAtLabel())<p class="nk-muted mt-1 text-sm">{{ $site->startsAtLabel() }}</p>@endif
        <div class="mt-8">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
    </div>
</section>
