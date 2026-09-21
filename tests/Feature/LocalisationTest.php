<?php

use App\Support\Locales;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('serves malay at the root and english under its own prefix', function () {
    $this->get('/')->assertOk();
    $this->get('/en')->assertOk();
});

it('leaves every malay url exactly where google already found it', function (string $path) {
    // Moving these would throw away everything indexed so far.
    $this->get($path)->assertOk();
})->with(['/', '/about', '/compare', '/blog', '/kad-jemputan']);

it('sets the language from the url, not from anything the visitor carries', function () {
    $this->get('/')->assertOk();
    expect(app()->getLocale())->toBe('ms');

    $this->get('/en/about')->assertOk();
    expect(app()->getLocale())->toBe('en');
});

it('refuses a prefix that is not a language', function () {
    $this->get('/de/about')->assertNotFound();
});

it('keeps route() answering in the language being served', function () {
    app()->setLocale('ms');
    expect(route('vendors.index', absolute: false))->toBe('/')
        ->and(route('blog.index', absolute: false))->toBe('/blog');

    app()->setLocale('en');
    expect(route('vendors.index', absolute: false))->toBe('/en')
        ->and(route('blog.index', absolute: false))->toBe('/en/blog');
});

it('answers about the route regardless of the language it is in', function () {
    // Every nav highlight and guard was written against these names before the
    // site had two languages, and none of them should have had to change.
    $this->get('/')->assertOk();
    expect(Locales::routeIs('vendors.*'))->toBeTrue();

    $this->get('/en')->assertOk();
    expect(Locales::routeIs('vendors.*'))->toBeTrue()
        ->and(Locales::routeIs('blog.*'))->toBeFalse()
        ->and(Locales::routeIs(['blog.*', 'vendors.index']))->toBeTrue();
});

it('names the same page in the other language, for the switcher and hreflang', function () {
    $this->get('/blog')->assertOk();

    expect(URL::routeIn('en', 'blog.index'))->toEndWith('/en/blog')
        ->and(URL::routeIn('ms', 'blog.index'))->toEndWith('/blog');
});

it('keeps the sitemap at the root, where a crawler looks for it', function () {
    $this->get('/sitemap.xml')->assertOk();
    // One sitemap for the site, not one per language.
    $this->get('/en/sitemap.xml')->assertNotFound();
});
