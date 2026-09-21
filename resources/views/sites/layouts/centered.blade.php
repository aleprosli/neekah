{{-- The cover page of a printed kad: ornament in every corner, crest, names. --}}
<section class="relative flex min-h-[100svh] flex-col items-center justify-center px-8 py-16 text-center">
    @foreach (['top-left', 'top-right', 'bottom-left', 'bottom-right'] as $corner)
        @include('sites.ornaments.'.$ornament, ['position' => $corner])
    @endforeach
    <div class="relative flex flex-col items-center">
        @include('sites.partials.bismillah', ['template' => $template, 'class' => 'mb-6'])
        @if ($site->cover_image)
            <img data-card-cover src="{{ $site->coverUrl() }}" alt="" class="mb-8 size-40 rounded-full object-cover" style="box-shadow: 0 0 0 5px var(--nk-page), 0 0 0 6px var(--nk-accent)">
        @else
            <div class="mb-8">@include('sites.partials.monogram', ['site' => $site])</div>
        @endif
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        <h1 class="nk-script mt-6">
            <span class="nk-name block text-5xl sm:text-6xl">{{ $site->bride_name }}</span>
            <span class="nk-accent my-1 block text-4xl">&amp;</span>
            <span class="nk-name block text-5xl sm:text-6xl">{{ $site->groom_name }}</span>
        </h1>
        @include('sites.partials.divider-css', ['class' => 'my-8'])
        @include('sites.partials.date-block', ['site' => $site])
    </div>
</section>
