{{-- A panel inside the sheet with ornament brackets, like a card's inner leaf. --}}
<section class="relative flex min-h-[100svh] items-center justify-center px-8 py-16">
    <div class="nk-plaque relative w-full overflow-hidden px-6 py-14 text-center">
        @foreach (['top-left', 'bottom-right'] as $corner)
            @include('sites.ornaments.'.$ornament, ['position' => $corner])
        @endforeach
        <div class="relative flex flex-col items-center">
            @include('sites.partials.bismillah', ['template' => $template, 'class' => 'mb-5'])
            <p class="nk-eyebrow">{{ $eyebrow }}</p>
            @if ($site->cover_image)
                <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="my-7 aspect-[4/3] w-full object-cover">
            @endif
            <h1 class="nk-script mt-7">
                <span class="nk-name block text-5xl">{{ $site->bride_name }}</span>
                <span class="nk-accent block text-3xl">&amp;</span>
                <span class="nk-name block text-5xl">{{ $site->groom_name }}</span>
            </h1>
            @include('sites.partials.divider-css', ['class' => 'my-7'])
            @include('sites.partials.date-block', ['site' => $site])
        </div>
    </div>
</section>
