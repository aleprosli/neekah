{{-- A spread of photographs like the first page of an album. --}}
@php $photos = ($site->relationLoaded('photos') || $site->exists) ? $site->photos->take(3) : collect(); @endphp
<section class="relative flex min-h-[100svh] flex-col justify-center px-8 py-16">
    <div class="mx-auto w-full">
        <div class="grid h-80 grid-cols-3 grid-rows-2 gap-2">
            @for ($tile = 0; $tile < 3; $tile++)
                @php $path = $tile === 0 ? ($site->cover_image ?? $photos->get(0)?->path) : $photos->get($tile)?->path; @endphp
                <div @class(['relative overflow-hidden', 'col-span-2 row-span-2' => $tile === 0])>
                    @if ($path)
                        <img src="{{ Storage::disk('public')->url($path) }}" alt="" class="size-full object-cover">
                    @else
                        <div class="nk-photo-fallback flex size-full items-center justify-center">
                            @if ($tile === 0)@include('sites.partials.monogram', ['site' => $site])@endif
                        </div>
                    @endif
                </div>
            @endfor
        </div>
        <div class="mt-10 text-center">
            <p class="nk-eyebrow">{{ $eyebrow }}</p>
            <h1 class="nk-script nk-name mt-4 text-5xl">{{ $site->bride_name }} <span class="nk-accent">&amp;</span> {{ $site->groom_name }}</h1>
            <div class="nk-hairline mx-auto my-7 w-16 border-t"></div>
            @include('sites.partials.date-block', ['site' => $site])
        </div>
    </div>
</section>
