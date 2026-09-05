{{-- Photograph above, details below, divided by a hairline. --}}
<section class="relative flex min-h-[92vh] flex-col justify-center px-6 py-14">
    @include('sites.ornaments.'.$ornament, ['position' => 'bottom-left'])
    <div class="relative mx-auto w-full max-w-sm">
        <div class="overflow-hidden rounded-2xl" style="border: 1px solid var(--nk-line)">
            @if ($site->cover_image)
                <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="aspect-[4/3] w-full object-cover">
            @else
                <div class="aspect-[4/3] w-full" style="background: var(--nk-panel)"></div>
            @endif
        </div>
        <div class="mt-8">
            <p class="nk-eyebrow">{{ $eyebrow }}</p>
            <p class="nk-script nk-name mt-4 text-5xl">{{ $site->bride_name }}</p>
            <p class="nk-accent text-2xl">&amp;</p>
            <p class="nk-script nk-name text-5xl">{{ $site->groom_name }}</p>
            <div class="nk-hairline my-7 border-t"></div>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="nk-muted text-[11px] tracking-[0.2em] uppercase">Tarikh</dt><dd class="nk-body mt-1">{{ $site->event_date->translatedFormat('j F Y') }}</dd></div>
                <div><dt class="nk-muted text-[11px] tracking-[0.2em] uppercase">Masa</dt><dd class="nk-body mt-1">{{ $site->startsAtLabel() ?? 'Akan diumumkan' }}</dd></div>
            </dl>
            <div class="mt-8">@include('sites.partials.quick-nav', ['site' => $site, 'preview' => $preview])</div>
        </div>
    </div>
</section>
