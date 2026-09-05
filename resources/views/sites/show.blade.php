@php $preview = $preview ?? false; $sample = $sample ?? false; @endphp

<x-layouts.site :site="$site" :preview="$preview" :sample="$sample">
    @include('sites.templates.'.$site->template, ['site' => $site, 'preview' => $preview])
</x-layouts.site>
