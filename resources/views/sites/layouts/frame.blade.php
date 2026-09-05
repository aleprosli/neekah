{{-- A double-ruled border like a printed card. --}}
<section class="relative flex min-h-[92vh] items-center justify-center px-6 py-12">
    <div class="nk-frame-inner relative w-full max-w-sm px-7 py-14 text-center">
        @foreach (['top-left', 'bottom-right'] as $corner)
            @include('sites.ornaments.'.$ornament, ['position' => $corner])
        @endforeach
        <div class="relative">
            <p class="nk-eyebrow">{{ $eyebrow }}</p>
            @if ($site->cover_image)
                <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="mx-auto my-7 h-40 w-full rounded object-cover">
            @endif
            <p class="nk-script nk-name mt-7 text-4xl sm:text-5xl">{{ $site->bride_name }}</p>
            <p class="nk-accent my-2 text-2xl">&amp;</p>
            <p class="nk-script nk-name text-4xl sm:text-5xl">{{ $site->groom_name }}</p>
            @include('sites.partials.divider-css', ['class' => 'mt-7'])
            <p class="nk-body mt-6 tracking-[0.18em] uppercase">{{ $site->event_date->translatedFormat('j . m . Y') }}</p>
            @if ($site->startsAtLabel())<p class="nk-muted mt-1 text-sm">{{ $site->startsAtLabel() }}</p>@endif
            <div class="mt-8">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
        </div>
    </div>
</section>
