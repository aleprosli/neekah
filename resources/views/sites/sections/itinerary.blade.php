@props(['site'])

@if (filled($site->itinerary))
    <section id="atur-cara" data-reveal class="scroll-mt-8 py-12 text-center">
        @include('sites.partials.section-heading', ['eyebrow' => 'Aturcara', 'title' => 'Atur Cara Majlis'])
        <ol class="mt-8 flex flex-col items-center gap-5">
            @foreach ($site->itinerary as $row)
                <li class="flex flex-col items-center">
                    @unless ($loop->first)<span class="nk-dot mb-5 size-1.5 rotate-45" aria-hidden="true"></span>@endunless
                    <p class="nk-muted text-xs tracking-[0.25em] uppercase">{{ $row['time'] }}</p>
                    <p class="nk-name mt-1 text-xl">{{ $row['label'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>
@endif
