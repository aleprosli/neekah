<?= '<?xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($sitemaps as $sitemap)
    <sitemap>
        <loc>{{ $sitemap['loc'] }}</loc>
        @if ($sitemap['lastmod'])
            <lastmod>{{ \Illuminate\Support\Carbon::parse($sitemap['lastmod'])->toAtomString() }}</lastmod>
        @endif
    </sitemap>
@endforeach
</sitemapindex>
