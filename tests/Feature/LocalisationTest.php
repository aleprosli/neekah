<?php

use App\Models\Category;
use App\Models\ChecklistItem;
use App\Models\Enquiry;
use App\Models\Setting;
use App\Models\SiteTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingSite;
use App\Notifications\EnquiryReceived;
use App\Support\Locales;
use App\Support\StoredNotification;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\SiteTemplateSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
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

it('serves admin-written data in the language of the page', function () {
    // Categories are added by admins, so they cannot live in a language file:
    // a category added tomorrow would have nowhere to be translated.
    $category = Category::where('slug', 'invitation')->sole();

    app()->setLocale('ms');
    expect($category->name)->toBe('Invitation');

    app()->setLocale('en');
    expect($category->name)->toBe('Invitations');
});

it('falls back rather than showing a blank where a translation is missing', function () {
    $category = Category::where('slug', 'catering')->sole();
    $category->name = ['en' => null];
    $category->save();

    // An admin who has not filled in the English name yet should not take the
    // page down with them.
    app()->setLocale('en');
    expect($category->fresh()->name)->toBe('Catering');
});

it('keeps one language when the other is written', function () {
    $category = Category::where('slug', 'venue')->sole();

    $category->name = ['en' => 'Venues'];
    $category->save();

    app()->setLocale('ms');
    expect($category->fresh()->name)->toBe('Venue');
    app()->setLocale('en');
    expect($category->fresh()->name)->toBe('Venues');
});

it('finds a row by its text in whichever language the caller knows', function () {
    // where('name', ...) compares against the whole JSON document now.
    expect(Category::whereTranslated('name', 'Invitations')->sole()->slug)->toBe('invitation')
        ->and(Category::whereTranslated('name', 'Invitation')->sole()->slug)->toBe('invitation');
});

it('keeps the template style in the url while translating what is shown', function () {
    $this->seed(SiteTemplateSeeder::class);

    // The category is a closed set of six and it is in the gallery's URLs, so
    // translating the value itself would move every one of those URLs.
    $this->get('/en/kad-jemputan')->assertOk()
        ->assertSee('Traditional')
        ->assertSee('?category=Traditional', false);

    $this->get('/kad-jemputan')->assertOk()->assertSee('Tradisional');
});

it('translates what a designer wrote about a template, not its name', function () {
    $this->seed(SiteTemplateSeeder::class);

    $template = SiteTemplate::where('slug', 'rose-garden')->sole();

    // "Rose Garden" is the name of a design, the way a paint colour has a name.
    app()->setLocale('en');
    expect($template->name)->toBe('Rose Garden')
        ->and($template->description)->toBe('Dusty pink roses in sprays and clusters on ivory, softly romantic.');

    app()->setLocale('ms');
    expect($template->description)->toBe('Mawar merah jambu lembut dalam jambangan di atas gading, romantis dan tenang.');
});

it('adds the language a checklist row is missing without duplicating or overwriting it', function () {
    $this->seed(ChecklistSeeder::class);

    $before = ChecklistItem::count();
    $item = ChecklistItem::whereTranslated('title', 'Tempah katering')->sole();

    // An admin has rewritten the English; a seeder has no business undoing it.
    $item->title = ['en' => 'Book catering (edited)'];
    $item->save();

    $this->seed(ChecklistSeeder::class);

    expect(ChecklistItem::count())->toBe($before);

    app()->setLocale('en');
    expect($item->fresh()->title)->toBe('Book catering (edited)');

    app()->setLocale('ms');
    expect($item->fresh()->title)->toBe('Tempah katering');
});

it('writes to a person in the language they were last reading the site in', function () {
    $this->seed(CategorySeeder::class);
    Notification::fake();

    $vendor = Vendor::factory()->create();
    $enquiry = Enquiry::factory()->for($vendor)->create();

    // A page's language is its URL; an email has no URL and one reader, so it
    // follows the language they were last browsing in.
    $vendor->user->forceFill(['locale' => 'en'])->save();

    expect($vendor->user->preferredLocale())->toBe('en');

    $vendor->user->notify(new EnquiryReceived($enquiry));

    Notification::assertSentTo($vendor->user, EnquiryReceived::class);
});

it('records the language a signed-in person is browsing in', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get('/en')->assertOk();
    expect($user->fresh()->locale)->toBe('en');

    $this->actingAs($user)->get('/')->assertOk();
    expect($user->fresh()->locale)->toBe('ms');
});

it('shows a stored notification in the language it is read in', function () {
    $data = [
        'icon' => '💬',
        'title_key' => 'notifications.enquiry_received.title',
        'title_params' => ['name' => 'Aina'],
        'body_key' => 'notifications.enquiry_received.body',
        'url' => '/',
    ];

    app()->setLocale('ms');
    expect(StoredNotification::render($data)['title'])->toBe('Enquiry baharu daripada Aina');

    app()->setLocale('en');
    expect(StoredNotification::render($data)['title'])->toBe('New enquiry from Aina');
});

it('still shows a notification written before the rows held keys', function () {
    // Rows already in the database hold a finished sentence. They were true
    // when they were written and there is nothing to look up.
    $legacy = ['icon' => '🔔', 'title' => 'Booking ABC dibuat', 'body' => 'Sila semak.', 'url' => '/'];

    app()->setLocale('en');
    expect(StoredNotification::render($legacy))->toMatchArray([
        'title' => 'Booking ABC dibuat',
        'body' => 'Sila semak.',
    ]);
});

it('leaves no Malay on the signed-in pages an English user sees', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(SiteTemplateSeeder::class);

    $couple = User::factory()->create(['locale' => 'en']);
    Wedding::factory()->for($couple)->create(['city' => 'Alor Setar', 'state' => 'Kedah']);

    // Controllers hand labels to Vue as props, and those were the last
    // category still hardcoded: the sidebar, the stat cards, the table empty
    // states and every page heading.
    foreach (['/en/dashboard', '/en/budget', '/en/tetamu', '/en/bookings'] as $url) {
        $html = $this->actingAs($couple)->get($url)->assertOk()->getContent();
        $body = preg_replace('/<script[^>]*>.*?<\/script>/s', ' ', $html);

        expect($body)
            ->not->toContain('Majlis saya')
            ->not->toContain('Senarai tetamu')
            ->not->toContain('Cari vendor');
    }

    $this->actingAs($couple)->get('/en/budget')->assertOk()
        ->assertSee('Wedding budget')->assertDontSee('Bajet majlis');

    $this->actingAs($couple)->get('/en/tetamu')->assertOk()
        ->assertSee('Guest list')->assertSee('Attending');
});

it('gives the English pages their own tagline and meta description', function () {
    Setting::put([
        'seo.tagline' => 'Semua Urusan Majlis, Satu Platform',
        'seo.description' => str_repeat('Cari vendor perkahwinan di Malaysia. ', 3),
        'seo.tagline_en' => 'Every Wedding Errand, One Platform',
        'seo.description_en' => str_repeat('Find wedding vendors in Malaysia. ', 3),
    ]);

    // The footer and the <title> are the last Malay left on a page Google
    // reads in English.
    $this->get('/en')->assertOk()
        ->assertSee('Every Wedding Errand, One Platform')
        ->assertDontSee('Semua Urusan Majlis, Satu Platform');

    $this->get('/')->assertOk()->assertSee('Semua Urusan Majlis, Satu Platform');
});

it('falls back to the Malay tagline when the admin has not written the English one', function () {
    Setting::put(['seo.tagline' => 'Semua Urusan Majlis, Satu Platform', 'seo.tagline_en' => '']);

    // Better the Malay words than an empty <title>.
    $this->get('/en')->assertOk()->assertSee('Semua Urusan Majlis, Satu Platform');
});

it('walks an English couple through creating their card in their own language', function () {
    $this->seed(SiteTemplateSeeder::class);

    $couple = User::factory()->create(['locale' => 'en']);
    Wedding::factory()->for($couple)->create();

    // The four-step guide builds its steps inside a PHP array, which is why it
    // stayed Malay long after the pages around it were translated.
    $this->actingAs($couple)->get('/en/dashboard')->assertOk()
        ->assertSee('Create your card in 4 steps')
        ->assertSee('Pick a template &amp; web address', false)
        ->assertDontSee('Pilih template')
        ->assertDontSee('langkah selesai');
});

it('walks a brand new English vendor through their onboarding in English', function () {
    // Every earlier sweep used a finished, approved vendor, so the onboarding
    // panel and the pending banner never rendered and stayed Malay.
    $vendor = Vendor::factory()->pending()->create([
        'tagline' => null,
        'description' => null,
        'cover_image' => null,
        'price_from' => 0,
    ]);
    $vendor->user->update(['locale' => 'en']);

    $html = $this->actingAs($vendor->user)->get('/en/vendor')->assertOk()->getContent();

    expect($html)
        ->toContain('couples cannot see your profile yet')
        ->not->toContain('belum dipaparkan kepada pengantin');

    expect(html_entity_decode($html))
        ->toContain('The first sentence couples read about you')
        ->not->toContain('Ayat pertama yang pengantin baca');
});

it('writes the flash messages after an action in the reader language', function () {
    // Flash messages only exist after a POST, so no page sweep could ever see
    // them: they were the last whole category still hardcoded in Malay.
    $couple = User::factory()->create(['locale' => 'en']);
    $wedding = Wedding::factory()->for($couple)->create();

    $this->actingAs($couple)
        ->post(route('en.weddings.timeline.store', $wedding), [
            'title' => 'Akad nikah',
            'starts_at' => '09:00',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'The activity has been added to the timeline.');

    app()->setLocale('ms');
    expect(__('flash.couple.timeline_added'))->toBe('Aktiviti ditambah ke timeline.');
});

it('names the person in a flash message in both languages', function () {
    $admin = User::factory()->admin()->create(['locale' => 'en']);
    $target = User::factory()->create(['name' => 'Nur Photography']);

    // The name is a parameter now, not a concatenation, so the sentence can be
    // reordered in a language that needs it.
    $this->actingAs($admin)->post(route('en.admin.users.impersonate', $target))
        ->assertSessionHas('status', 'You are now viewing Neekah as Nur Photography.');
});

it('keeps a wedding card Malay no matter what the last request was', function () {
    $this->seed(SiteTemplateSeeder::class);
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    WeddingSite::factory()->for($wedding)->create(['is_published' => true, 'subdomain' => 'aina-hakim']);

    $host = 'http://aina-hakim.'.config('neekah.site_domain');

    // Cards and sitemaps sit outside both language sets, so they used to take
    // whatever App::setLocale the previous request in this process left.
    $this->get('/en')->assertOk();

    $this->get($host)->assertOk()
        ->assertSee('<html lang="ms"', false)
        ->assertDontSee('<html lang="en"', false)
        ->assertSee('og:locale" content="ms_MY"', false);
});

it('does not change a signed-in reader preference from a page that has no language', function () {
    $this->seed(SiteTemplateSeeder::class);
    $couple = User::factory()->create(['locale' => 'en']);
    $wedding = Wedding::factory()->for($couple)->create();
    WeddingSite::factory()->for($wedding)->create(['is_published' => true, 'subdomain' => 'aina-hakim']);

    // Previewing their own card must not silently switch their emails to Malay.
    $this->actingAs($couple)->get('http://aina-hakim.'.config('neekah.site_domain'))->assertOk();

    expect($couple->fresh()->locale)->toBe('en');
});

it('writes a validation error entirely in one language', function () {
    $couple = User::factory()->create(['locale' => 'en']);

    // The message came from the framework in English while the field name came
    // from the FormRequest in Malay, so an English page said "The nama majlis
    // field is required."
    $this->actingAs($couple)->post(route('en.weddings.store'), [])
        ->assertSessionHasErrors(['title' => 'The wedding name field is required.']);

    // Written out: route() would itself come back in whatever language the
    // previous request left behind.
    $this->actingAs($couple)->post('/weddings', [])
        ->assertSessionHasErrors(['title' => 'Medan nama majlis wajib diisi.']);
});

it('writes every translation as plain text, since {{ }} and $t() print an entity as it is', function () {
    $withEntities = collect(Locales::codes())
        ->flatMap(fn (string $locale): array => glob(lang_path($locale.'/*.php')))
        // Laravel's own pagination views print these two with {!! !!}.
        ->reject(fn (string $file): bool => basename($file) === 'pagination.php')
        ->flatMap(fn (string $file): array => collect(Arr::dot(require $file))
            ->filter(fn (mixed $text): bool => is_string($text) && preg_match('/&(#\d+|#x[0-9a-f]+|[a-z]+);/i', $text) === 1)
            ->keys()
            ->map(fn (string $key): string => basename(dirname($file)).'/'.basename($file, '.php').'.'.$key)
            ->all())
        ->all();

    expect($withEntities)->toBe([]);
});
