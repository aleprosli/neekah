@php $preview = $preview ?? false; $sample = $sample ?? false; $template = $template ?? $site->design(); @endphp

<x-layouts.site :site="$site" :preview="$preview" :sample="$sample" :template="$template">
    @include('sites.partials.card', ['site' => $site, 'template' => $template, 'preview' => $preview])
</x-layouts.site>
