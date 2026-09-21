@props(['site'])

@if (filled($site->contacts))
    <section id="hubungi" data-reveal class="scroll-mt-8 py-12 text-center">
        @include('sites.partials.section-heading', ['eyebrow' => 'Untuk pertanyaan', 'title' => 'Hubungi'])
        <ul class="mt-8 flex flex-col gap-3">
            @foreach ($site->contacts as $contact)
                <li>
                    <a href="tel:{{ $contact['phone'] }}" class="nk-plaque flex items-center justify-between gap-4 px-5 py-3.5 text-left">
                        <span class="nk-name text-lg">{{ $contact['name'] }}</span>
                        <span class="nk-muted text-sm tabular-nums">{{ $contact['phone'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
