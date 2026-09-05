@php $preview = $preview ?? false; $sample = $sample ?? false; $guest = $guest ?? null; $template = $template ?? $site->design(); @endphp

<x-layouts.site :site="$site" :preview="$preview" :sample="$sample" :template="$template">
    @include('sites.partials.card', ['site' => $site, 'template' => $template, 'preview' => $preview, 'guest' => $guest])
</x-layouts.site>
