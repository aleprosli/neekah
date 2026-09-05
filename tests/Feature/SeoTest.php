<?php

use App\Actions\RenderInvitationPreview;
use App\Models\Category;
use App\Models\SiteTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WeddingSite;
use App\Support\Seo;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SiteTemplateSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('describes the marketplace with a canonical address and social tags', function () {
    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('vendors.index').'">', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee('Cari vendor perkahwinan di Malaysia');
});

it('folds the filters that only reorder the same vendors onto one address', function () {
    // Category and state make a genuinely different listing. Sort, price and
    // rating return the same vendors in a different order, so they must not
    // spawn a dozen pages competing for the same content.
    $response = $this->get(route('vendors.index', [
        'category' => 'photography',
        'state' => 'Kedah',
        'sort' => 'rating',
        'min_price' => 500,
        'page' => 2,
    ]))->assertOk();

    $canonical = route('vendors.index').'?category=photography&state=Kedah&page=2';

    $response->assertSee('<link rel="canonical" href="'.e($canonical).'">', false)
        ->assertSee('Vendor Photography di Kedah');
});

it('keeps keyword searches out of the index', function () {
    $this->get(route('vendors.index', ['q' => 'photographer murah']))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false);

    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertDontSee('name="robots" content="noindex', false);
});

it('gives a vendor profile its own title, description and canonical', function () {
    $vendor = Vendor::factory()->for(Category::where('slug', 'photography')->sole())->create([
        'name' => 'Studio Cahaya',
        'city' => 'Alor Setar',
        'tagline' => 'Gambar candid untuk majlis Melayu.',
    ]);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee('<title>Studio Cahaya — Photography di Alor Setar · Neekah</title>', false)
        ->assertSee('Gambar candid untuk majlis Melayu.')
        ->assertSee('<link rel="canonical" href="'.route('vendors.show', $vendor).'">', false);
});

it('never lets a title or description run past the length search engines show', function () {
    $vendor = Vendor::factory()->for(Category::first())->create([
        'name' => str_repeat('Panjang ', 12),
        'tagline' => str_repeat('Ayat yang sangat panjang sekali. ', 12),
    ]);

    $response = $this->get(route('vendors.show', $vendor))->assertOk();
    $html = $response->getContent();

    preg_match('/<title>(.*?)<\/title>/', $html, $title);
    preg_match('/<meta name="description" content="(.*?)">/', $html, $description);

    expect(mb_strlen(html_entity_decode($title[1])))->toBeLessThanOrEqual(Seo::TITLE_LIMIT)
        ->and(mb_strlen(html_entity_decode($description[1])))->toBeLessThanOrEqual(Seo::DESCRIPTION_LIMIT);
});

it('keeps a couple invitation out of search results and off our brand', function () {
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create(['bride_name' => 'Aina', 'groom_name' => 'Hakim']);

    $response = $this->get('http://'.$site->subdomain.'.'.config('neekah.site_domain').'/')->assertOk();

    // A card carries a venue, contact phone numbers and guests' messages.
    $response->assertSee('name="robots" content="noindex, nofollow"', false)
        // Shared to WhatsApp it should preview under their names, not ours.
        ->assertSee('<title>Aina &amp; Hakim</title>', false)
        ->assertSee('property="og:title" content="Aina &amp; Hakim"', false);
});

it('keeps the signed in areas and the sign in screens out of the index', function () {
    $this->get(route('login'))->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);
    $this->get(route('vendors.compare'))->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false);
});

it('declares the language the pages are actually written in', function () {
    $this->get(route('vendors.index'))->assertOk()->assertSee('<html lang="ms"', false);
});

it('serves a sitemap index pointing at one file per kind of page', function () {
    $this->get(route('sitemap.index'))
        ->assertOk()
        ->assertHeader('content-type', 'application/xml; charset=utf-8')
        ->assertSee(route('sitemap.pages'))
        ->assertSee(route('sitemap.vendors'))
        ->assertSee(route('sitemap.templates'));
});

it('lists approved vendors in the sitemap and leaves the rest out', function () {
    $live = Vendor::factory()->for(Category::first())->create(['name' => 'Studio Hadir']);
    $waiting = Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Studio Menunggu']);

    $this->get(route('sitemap.vendors'))
        ->assertOk()
        ->assertSee(route('vendors.show', $live))
        ->assertDontSee(route('vendors.show', $waiting));
});

it('puts every category listing in the sitemap, since each is a canonical page', function () {
    $this->get(route('sitemap.pages'))
        ->assertOk()
        ->assertSee(e(route('vendors.index', ['category' => 'photography'])), false)
        ->assertSee(route('landing'))
        ->assertSee(route('sites.templates'));
});

it('lists the invitation templates but never a couple own card', function () {
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create();

    $this->get(route('sitemap.templates'))
        ->assertOk()
        ->assertSee(route('sites.templates.show', SiteTemplate::active()->first()))
        ->assertDontSee($site->subdomain);
});

it('ships a robots file that keeps searches and token links out', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Disallow: /compare')
        ->toContain('Disallow: /invitations/')
        // Cards must stay crawlable or Googlebot never reads the noindex on them.
        ->toContain('Allow: /');
});

it('stamps this deployment own sitemap address into robots.txt', function () {
    $original = file_get_contents(public_path('robots.txt'));

    try {
        $this->artisan('neekah:robots')->assertSuccessful();

        expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap: '.route('sitemap.index'));
    } finally {
        file_put_contents(public_path('robots.txt'), $original);
    }
});

it('refuses to serve the app sitemap from a card subdomain', function () {
    $this->get('http://aina.'.config('neekah.site_domain').'/sitemap.xml')->assertNotFound();
});

it('previews a shared card as the couple own design, not the neekah logo', function () {
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create([
        'template' => 'malam-emas',
        'bride_name' => 'Aina',
        'groom_name' => 'Hakim',
    ]);

    $this->get('http://'.$site->subdomain.'.'.config('neekah.site_domain').'/')
        ->assertOk()
        ->assertSee('property="og:image" content="'.e(route('sites.preview-image', ['subdomain' => $site->subdomain])).'"', false)
        ->assertSee('property="og:image:width" content="1200"', false)
        ->assertSee('property="og:image:height" content="630"', false)
        ->assertDontSee('neekah-og.png');
});

it('draws that preview at the size whatsapp and facebook render', function () {
    Storage::fake('public');
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create(['template' => 'seri-gangsa']);

    $response = $this->get('http://'.$site->subdomain.'.'.config('neekah.site_domain').'/preview.png')
        ->assertOk()
        ->assertHeader('content-type', 'image/png');

    $image = imagecreatefromstring($response->getContent());

    expect(imagesx($image))->toBe(1200)
        ->and(imagesy($image))->toBe(630);
});

it('paints the preview in the palette of the template the couple chose', function () {
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create(['bride_name' => 'Aina', 'groom_name' => 'Hakim']);
    $render = app(RenderInvitationPreview::class);

    $corner = function (string $slug) use ($site, $render): array {
        $site->template = $slug;
        $image = imagecreatefromstring($render->draw($site, SiteTemplate::where('slug', $slug)->sole()));
        $colour = imagecolorat($image, 5, 5);

        return [($colour >> 16) & 0xFF, ($colour >> 8) & 0xFF, $colour & 0xFF];
    };

    // A light template and a dark one must not come out the same picture.
    expect($corner('seri-gangsa'))->not->toBe($corner('malam-emas'));
});

it('redraws the preview when the couple edits the card', function () {
    Storage::fake('public');
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create(['bride_name' => 'Aina']);
    $render = app(RenderInvitationPreview::class);

    $before = $render->handle($site, $site->design());

    $site->update(['bride_name' => 'Aina Sofea']);
    $after = $render->handle($site->fresh(), $site->design());

    expect($after)->not->toBe($before);
    Storage::disk('public')->assertExists($after);
});

it('gives each template gallery page a preview of that design', function () {
    $this->seed(SiteTemplateSeeder::class);
    $template = SiteTemplate::where('slug', 'mawar-pagi')->sole();

    $this->get(route('sites.templates.show', $template))
        ->assertOk()
        ->assertSee(e(route('sites.templates.image', $template)), false);

    $this->get(route('sites.templates.image', $template))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');
});

it('refuses a preview for an unpublished card', function () {
    $this->seed(SiteTemplateSeeder::class);
    $draft = WeddingSite::factory()->create(['subdomain' => 'belum-siar']);

    $this->get('http://belum-siar.'.config('neekah.site_domain').'/preview.png')->assertNotFound();
});
