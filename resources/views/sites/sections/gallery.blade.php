@props(['site'])

@if ($site->relationLoaded('photos') ? $site->photos->isNotEmpty() : $site->photos()->exists())
    <section id="galeri" data-reveal class="scroll-mt-8 py-12 text-center">
        @include('sites.partials.section-heading', ['eyebrow' => 'Galeri', 'title' => 'Kenangan'])
        <div class="mt-8 grid grid-cols-2 gap-2">
            @foreach ($site->photos as $photo)
                <figure @class(['overflow-hidden', 'col-span-2' => $loop->first && $site->photos->count() % 2 === 1])>
                    <img src="{{ \App\Actions\StoreOptimizedImage::thumbnailUrl($photo->path) }}" alt="{{ $photo->caption ?? 'Gambar pengantin' }}" loading="lazy" decoding="async" @class(['w-full object-cover', 'h-56' => $loop->first && $site->photos->count() % 2 === 1, 'h-40 sm:h-48' => ! ($loop->first && $site->photos->count() % 2 === 1)])>
                    @if ($photo->caption)<figcaption class="nk-muted mt-1.5 text-xs">{{ $photo->caption }}</figcaption>@endif
                </figure>
            @endforeach
        </div>
    </section>
@endif
