@php
    $preview = $preview ?? false;
    $sample = $sample ?? false;
    $guest = $guest ?? null;
    $template = $template ?? $site->design();
@endphp

<x-layouts.site :site="$site" :preview="$preview" :sample="$sample" :template="$template">
    {{-- resources/js/components/card/CardView.vue draws the card. What is inside
         the mount element is what a link preview, a crawler and a visitor without
         JavaScript get: the invitation in plain words, which is the one thing that
         must never depend on a script. --}}
    <div data-vue="card-view" data-props="@vueProps($props)">
        <div class="nkc-fallback mx-auto max-w-md px-6 py-16 text-center">
            <p class="text-xs tracking-[0.3em] uppercase">{{ __('pages.card.walimatulurus') }}</p>
            <h1 class="mt-4 font-display text-4xl">{{ $site->groom_name }} &amp; {{ $site->bride_name }}</h1>

            @if ($site->event_date)
                <p class="mt-4 text-lg">{{ $site->event_date->translatedFormat('l, j F Y') }}</p>
            @endif
            @if ($site->startsAtLabel())
                <p class="text-sm">{{ $site->startsAtLabel() }}{{ $site->endsAtLabel() ? ' – '.$site->endsAtLabel() : '' }}</p>
            @endif

            @if ($site->venue_name)
                <p class="mt-6 font-medium">{{ $site->venue_name }}</p>
            @endif
            @if ($site->venue_address)
                <p class="text-sm whitespace-pre-line">{{ $site->venue_address }}</p>
            @endif

            @if ($site->invitation_note)
                <p class="mt-6 text-sm leading-relaxed">{{ $site->invitation_note }}</p>
            @endif

            @if (filled($site->itinerary))
                <dl class="mx-auto mt-8 max-w-xs text-left text-sm">
                    @foreach ($site->itinerary as $row)
                        <div class="flex justify-between gap-4 border-b py-2">
                            <dt>{{ $row['time'] ?? '' }}</dt>
                            <dd>{{ $row['label'] ?? '' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if (filled($site->contacts))
                <ul class="mt-6 text-sm">
                    @foreach ($site->contacts as $contact)
                        <li>{{ $contact['name'] ?? '' }} — {{ $contact['phone'] ?? '' }}</li>
                    @endforeach
                </ul>
            @endif

            <noscript>
                <p class="mt-8 text-xs">{{ __('card.no_javascript') }}</p>
            </noscript>
        </div>
    </div>
</x-layouts.site>
