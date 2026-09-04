<?php

use App\Enums\WeddingRole;
use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingTimelineItem;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create(['title' => 'Aina & Hakim']);
    $this->vendor = Vendor::factory()->for(Category::first())->create(['name' => 'ABC Wedding Photography']);
    $this->booking = Booking::factory()->confirmed()->for($this->aina)->for($this->vendor)->create(['wedding_id' => $this->wedding->id]);
});

it('builds the day in time order', function () {
    foreach ([['14:00', 'Bersanding'], ['08:00', 'Makeup'], ['13:00', 'Akad Nikah']] as [$time, $title]) {
        $this->actingAs($this->aina)
            ->post(route('weddings.timeline.store', $this->wedding), ['starts_at' => $time, 'title' => $title])
            ->assertRedirect();
    }

    $this->actingAs($this->aina)
        ->get(route('timeline.index'))
        ->assertOk()
        ->assertSeeInOrder(['Makeup', 'Akad Nikah', 'Bersanding'])
        ->assertSee('8:00 AM');
});

it('assigns a booked vendor to a slot and rejects one that is not booked', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.timeline.store', $this->wedding), [
            'starts_at' => '10:00',
            'ends_at' => '17:00',
            'title' => 'Photographer arrives',
            'vendor_id' => $this->vendor->id,
            'location' => 'Rumah pengantin',
        ])
        ->assertRedirect();

    expect(WeddingTimelineItem::sole()->vendor_id)->toBe($this->vendor->id);

    $stranger = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($this->aina)
        ->post(route('weddings.timeline.store', $this->wedding), ['starts_at' => '11:00', 'title' => 'X', 'vendor_id' => $stranger->id])
        ->assertSessionHasErrors('vendor_id');
});

it('shows a vendor only their own slots', function () {
    WeddingTimelineItem::factory()->for($this->wedding)->create(['starts_at' => '10:00', 'title' => 'Photographer arrives', 'vendor_id' => $this->vendor->id]);
    WeddingTimelineItem::factory()->for($this->wedding)->create(['starts_at' => '08:00', 'title' => 'Makeup pengantin', 'vendor_id' => null]);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.bookings.show', $this->booking))
        ->assertOk()
        ->assertSee('Photographer arrives')
        ->assertDontSee('Makeup pengantin');
});

it('rejects an end time before the start and a title that is missing', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.timeline.store', $this->wedding), ['starts_at' => '14:00', 'ends_at' => '10:00', 'title' => ''])
        ->assertSessionHasErrors(['ends_at', 'title']);
});

it('lets the partner edit and delete slots but keeps strangers out', function () {
    $hakim = User::factory()->create();
    $this->wedding->addMember($hakim, WeddingRole::Partner);
    $item = WeddingTimelineItem::factory()->for($this->wedding)->create(['starts_at' => '08:00', 'title' => 'Makeup']);

    $this->actingAs($hakim)
        ->put(route('weddings.timeline.update', [$this->wedding, $item]), ['starts_at' => '07:30', 'title' => 'Makeup pengantin'])
        ->assertRedirect();

    expect($item->fresh()->title)->toBe('Makeup pengantin');

    $this->actingAs(User::factory()->create())
        ->delete(route('weddings.timeline.destroy', [$this->wedding, $item]))
        ->assertForbidden();

    $this->actingAs($hakim)->delete(route('weddings.timeline.destroy', [$this->wedding, $item]))->assertRedirect();
    expect(WeddingTimelineItem::count())->toBe(0);
});
