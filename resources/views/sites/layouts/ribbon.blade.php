{{-- A coloured band carries the names across the card. --}}
<section class="relative flex min-h-[92vh] flex-col items-center justify-center px-6 text-center">
    @include('sites.ornaments.'.$ornament, ['position' => 'top-left'])
    @include('sites.ornaments.'.$ornament, ['position' => 'bottom-right'])
    <div class="relative w-full max-w-sm">
        @if ($site->cover_image)
            <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="mx-auto mb-0 h-52 w-full rounded-t-2xl object-cover">
        @endif
        <div class="nk-ribbon -mx-4 px-6 py-6 shadow-lg">
            <p class="text-[11px] tracking-[0.38em] uppercase opacity-80">{{ $eyebrow }}</p>
            <p class="nk-script mt-3 text-4xl">{{ $site->bride_name }} &amp; {{ $site->groom_name }}</p>
        </div>
        <p class="nk-body mt-8 text-lg tracking-[0.12em]">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
        @if ($site->startsAtLabel())<p class="nk-muted mt-1 text-sm">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) &ndash; {{ $site->endsAtLabel() }}@endif</p>@endif
        <div class="mt-8">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
    </div>
</section>
