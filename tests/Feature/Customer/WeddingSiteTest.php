<?php

use App\Enums\WeddingRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SiteTemplateSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->seed(SiteTemplateSeeder::class);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create(['title' => 'Aina & Hakim', 'city' => 'Alor Setar', 'state' => 'Kedah']);
});

it('offers a draft filled from the wedding project', function () {
    $props = $this->actingAs($this->aina)->get(route('site.edit'))->assertOk()->viewData('props');

    // A couple with no card yet still opens the editor with something to
    // preview, filled in from the wedding project.
    expect($props['exists'])->toBeFalse()
        ->and($props['site']['bride_name'])->toBe('Aina')
        ->and($props['site']['groom_name'])->toBe('Hakim')
        ->and($props['site']['subdomain'])->toBe('aina-hakim')
        ->and($props['status'])->toBeNull();
});

it('creates the invitation as a draft that is not public yet', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), siteData())
        ->assertRedirect(route('site.edit'));

    $site = WeddingSite::sole();

    expect($site->subdomain)->toBe('ainapilihhakim')
        ->and($site->template)->toBe('mawar-pagi')
        ->and($site->is_published)->toBeFalse()
        ->and($site->itinerary)->toHaveCount(2)
        ->and($site->contacts)->toHaveCount(1);

    // Not reachable until published.
    $this->get('http://ainapilihhakim.'.config('neekah.site_domain'))->assertNotFound();
});

it('offers only as many itinerary rows as the server will accept', function () {
    $props = $this->actingAs($this->aina)->get(route('site.edit'))->assertOk()->viewData('props');

    // The editor adds rows on demand, so its ceilings have to match the rules
    // in StoreWeddingSiteRequest or it would offer a row that is then rejected.
    expect($props['limits']['itinerary'])->toBe(12)
        ->and($props['limits']['contacts'])->toBe(6)
        ->and($props['limits']['gift_accounts'])->toBe(4);

    // The rules need a bound route to build, so the ceilings are read from the
    // request itself rather than by instantiating it here.
    $rules = file_get_contents(app_path('Http/Requests/StoreWeddingSiteRequest.php'));

    expect($rules)->toContain("'itinerary' => ['nullable', 'array', 'max:12']")
        ->toContain("'contacts' => ['nullable', 'array', 'max:6']")
        ->toContain("'gift_accounts' => ['nullable', 'array', 'max:4']");
});

it('publishes and unpublishes the invitation', function () {
    $site = WeddingSite::factory()->for($this->wedding)->create(['subdomain' => 'ainapilihhakim']);

    $this->actingAs($this->aina)
        ->put(route('weddings.site.publish', $this->wedding), ['published' => 1])
        ->assertRedirect();

    expect($site->fresh()->is_published)->toBeTrue();
    $this->get('http://ainapilihhakim.'.config('neekah.site_domain'))->assertOk();

    $this->actingAs($this->aina)->put(route('weddings.site.publish', $this->wedding), ['published' => 0]);

    expect($site->fresh()->is_published)->toBeFalse();
    $this->get('http://ainapilihhakim.'.config('neekah.site_domain'))->assertNotFound();
});

it('rejects a taken, reserved or malformed address', function () {
    WeddingSite::factory()->create(['subdomain' => 'diambil']);

    foreach (['diambil', 'admin', 'Ada Ruang', 'a', 'ada_underscore'] as $subdomain) {
        $this->actingAs($this->aina)
            ->put(route('weddings.site.update', $this->wedding), [...siteData(), 'subdomain' => $subdomain])
            ->assertSessionHasErrors('subdomain');
    }
});

it('uploads a cover image and keeps the partner able to edit', function () {
    Storage::fake('public');
    $hakim = User::factory()->create();
    $this->wedding->addMember($hakim, WeddingRole::Partner);

    $this->actingAs($hakim)
        ->put(route('weddings.site.update', $this->wedding), [...siteData(), 'cover_image' => UploadedFile::fake()->image('kad.jpg')])
        ->assertRedirect();

    $site = WeddingSite::sole();
    Storage::disk('public')->assertExists($site->cover_image);

    $this->actingAs(User::factory()->create())
        ->put(route('weddings.site.update', $this->wedding), siteData())
        ->assertForbidden();
});

it('previews the couple own card without publishing it', function () {
    $this->actingAs($this->aina)
        ->get(route('site.preview'))
        ->assertOk()
        ->assertSee('Pratonton kad anda')
        ->assertSee('Aina');

    expect(WeddingSite::count())->toBe(0);
});

it('starts the draft on an address nobody holds yet', function () {
    WeddingSite::factory()->create(['subdomain' => 'aina-hakim']);

    $props = $this->actingAs($this->aina)->get(route('site.edit'))->assertOk()->viewData('props');

    expect($props['site']['subdomain'])->toBe('aina-hakim-'.$this->wedding->event_date->year);
});

it('tells the couple a free address is available as they type', function () {
    $this->actingAs($this->aina)
        ->getJson(route('site.subdomain', ['subdomain' => ' Aina-Hakim ']))
        ->assertOk()
        ->assertJson([
            'subdomain' => 'aina-hakim',
            'available' => true,
            'suggestions' => [],
        ]);
});

it('says a taken address is taken and offers free ones instead', function () {
    WeddingSite::factory()->create(['subdomain' => 'aina-hakim']);
    WeddingSite::factory()->create(['subdomain' => 'aina-hakim-'.$this->wedding->event_date->year]);

    $this->actingAs($this->aina)
        ->getJson(route('site.subdomain', ['subdomain' => 'aina-hakim']))
        ->assertOk()
        ->assertJson([
            'available' => false,
            'message' => 'Alamat web ini telah diambil. Cuba yang lain.',
            'suggestions' => ['walimah-aina-hakim', 'aina-hakim-kahwin', 'majlis-aina-hakim'],
        ]);
});

it('refuses a reserved address in the live check with the same message as the save', function () {
    $this->actingAs($this->aina)
        ->getJson(route('site.subdomain', ['subdomain' => 'admin']))
        ->assertOk()
        ->assertJson([
            'available' => false,
            'message' => 'Alamat web ini dikhaskan untuk platform. Sila pilih yang lain.',
        ]);
});

it('counts the couple own saved address as theirs to keep', function () {
    WeddingSite::factory()->for($this->wedding)->create(['subdomain' => 'ainapilihhakim']);

    $this->actingAs($this->aina)
        ->getJson(route('site.subdomain', ['subdomain' => 'ainapilihhakim']))
        ->assertOk()
        ->assertJson(['available' => true]);
});

it('keeps the address check to signed-in couples', function () {
    // Without a wedding it redirects like every planning tool; see WeddingRequiredTest.
    $this->getJson(route('site.subdomain', ['subdomain' => 'aina-hakim']))->assertUnauthorized();
});

/**
 * @return array<string, mixed>
 */
function siteData(): array
{
    return [
        'subdomain' => 'ainapilihhakim',
        'template' => 'mawar-pagi',
        'bride_name' => 'Aina Zulkifli',
        'groom_name' => 'Hakim Ismail',
        'event_date' => now()->addMonths(4)->toDateString(),
        'starts_at' => '11:00',
        'ends_at' => '16:00',
        'venue_name' => 'Dewan Seri Melati',
        'rsvp_enabled' => 1,
        'itinerary' => [
            ['time' => '11:00 pagi', 'label' => 'Ketibaan tetamu'],
            ['time' => '1:00 petang', 'label' => 'Makan beradab'],
            ['time' => '', 'label' => ''],
        ],
        'contacts' => [
            ['name' => 'Puan Rohana', 'phone' => '012-345 6789'],
            ['name' => '', 'phone' => ''],
        ],
    ];
}

it('opens the appearance panel on what the card is set to now', function () {
    $props = $this->actingAs($this->aina)->get(route('site.edit'))->assertOk()->viewData('props');

    // Nothing has been overridden, so the panel shows the template's own
    // choices rather than a row of empty fields.
    expect($props['design']['current']['artwork'])->not->toBeNull()
        ->and($props['design']['current']['palette'])->toHaveKey('accent')
        ->and($props['design']['options']['artwork'])->not->toBeEmpty()
        ->and(collect($props['sections'])->pluck('key'))->toContain('countdown', 'rsvp')
        ->and(collect($props['sections'])->every(fn (array $s): bool => $s['on']))->toBeTrue();
});

it('keeps what a couple changed and throws away what it cannot draw', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), [
            ...siteData(),
            'design_overrides' => [
                'artwork' => 'gerbang',
                'palette' => ['accent' => '#112233', 'page' => 'rgb(1,2,3)'],
                'ornament' => 'definitely-not-an-ornament',
                'type' => ['script' => "'Comic Sans MS', cursive"],
                'onload' => 'alert(1)',
            ],
        ])
        ->assertSessionHasNoErrors();

    $overrides = $this->wedding->fresh()->site->design_overrides;

    expect($overrides)->toBe([
        'artwork' => 'gerbang',
        'palette' => ['accent' => '#112233'],
    ]);
});

it('leaves a card that changed nothing on the template exactly as designed', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), siteData())
        ->assertSessionHasNoErrors();

    $site = $this->wedding->fresh()->site;

    expect($site->design_overrides)->toBeNull()
        ->and($site->sections)->toBeNull()
        ->and($site->design()->artwork())->toBe('kalungan');
});

it('prints the sections in the order the couple arranged, and drops the ones they switched off', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), [
            ...siteData(),
            'sections' => [
                ['key' => 'venue', 'on' => '1'],
                ['key' => 'countdown', 'on' => '0'],
                ['key' => 'itinerary', 'on' => '1'],
            ],
        ])
        ->assertSessionHasNoErrors();

    $order = $this->wedding->fresh()->site->sectionOrder();

    expect(array_slice($order, 0, 2))->toBe(['venue', 'itinerary'])
        ->and($order)->not->toContain('countdown')
        // A section the couple never saw is new, not switched off.
        ->and($order)->toContain('rsvp');
});

it('renders the published card in the arranged order', function () {
    $site = WeddingSite::factory()->for($this->wedding)->create([
        'template' => 'mawar-pagi',
        'is_published' => true,
        'venue_name' => 'Dewan Seri Melati',
        'itinerary' => [['time' => '11:00 pagi', 'label' => 'Ketibaan tetamu']],
        'sections' => [
            ['key' => 'itinerary', 'on' => true],
            ['key' => 'venue', 'on' => true],
            ['key' => 'countdown', 'on' => false],
        ],
    ]);

    $this->get('http://'.$site->subdomain.'.'.config('neekah.site_domain'))
        ->assertOk()
        ->assertSeeInOrder(['Atur Cara Majlis', 'Dewan Seri Melati'], false)
        ->assertDontSee('Menghitung Hari');
});
