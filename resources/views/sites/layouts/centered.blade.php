{{-- Names centred in a full-bleed panel ringed by ornament. --}}
<section class="relative flex min-h-[92vh] flex-col items-center justify-center px-6 text-center">
    @foreach (['top-left', 'top-right', 'bottom-left', 'bottom-right'] as $corner)
        @include('sites.ornaments.'.$ornament, ['position' => $corner])
    @endforeach
    <div class="relative">
        @if ($site->cover_image)
            <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="mx-auto mb-8 size-40 rounded-full object-cover shadow-xl sm:size-48" style="box-shadow: 0 0 0 6px var(--nk-panel), 0 0 0 7px var(--nk-line)">
        @endif
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        <p class="nk-script mt-7">
            <span class="nk-name block text-5xl sm:text-6xl">{{ $site->bride_name }}</span>
            <span class="nk-accent my-3 block text-3xl">&amp;</span>
            <span class="nk-name block text-5xl sm:text-6xl">{{ $site->groom_name }}</span>
        </p>
        @include('sites.partials.divider-css', ['class' => 'mt-8'])
        <p class="nk-body mt-6 text-lg tracking-[0.12em]">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
        @if ($site->startsAtLabel())<p class="nk-muted mt-1 text-sm">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) &ndash; {{ $site->endsAtLabel() }}@endif</p>@endif
        <div class="mt-9">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
    </div>
</section>
