@props(['site'])

@if ($site->wishes_enabled && $site->approvedWishes()->exists())
    <section id="ucapan" data-reveal class="scroll-mt-8 py-12 text-center">
        @include('sites.partials.section-heading', ['eyebrow' => 'Ucapan', 'title' => 'Doa & Restu'])
        <ul class="mt-8 flex flex-col gap-6">
            @foreach ($site->approvedWishes as $wish)
                <li>
                    <p class="nk-body leading-relaxed italic">&ldquo;{{ $wish->message }}&rdquo;</p>
                    <p class="nk-muted mt-2 text-xs tracking-[0.2em] uppercase">{{ $wish->name }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endif
