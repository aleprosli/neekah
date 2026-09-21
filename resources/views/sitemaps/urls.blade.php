<?= '<?xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if (! empty($url['lastmod']))<lastmod>{{ $url['lastmod'] }}</lastmod>@endif
        @foreach ($url['alternates'] ?? [] as $code => $href)
        <xhtml:link rel="alternate" hreflang="{{ \App\Support\Locales::hreflang($code) }}" href="{{ $href }}"/>
        @if ($code === \App\Support\Locales::DEFAULT)<xhtml:link rel="alternate" hreflang="x-default" href="{{ $href }}"/>@endif
        @endforeach
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
    </url>
@endforeach
</urlset>
