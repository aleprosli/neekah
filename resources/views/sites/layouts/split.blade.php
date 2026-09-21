{{-- A portrait photograph set off the sheet by an offset rule, details beneath. --}}
<section class="relative flex min-h-[100svh] flex-col justify-center px-8 py-16 text-center">
    @include('sites.ornaments.'.$ornament, ['position' => 'bottom-left'])
    <div class="relative mx-auto w-full max-w-xs">
        <div class="relative">
            <div class="absolute inset-0 translate-x-3 translate-y-3" style="border: 1px solid var(--nk-accent)"></div>
            @if ($site->cover_image)
                <img data-card-cover src="{{ $site->coverUrl() }}" alt="" class="relative aspect-[4/5] w-full object-cover">
            @else
                <div class="nk-photo-fallback relative flex aspect-[4/5] w-full items-center justify-center">@include('sites.partials.monogram', ['site' => $site])</div>
            @endif
        </div>
        <p class="nk-eyebrow mt-12">{{ $eyebrow }}</p>
        <h1 class="nk-script nk-name mt-4 text-5xl">{{ $site->bride_name }} <span class="nk-accent block text-3xl">&amp;</span> {{ $site->groom_name }}</h1>
        <div class="nk-hairline mx-auto my-7 w-16 border-t"></div>
        @include('sites.partials.date-block', ['site' => $site])
    </div>
</section>
