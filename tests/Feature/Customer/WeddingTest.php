<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
});

it('prompts a customer with no wedding to create one', function () {
    $this->actingAs($this->customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cipta wedding project');
});

it('creates a wedding project', function () {
    $this->actingAs($this->customer)
        ->post(route('weddings.store'), [
            'title' => 'Aina & Hakim',
            'event_date' => '2027-12-20',
            'city' => 'Alor Setar',
            'state' => 'Kedah',
            'budget' => 30000,
            'notes' => 'Majlis dua belah pihak',
        ])
        ->assertRedirect(route('dashboard'));

    $wedding = Wedding::sole();

    expect($wedding->user_id)->toBe($this->customer->id)
        ->and($wedding->title)->toBe('Aina & Hakim')
        ->and((float) $wedding->budget)->toBe(30000.0);

    $this->actingAs($this->customer)->get(route('dashboard'))->assertOk()->assertSee('Aina & Hakim');
});

it('welcomes a couple to their first wedding and previews the budget split they will get', function () {
    $this->customer->update(['name' => 'Nur Aina Adriana Binti Abdullah']);

    $response = $this->actingAs($this->customer)
        ->get(route('weddings.create'))
        ->assertOk()
        ->assertSee('Selamat datang, Aina')
        ->assertSee(__('pages.wedding_create.title'));

    $shares = collect($response->viewData('props')['budgetShares']);

    expect($shares->first())->toBe(['name' => 'Catering', 'icon' => '🍽️', 'share' => 0.33])
        ->and($shares->pluck('share')->all())->toBe($shares->pluck('share')->sortDesc()->values()->all());

    // What the preview promised is what the Bajet page starts with.
    $this->actingAs($this->customer)->post(route('weddings.store'), [
        'title' => 'Aina & Hakim',
        'event_date' => now()->addYear()->toDateString(),
        'city' => 'Alor Setar',
        'state' => 'Kedah',
        'budget' => 30000,
    ]);

    $catering = Category::where('slug', 'catering')->sole();

    expect((float) Wedding::sole()->budgetItems()->where('category_id', $catering->id)->value('planned_amount'))->toBe(30000 * 0.33);
});

it('leaves the budget split out when an existing wedding is edited', function () {
    $wedding = Wedding::factory()->for($this->customer)->create();

    $props = $this->actingAs($this->customer)
        ->get(route('weddings.edit', $wedding))
        ->assertOk()
        ->assertDontSee(__('pages.wedding_create.title'))
        ->viewData('props');

    expect($props['budgetShares'])->toBe([])
        ->and($props['budgetUrl'])->toBe(route('budget.index'));
});

it('rejects a wedding in the past or in an unknown state', function () {
    $this->actingAs($this->customer)
        ->post(route('weddings.store'), [
            'title' => 'Test',
            'event_date' => now()->subDay()->toDateString(),
            'city' => 'Nowhere',
            'state' => 'Atlantis',
            'budget' => 1000,
        ])
        ->assertSessionHasErrors(['event_date', 'state']);
});

it('only lets the owner edit their wedding', function () {
    $wedding = Wedding::factory()->for($this->customer)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('weddings.edit', $wedding))->assertForbidden();

    $this->actingAs($this->customer)
        ->put(route('weddings.update', $wedding), [
            'title' => 'Nama Baharu',
            'event_date' => now()->addYear()->toDateString(),
            'city' => 'Ipoh',
            'state' => 'Perak',
            'budget' => 45000,
        ])
        ->assertRedirect(route('dashboard'));

    expect($wedding->fresh()->title)->toBe('Nama Baharu');
});

it('shows budget totals and booked categories on the dashboard', function () {
    $wedding = Wedding::factory()->for($this->customer)->create(['budget' => 30000]);
    $vendor = Vendor::factory()->for(Category::where('slug', 'photography')->first())->create();
    Booking::factory()->confirmed()->for($this->customer)->for($vendor)->create([
        'wedding_id' => $wedding->id,
        'total_amount' => 2500,
    ]);

    $this->actingAs($this->customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('RM30,000')
        ->assertSee('RM2,500')
        ->assertSee($vendor->name)
        ->assertSee('1 / 12');
});

it('points the couple at their digital card from the dashboard, and stops asking once it is out', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();

    $this->actingAs($couple)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Buat kad digital')
        ->assertSee(route('site.edit'), false);

    WeddingSite::factory()->published()->for($wedding)->create();

    $this->actingAs($couple)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Kad digital saya')
        ->assertDontSee('Buat kad digital');
});

it('offers no card button before there is a wedding to make one for', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Buat kad digital')
        ->assertDontSee('Kad digital saya');
});

it('puts the partner invitation right under the buttons, and the digital card first among them', function () {
    $couple = User::factory()->create();
    Wedding::factory()->for($couple)->create();

    // The invitation used to sit at the foot of the dashboard, where it went
    // unseen. In the markup the card button also comes first, which is the
    // order a phone shows them in.
    $this->actingAs($couple)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Buat kad digital', 'Edit majlis', 'Cari vendor', 'Uruskan majlis berdua', 'data-vue="customer-dashboard-page"'], false);
});
