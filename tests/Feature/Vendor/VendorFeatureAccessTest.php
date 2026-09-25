<?php

use App\Enums\VendorFeature;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\EnquiryReceived;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->basic = Vendor::factory()->for(Category::first())->create();
    $this->pro = Vendor::factory()->pro()->for(Category::first())->create();
});

it('gives Basic the profile features and boosting, and Pro everything', function () {
    foreach (['vendor.packages.index', 'vendor.portfolio.index', 'vendor.reviews.index', 'vendor.boost.index'] as $route) {
        $this->actingAs($this->basic->user)->get(route($route))->assertOk();
    }

    foreach (['vendor.availability.index', 'vendor.bookings.index', 'vendor.enquiries.index', 'vendor.points.index'] as $route) {
        $this->actingAs($this->pro->user)->get(route($route))->assertOk();
    }

    expect(collect(VendorFeature::cases())->filter->requiresPro()->values()->all())
        ->toBe([VendorFeature::Calendar, VendorFeature::Bookings, VendorFeature::Enquiries, VendorFeature::Points, VendorFeature::OnlineBooking]);
});

it('sends a Basic vendor to the Pro page for a Pro feature, and refuses writes to it', function () {
    $this->actingAs($this->basic->user)
        ->get(route('vendor.bookings.index'))
        ->assertRedirect(route('vendor.pro.index'))
        ->assertSessionHas('status', __('flash.vendor.feature_needs_pro', ['feature' => VendorFeature::Bookings->label()]));

    $this->actingAs($this->basic->user)->get(route('vendor.availability.index'))->assertRedirect(route('vendor.pro.index'));
    $this->actingAs($this->basic->user)->post(route('vendor.bookings.store'), [])->assertForbidden();
});

it('lists Basic first and Pro below in folding groups, with Pro locked on Basic', function () {
    $this->actingAs($this->basic->user)
        ->get(route('vendor.profile.edit'))
        ->assertSeeInOrder(['data-nav-group="vendor-basic"', route('vendor.packages.index'), 'data-nav-group="vendor-pro"', __('pages.sidebar_vendor.kalendar_tempahan')], false)
        ->assertSee(__('nav.pro_locked'))
        ->assertSee(__('pages.sidebar_vendor.upgrade_pro'))
        ->assertDontSee(route('vendor.bookings.index'), false);

    $this->actingAs($this->pro->user)
        ->get(route('vendor.profile.edit'))
        ->assertSee(route('vendor.bookings.index'), false)
        ->assertDontSee(__('nav.pro_locked'));
});

it('keeps the setup page working for a vendor awaiting approval', function () {
    $pending = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($pending->user)
        ->post(route('vendor.packages.store'), ['name' => 'Pakej Asas', 'price' => 1000, 'features' => 'Album'])
        ->assertRedirect(route('vendor.dashboard', ['langkah' => 'pakej']));

    expect($pending->packages()->count())->toBe(1);
});

it('has no admin pages for choosing features any more', function () {
    expect(Route::has('admin.vendor-features.index'))->toBeFalse()
        ->and(Route::has('admin.vendors.features'))->toBeFalse();

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.vendors.show', $this->basic))->assertOk();
});

it('still takes enquiries for Basic, but shows only how many are waiting', function () {
    Enquiry::factory()->for($this->basic)->create(['message' => 'Boleh saya tahu harga pakej?']);

    $this->actingAs($this->basic->user)
        ->get(route('vendor.enquiries.index'))
        ->assertOk()
        ->assertViewIs('vendor.enquiries.locked')
        ->assertDontSee('Boleh saya tahu harga pakej?');

    expect($this->actingAs($this->basic->user)->get(route('vendor.enquiries.index'))->viewData('props')['waiting'])->toBe(1);

    $enquiry = Enquiry::factory()->for($this->pro)->create(['message' => 'Tarikh 12 Disember kosong?']);
    $this->actingAs($this->pro->user)->get(route('vendor.enquiries.index'))->assertSee('Tarikh 12 Disember kosong?');
    $this->actingAs($this->basic->user)->get(route('vendor.enquiries.show', $enquiry))->assertRedirect(route('vendor.pro.index'));
});

it('tells a Basic vendor an enquiry arrived without giving away what it says', function () {
    $enquiry = Enquiry::factory()->for($this->basic)->create(['message' => 'Boleh saya tahu harga pakej?']);

    $mail = (new EnquiryReceived($enquiry))->toMail($this->basic->user);

    expect(implode(' ', $mail->introLines))->not->toContain('Boleh saya tahu harga pakej?')
        ->and($mail->actionUrl)->toBe(route('vendor.pro.index'))
        ->and((new EnquiryReceived($enquiry))->toDatabase($this->basic->user)['title_key'])->toBe('notifications.enquiry_received.locked_title');
});
