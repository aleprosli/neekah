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
    $this->actingAs($this->aina)
        ->get(route('site.edit'))
        ->assertOk()
        ->assertSee('Aina')
        ->assertSee('Hakim')
        ->assertSee('aina-hakim')
        ->assertSee('Alamat web');
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
