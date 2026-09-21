@props(['site'])

<section data-reveal class="py-12 text-center">
    @include('sites.partials.section-heading', ['eyebrow' => 'Menanti hari bahagia', 'title' => 'Menghitung Hari'])
    <div class="mt-8">@include('sites.partials.countdown', ['site' => $site])</div>
</section>
