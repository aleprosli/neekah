@props(['site', 'preview' => false, 'guest' => null])

@if ($site->acceptsRsvps())
    <section id="rsvp" data-reveal class="scroll-mt-8 py-12 text-center">
        @include('sites.partials.section-heading', ['eyebrow' => 'RSVP', 'title' => 'Kehadiran'])
        <p class="nk-body mt-5 mb-7 text-sm">Sahkan kehadiran anda &mdash; maklum balas anda memudahkan kami menyediakan jamuan.</p>
        <div class="nk-plaque px-5 py-7">
            @include('sites.partials.rsvp', ['site' => $site, 'preview' => $preview, 'guest' => $guest])
        </div>
    </section>
@endif
