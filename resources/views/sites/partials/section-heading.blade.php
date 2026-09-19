@props(['eyebrow', 'title'])
<div class="text-center">
    <p class="nk-eyebrow">{{ $eyebrow }}</p>
    <h2 class="nk-heading mt-2 text-4xl">{{ $title }}</h2>
    @include('sites.partials.divider-css', ['class' => 'mt-3'])
</div>
