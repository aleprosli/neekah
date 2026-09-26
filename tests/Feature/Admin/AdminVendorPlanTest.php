<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->basic = Vendor::factory()->for(Category::first())->create(['name' => 'Studio Asas']);
    $this->pro = Vendor::factory()->pro()->for(Category::first())->create(['name' => 'Studio Pro']);
});

it('shows each vendor plan on the list, and filters by it', function () {
    $response = $this->actingAs($this->admin)->getJson(route('admin.vendors.data', ['plan' => 'pro']))->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$this->pro->id])
        ->and($response->json('data.0.plan'))->toContain('Pro')
        ->and($response->json('filters.plan'))->toBe(['' => 2, 'basic' => 1, 'pro' => 1]);

    $basic = $this->actingAs($this->admin)->getJson(route('admin.vendors.data', ['plan' => 'basic']))->json('data');
    expect(collect($basic)->pluck('id')->all())->toBe([$this->basic->id])
        ->and($basic[0]['plan'])->toContain('Basic');
});

it('lets an admin give a vendor Pro for free, and end it early', function () {
    $card = $this->actingAs($this->admin)->get(route('admin.vendors.show', $this->basic))->assertOk()->viewData('props')['plan'];
    expect($card['isPro'])->toBeFalse();

    $this->actingAs($this->admin)->post($card['storeUrl'], ['plan' => 'monthly', 'amount' => '0', 'note' => 'Promosi'])->assertSessionHasNoErrors();

    $vendor = $this->basic->fresh();
    expect($vendor->isPro())->toBeTrue()
        ->and((float) $vendor->subscriptions()->sole()->amount)->toBe(0.0);
    $this->actingAs($vendor->user)->get(route('vendor.bookings.index'))->assertOk();

    $this->actingAs($this->admin)->delete(route('admin.vendors.pro.end', $vendor))->assertRedirect();

    expect($vendor->fresh()->isPro())->toBeFalse();
    $this->actingAs($vendor->user->fresh())->get(route('vendor.bookings.index'))->assertRedirect(route('vendor.pro.index'));
});

it('keeps the plan tools from anyone but an admin', function () {
    $this->actingAs($this->basic->user)->post(route('admin.vendors.pro', $this->basic), ['plan' => 'yearly', 'amount' => 0])->assertForbidden();
    $this->actingAs($this->pro->user)->delete(route('admin.vendors.pro.end', $this->pro))->assertForbidden();

    expect($this->basic->fresh()->isPro())->toBeFalse()
        ->and($this->pro->fresh()->isPro())->toBeTrue();
});
