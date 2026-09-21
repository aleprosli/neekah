<?php

use App\Models\SiteTemplate;
use App\Models\Vendor;
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

it('translates the marketplace, not just the navigation', function () {
    Vendor::factory()->count(3)->create();

    $this->get('/')->assertOk()
        ->assertSee('Cari vendor majlis anda')
        ->assertSee('Semua filter');

    $this->get('/en')->assertOk()
        ->assertSee('Find your wedding vendors')
        ->assertSee('All filters')
        ->assertDontSee('Semua filter');
});

it('counts vendors correctly in a language with no plural form', function () {
    Vendor::factory()->count(3)->create();

    // Laravel has no pluralisation rule for Malay, so a choice string would
    // fall to its first branch whatever the number was — which read
    // "Tiada vendor" on a page listing three of them.
    $this->get('/')->assertOk()->assertSee('3 vendor')->assertDontSee('Tiada vendor');
    $this->get('/en')->assertOk()->assertSee('3 vendors');
});

it('offers the other language from the navigation on every width', function () {
    $page = $this->get('/')->assertOk();

    // A phone hid the switcher entirely at first.
    $page->assertSee('hreflang="en-MY"', false)->assertSee('>EN</a>', false);
    expect($page->getContent())->not->toContain('language-switcher class="mr-1 hidden');
});

it('gives every shell a way to change language, not just the public pages', function () {
    // The switcher lived only in the public header, so login, the dashboards
    // and the admin panel had no way to change language at all.
    $this->get('/login')->assertOk()->assertSee('>EN</a>', false);
    $this->get('/register')->assertOk()->assertSee('>EN</a>', false);
    $this->get('/')->assertOk()->assertSee('>EN</a>', false);
});

it('translates the login page all the way through, blade and vue alike', function () {
    $malay = $this->get('/login?as=pengantin')->assertOk();
    $malay->assertSee('Kata laluan')->assertSee('Ingat saya');

    $english = $this->get('/en/login?as=pengantin')->assertOk();
    $english->assertSee('Password')
        ->assertSee('Welcome back. Manage your wedding and your bookings.')
        ->assertDontSee('Kata laluan')
        ->assertDontSee('Ingat saya');
});

it('ships the browser its own dictionary, in the language of the page', function () {
    $read = function (string $path): array {
        $html = $this->get($path)->assertOk()->getContent();
        preg_match('/id="translations">(.*?)<\/script>/s', $html, $m);

        return json_decode(html_entity_decode($m[1] ?? '{}'), true) ?? [];
    };

    // Vue islands cannot call __(), so the page carries the strings instead.
    expect($read('/login')['auth']['remember_me'])->toBe('Ingat saya')
        ->and($read('/en/login')['auth']['remember_me'])->toBe('Remember me');
});

it('changes language with a whole page load, not a region swap', function () {
    // navigation.js swaps <main> and the nav regions and keeps everything
    // else. Changing language changes the lang attribute, the dictionary the
    // browser was handed for Vue, the sidebar and the header — so the URL
    // changed while the page around it stayed in the old language until it
    // was refreshed by hand.
    $this->get('/')->assertOk()->assertSee('data-no-swap', false);
});

it('translates the vendor sign-up form, which is all Vue', function () {
    $this->get('/vendor/register')->assertOk()
        ->assertSee('Sertai Neekah sebagai vendor');

    $english = $this->get('/en/vendor/register')->assertOk();

    $english->assertSee('Join Neekah as a vendor')->assertDontSee('Sertai Neekah sebagai vendor');

    // The fields themselves live in Vue and read the shipped dictionary.
    preg_match('/id="translations">(.*?)<\/script>/s', $english->getContent(), $m);
    $dictionary = json_decode(html_entity_decode($m[1] ?? '{}'), true);

    expect($dictionary['vendor_signup']['business_name'])->toBe('Business name')
        ->and($dictionary['vendor_signup']['owner_heading'])->toBe('Owner account');
});

it('gives a page only the strings its own islands read', function () {
    $dictionary = function (string $path): array {
        preg_match('/id="translations">(.*?)<\/script>/s', $this->get($path)->getContent(), $m);

        return json_decode(html_entity_decode($m[1] ?? '{}'), true) ?? [];
    };

    // A wedding guest opening a couple's card was handed nine kilobytes of the
    // couple's own editor strings — every label in the guest list, the budget
    // and the booking screens — on a page that mounts no Vue at all.
    $template = SiteTemplate::factory()->create();

    expect($dictionary(route('sites.templates.show', $template)))->toBe([])
        ->and(array_keys($dictionary('/login')))->toContain('auth')
        ->and(array_keys($dictionary('/login')))->not->toContain('guests');
});
