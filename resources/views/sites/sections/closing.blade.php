@props(['site'])

<section data-reveal class="pt-12 pb-6 text-center">
    @include('sites.partials.divider-css')
    @if ($site->closing_note)<p class="nk-body mt-8 leading-relaxed italic">{{ $site->closing_note }}</p>@endif
    <div class="mt-10">@include('sites.partials.monogram', ['site' => $site, 'size' => 'size-16 text-2xl'])</div>
    <p class="nk-script nk-name mt-6 text-4xl">{{ $site->bride_name }} <span class="nk-accent">&amp;</span> {{ $site->groom_name }}</p>
</section>
