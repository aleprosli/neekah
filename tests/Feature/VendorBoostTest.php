<?php

use App\Actions\ChangeVendorStatus;
use App\Actions\GrantBoostTokens;
use App\Enums\BoostTokenReason;
use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBoost;
use App\Notifications\BoostEnding;
use App\Notifications\BoostTokensReceived;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    [$this->category, $this->other] = Category::query()->orderBy('id')->take(2)->get()->all();
    $this->vendor = Vendor::factory()->for($this->category)->create(['name' => 'Studio Boost']);
});

function giveTokens(Vendor $vendor, int $tokens): void
{
    app(GrantBoostTokens::class)->handle($vendor, $tokens, BoostTokenReason::Admin);
}

it('gives the welcome tokens once, on the first approval only', function () {
    Notification::fake();
    $vendor = Vendor::factory()->pending()->for($this->category)->create();

    app(ChangeVendorStatus::class)->handle($vendor, VendorStatus::Approved);
    app(ChangeVendorStatus::class)->handle($vendor, VendorStatus::Suspended);
    app(ChangeVendorStatus::class)->handle($vendor, VendorStatus::Approved);

    expect($vendor->fresh()->boost_tokens)->toBe(7)
        ->and($vendor->boostEntries()->count())->toBe(1);
    Notification::assertSentToTimes($vendor->user, BoostTokensReceived::class, 1);
});

it('spends a token a day to boost a category, and extends a boost already running', function () {
    giveTokens($this->vendor, 10);

    $this->actingAs($this->vendor->user)->post(route('vendor.boost.store'), ['category_id' => $this->category->id, 'days' => 3])->assertSessionHasNoErrors();
    $this->actingAs($this->vendor->user)->post(route('vendor.boost.store'), ['category_id' => $this->category->id, 'days' => 2])->assertSessionHasNoErrors();

    $boost = VendorBoost::sole();
    expect($this->vendor->fresh()->boost_tokens)->toBe(5)
        ->and($boost->tokens)->toBe(5)
        ->and($boost->ends_at->equalTo(now()->startOfHour()->addDays(5)))->toBeTrue();
});

it('refuses a boost without enough tokens, or in a category the vendor does not work in', function () {
    giveTokens($this->vendor, 2);

    $this->actingAs($this->vendor->user)->post(route('vendor.boost.store'), ['category_id' => $this->category->id, 'days' => 3])->assertSessionHasErrors('days');
    $this->actingAs($this->vendor->user)->post(route('vendor.boost.store'), ['category_id' => $this->other->id, 'days' => 1])->assertSessionHasErrors('category_id');

    expect(VendorBoost::count())->toBe(0)
        ->and($this->vendor->fresh()->boost_tokens)->toBe(2);
});

it('puts a boosted vendor first in its own category, labelled, and only in the Disyorkan order', function () {
    $leader = Vendor::factory()->for($this->category)->create(['name' => 'Studio Juara', 'score' => 95, 'views_30d' => 50]);
    VendorBoost::factory()->create(['vendor_id' => $this->vendor->id, 'category_id' => $this->category->id]);
    $elsewhere = Vendor::factory()->for($this->other)->create(['name' => 'Katering Juara', 'score' => 99]);
    VendorBoost::factory()->create(['vendor_id' => $elsewhere->id, 'category_id' => $this->other->id, 'starts_at' => now()->subDays(5), 'ends_at' => now()->subMinute()]);

    $this->get(route('vendors.index', ['category' => $this->category->slug]))
        ->assertOk()
        ->assertSeeInOrder([__('marketplace.card.promoted'), 'Studio Boost', 'Studio Juara']);

    $this->get(route('vendors.index', ['category' => $this->category->slug, 'sort' => 'popular']))
        ->assertSeeInOrder(['Studio Juara', 'Studio Boost'])
        ->assertDontSee(__('marketplace.card.promoted'));

    // A boost that ran out lifts nobody.
    $this->get(route('vendors.index', ['category' => $this->other->slug]))->assertDontSee(__('marketplace.card.promoted'));
});

it('gives Pro vendors their monthly tokens once every 30 days and reminds a boost ending tomorrow', function () {
    Notification::fake();
    $this->vendor->update(['pro_until' => now()->addMonths(2)]);
    $free = Vendor::factory()->for($this->category)->create();
    $ending = VendorBoost::factory()->create(['vendor_id' => $free->id, 'category_id' => $this->category->id, 'ends_at' => now()->addDay()->setTime(15, 0)]);

    $this->artisan('neekah:boost-grants')->assertSuccessful();
    $this->artisan('neekah:boost-grants')->assertSuccessful();

    expect($this->vendor->fresh()->boost_tokens)->toBe(10)
        ->and($free->fresh()->boost_tokens)->toBe(0);
    Notification::assertSentTo($free->user, BoostEnding::class, fn (BoostEnding $notice): bool => $notice->boost->is($ending));

    $this->travel(31)->days();
    $this->artisan('neekah:boost-grants');
    expect($this->vendor->fresh()->boost_tokens)->toBe(20);
});

it('lets an admin add or take back tokens, never below zero', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.vendors.boost', $this->vendor), ['change' => 12, 'note' => 'Promosi'])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('admin.vendors.boost', $this->vendor), ['change' => -5])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('admin.vendors.boost', $this->vendor), ['change' => -50])->assertSessionHasErrors('tokens');

    expect($this->vendor->fresh()->boost_tokens)->toBe(7)
        ->and($this->vendor->boostEntries()->where('added_by', $admin->id)->count())->toBe(2);
    $this->actingAs($admin)->get(route('admin.vendors.show', $this->vendor))->assertOk();
    $this->actingAs($this->vendor->user)->post(route('admin.vendors.boost', $this->vendor), ['change' => 100])->assertForbidden();
});

it('gives existing approved vendors their welcome tokens once from the command', function () {
    Notification::fake();

    $this->artisan('neekah:boost-welcome')->assertSuccessful();
    $this->artisan('neekah:boost-welcome')->assertSuccessful();

    expect($this->vendor->fresh()->boost_tokens)->toBe(7);
});

it('shows the vendor their balance, what is running and the history', function () {
    giveTokens($this->vendor, 4);

    $props = $this->actingAs($this->vendor->user)->get(route('vendor.boost.index'))->assertOk()->viewData('props');

    expect($props['balance'])->toBe(4)
        ->and(collect($props['categories'])->pluck('id')->all())->toContain($this->category->id)
        ->and($props['history'])->toHaveCount(1)
        ->and($props['packs'])->toBe([]);
});
