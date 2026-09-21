@php $seo = app(App\Support\Seo::class); @endphp

<title>{{ $seo->resolvedTitle() }}</title>
<meta name="description" content="{{ $seo->resolvedDescription() }}">
<link rel="canonical" href="{{ $seo->resolvedCanonical() }}">

{{-- Each language is its own page, and each says where the other one is.
     Without this pair Google reads two near-identical sites and picks one. --}}
@if ($seo->isIndexable() && ($alternates = App\Support\Locales::alternates()))
    @foreach ($alternates as $code => $href)
        <link rel="alternate" hreflang="{{ App\Support\Locales::hreflang($code) }}" href="{{ $href }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $alternates[App\Support\Locales::DEFAULT] ?? reset($alternates) }}">
@endif

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
<meta property="og:locale" content="{{ str_replace('-', '_', App\Support\Locales::hreflang(App\Support\Locales::current())) }}">
@if ($seo->publishedTime())
    <meta property="article:published_time" content="{{ $seo->publishedTime() }}">
@endif
@if ($seo->modifiedTime())
    <meta property="article:modified_time" content="{{ $seo->modifiedTime() }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->resolvedTitle() }}">
<meta name="twitter:description" content="{{ $seo->resolvedDescription() }}">
<meta name="twitter:image" content="{{ $seo->resolvedImage() }}">
@if ($twitter = app(App\Support\SeoSettings::class)->twitter())
    <meta name="twitter:site" content="{{ $twitter }}">
@endif

@if ($jsonLd = $seo->jsonLd())
    {{-- Encoded by Seo::jsonLd() with JSON_HEX_TAG, so it cannot break out of the tag. --}}
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endif
