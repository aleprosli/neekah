@php $seo = app(App\Support\Seo::class); @endphp

<title>{{ $seo->resolvedTitle() }}</title>
<meta name="description" content="{{ $seo->resolvedDescription() }}">
<link rel="canonical" href="{{ $seo->resolvedCanonical() }}">

@unless ($seo->isIndexable())
    {{-- Private, token-gated or a search result. None of it is ours to publish. --}}
    <meta name="robots" content="noindex, nofollow">
@endunless

<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:type" content="{{ $seo->resolvedType() }}">
<meta property="og:title" content="{{ $seo->resolvedTitle() }}">
<meta property="og:description" content="{{ $seo->resolvedDescription() }}">
<meta property="og:url" content="{{ $seo->resolvedCanonical() }}">
<meta property="og:image" content="{{ $seo->resolvedImage() }}">
<meta property="og:image:alt" content="{{ $seo->resolvedTitle() }}">
@if ($seo->imageWidth())
    <meta property="og:image:width" content="{{ $seo->imageWidth() }}">
    <meta property="og:image:height" content="{{ $seo->imageHeight() }}">
@endif
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}_MY">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->resolvedTitle() }}">
<meta name="twitter:description" content="{{ $seo->resolvedDescription() }}">
<meta name="twitter:image" content="{{ $seo->resolvedImage() }}">
@if (config('neekah.seo.twitter'))
    <meta name="twitter:site" content="{{ config('neekah.seo.twitter') }}">
@endif
