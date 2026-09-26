<?php

use App\Models\Category;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\ContactSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);

    $this->vendor = Vendor::factory()->pending()->for(Category::first())->create([
        'tagline' => null,
        'description' => null,
        'cover_image' => null,
        'price_from' => 0,
    ]);
});

it('shows a vendor awaiting approval the setup guide instead of the sidebar and bookings', function () {
    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertOk()
        ->assertViewIs('vendor.setup')
        ->assertSee(__('pages.vendor_setup.guide_title'))
        ->assertSee(__('pages.vendor_setup.why_title'))
        ->assertSee(__('pages.vendor_setup.unlock_title'))
        ->assertSee(route('vendor.setup.profile'), false)
        ->assertDontSee('id="dashboard-drawer"', false)
        ->assertDontSee(route('vendor.bookings.create'), false);
});

it('puts what to do now above the form, marking the step the vendor is on', function () {
    // Above the form in the markup, so a phone shows it first.
    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertSeeInOrder([
            __('pages.vendor_setup.guide_title'),
            __('pages.vendor_setup.guide_1_title'),
            __('pages.vendor_setup.you_are_here'),
            __('pages.vendor_setup.guide_2_title'),
            route('vendor.setup.profile'),
        ], false);
});

it('sends a vendor awaiting approval back to the dashboard from every other vendor page', function (string $route) {
    $this->actingAs($this->vendor->user)
        ->get(route($route))
        ->assertRedirect(route('vendor.dashboard'));
})->with(['vendor.bookings.index', 'vendor.bookings.create', 'vendor.profile.edit', 'vendor.packages.index', 'vendor.availability.index', 'vendor.enquiries.index', 'vendor.pro.index']);

it('does not let a vendor awaiting approval record a booking', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.store'), [])
        ->assertRedirect(route('vendor.dashboard'));

    expect($this->vendor->bookings()->count())->toBe(0);
});

it('keeps the full vendor area for an approved vendor', function () {
    $approved = Vendor::factory()->pro()->for(Category::first())->create();

    $this->actingAs($approved->user)
        ->get(route('vendor.dashboard'))
        ->assertOk()
        ->assertViewIs('vendor.dashboard')
        ->assertSee('id="dashboard-drawer"', false);

    $this->actingAs($approved->user)->get(route('vendor.bookings.index'))->assertOk();
});

it('saves the tagline and description from the setup card', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.setup.profile'), ['tagline' => 'Candid di utara', 'description' => 'Fotografi majlis.'])
        ->assertRedirect(route('vendor.dashboard'))
        ->assertSessionHas('status', __('flash.vendor.setup_profile_saved'));

    expect($this->vendor->refresh())
        ->tagline->toBe('Candid di utara')
        ->description->toBe('Fotografi majlis.');
});

it('keeps setup card errors in the card own bag', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.setup.profile'), [])
        ->assertSessionHasErrors(['tagline', 'description'], errorBag: 'setupProfile');
});

it('uploads the cover photo from the setup card', function () {
    Storage::fake('public');

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.setup.cover'), ['cover_image' => UploadedFile::fake()->image('cover.jpg', 800, 600)])
        ->assertRedirect(route('vendor.dashboard'));

    Storage::disk('public')->assertExists($this->vendor->refresh()->cover_image);
});

it('returns a vendor awaiting approval to the dashboard after changing the catalogue', function () {
    Storage::fake('public');
    $user = $this->vendor->user;

    $this->actingAs($user)
        ->post(route('vendor.portfolio.store'), ['images' => [UploadedFile::fake()->image('a.jpg', 800, 600)]])
        ->assertRedirect(route('vendor.dashboard', ['langkah' => 'portfolio']));

    $this->actingAs($user)
        ->delete(route('vendor.portfolio.destroy', PortfolioItem::sole()))
        ->assertRedirect(route('vendor.dashboard', ['langkah' => 'portfolio']));

    $this->actingAs($user)
        ->post(route('vendor.packages.store'), ['name' => 'Pakej Asas', 'price' => 2000, 'features' => "Album\nVideo"])
        ->assertRedirect(route('vendor.dashboard', ['langkah' => 'pakej']));

    // There is no price card: the starting price comes from the cheapest package.
    expect((float) $this->vendor->refresh()->price_from)->toBe(2000.0);

    $this->actingAs($user)
        ->delete(route('vendor.packages.destroy', Package::sole()))
        ->assertRedirect(route('vendor.dashboard', ['langkah' => 'pakej']));

});

it('tells a vendor awaiting approval how to reach the Neekah team', function () {
    app(ContactSettings::class)->save(['email' => 'neekahhq@gmail.com', 'phone' => '601163983556', 'whatsapp' => '601163983556']);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertSee('mailto:neekahhq@gmail.com', false)
        ->assertSee('https://wa.me/601163983556?text=', false)
        ->assertSee('tel:+601163983556', false)
        ->assertSee('+60 11-6398 3556');
});

/**
 * The radio that opens a step, as the setup page renders it when that step is on screen.
 */
function openStep(string $key): string
{
    return 'id="langkah-'.$key.'" value="'.$key.'" class="sr-only" checked';
}

it('opens the first step still to do, and a named step when a save comes back to it', function () {
    $this->vendor->update(['tagline' => 'Candid', 'description' => 'Fotografi majlis.']);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertSee(openStep('cover'), false)
        ->assertDontSee(openStep('profil'), false);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard', ['langkah' => 'pakej']))
        ->assertSee(openStep('pakej'), false);
});

it('opens the step whose form came back with errors', function () {
    $this->actingAs($this->vendor->user)
        ->from(route('vendor.dashboard'))
        ->followingRedirects()
        ->post(route('vendor.packages.store'), [])
        ->assertSee(openStep('pakej'), false);
});

it('thanks a vendor who has done every step and says the admin will review them', function () {
    $this->vendor->update(['tagline' => 'Candid', 'description' => 'Fotografi majlis.', 'cover_image' => 'vendors/cover.webp']);
    PortfolioItem::factory()->count(3)->for($this->vendor)->create();
    Package::factory()->for($this->vendor)->create();

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertSee(openStep('selesai'), false)
        ->assertSee(__('pages.vendor_setup.thanks_title', ['name' => $this->vendor->name]))
        ->assertSee(__('pages.vendor_setup.finish'))
        // Their part is done: the guide now points at the admin's review.
        ->assertSeeInOrder([__('pages.vendor_setup.guide_2_title'), __('pages.vendor_setup.you_are_here'), __('pages.vendor_setup.guide_3_title')]);
});

it('points the last step at what is still missing instead of offering to finish', function () {
    $this->actingAs($this->vendor->user)
        ->get(route('vendor.dashboard', ['langkah' => 'pakej']))
        ->assertDontSee('id="langkah-selesai"', false)
        ->assertSee(__('pages.vendor_setup.still_to_do', ['count' => 4]));
});
