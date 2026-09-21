@props(['site'])

@if ($site->salutation || $site->bride_parents || $site->groom_parents || $site->invitation_note)
    <section data-reveal class="py-12 text-center">
        @include('sites.partials.divider-css')
        @if ($site->salutation)<p class="nk-body mt-8 text-lg leading-relaxed">{{ $site->salutation }}</p>@endif
        @if ($site->bride_parents || $site->groom_parents)
            <div class="mt-8 flex flex-col gap-2">
                @if ($site->bride_parents)<p class="nk-name text-xl leading-snug font-medium">{{ $site->bride_parents }}</p>@endif
                @if ($site->bride_parents && $site->groom_parents)<p class="nk-script nk-accent text-3xl">&amp;</p>@endif
                @if ($site->groom_parents)<p class="nk-name text-xl leading-snug font-medium">{{ $site->groom_parents }}</p>@endif
            </div>
        @endif
        @if ($site->invitation_note)<p class="nk-body mt-8 leading-relaxed">{{ $site->invitation_note }}</p>@endif
    </section>
@endif
