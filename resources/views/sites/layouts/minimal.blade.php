{{-- Type only. No ornament, no photograph, all whitespace. --}}
<section class="relative flex min-h-[94vh] flex-col items-center justify-center px-8 text-center">
    @include('sites.ornaments.'.$ornament, ['position' => 'top-left'])
    <div class="relative max-w-sm">
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        <h1 class="nk-name mt-10 text-4xl leading-[1.15] font-light tracking-tight sm:text-5xl">
            {{ $site->bride_name }}<br><span class="nk-muted text-2xl">&amp;</span><br>{{ $site->groom_name }}
        </h1>
        <div class="nk-hairline mx-auto my-10 w-16 border-t"></div>
        <p class="nk-body text-sm tracking-[0.3em] uppercase">{{ $site->event_date->translatedFormat('j F Y') }}</p>
        @if ($site->startsAtLabel())<p class="nk-muted mt-2 text-sm tracking-[0.2em]">{{ $site->startsAtLabel() }}</p>@endif
        <div class="mt-10">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
    </div>
</section>
