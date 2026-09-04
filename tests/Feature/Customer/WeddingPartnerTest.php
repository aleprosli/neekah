<?php

use App\Enums\WeddingRole;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use App\Notifications\WeddingPartnerInvited;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->aina = User::factory()->create(['name' => 'Aina Zulkifli', 'email' => 'aina@example.com']);
    $this->hakim = User::factory()->create(['name' => 'Hakim Ismail', 'email' => 'hakim@example.com']);
    $this->wedding = Wedding::factory()->for($this->aina)->create(['title' => 'Aina & Hakim', 'budget' => 30000]);
});

it('makes the creator the owner and the only member', function () {
    expect($this->wedding->members)->toHaveCount(1)
        ->and($this->wedding->isOwnedBy($this->aina))->toBeTrue()
        ->and($this->wedding->partner())->toBeNull();

    $this->actingAs($this->aina)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Belum terhubung')
        ->assertSee('Jemput pasangan');
});

it('emails an invitation and lets the partner accept it', function () {
    Notification::fake();

    $this->actingAs($this->aina)
        ->post(route('weddings.invitations.store', $this->wedding), ['email' => 'hakim@example.com'])
        ->assertRedirect();

    $invitation = WeddingInvitation::sole();

    expect($invitation->email)->toBe('hakim@example.com')
        ->and($invitation->invited_by)->toBe($this->aina->id)
        ->and($invitation->isPending())->toBeTrue();

    Notification::assertSentOnDemand(WeddingPartnerInvited::class);

    $this->actingAs($this->hakim)->get(route('invitations.show', $invitation))->assertOk()->assertSee('Aina & Hakim');

    $this->actingAs($this->hakim)
        ->post(route('invitations.accept', $invitation))
        ->assertRedirect(route('dashboard'));

    $this->wedding->refresh()->load('members');

    expect($this->wedding->members)->toHaveCount(2)
        ->and($this->wedding->partner()->id)->toBe($this->hakim->id)
        ->and($invitation->fresh()->accepted_by)->toBe($this->hakim->id);
});

it('sends a stranger with the link to register and joins them automatically', function () {
    $invitation = WeddingInvitation::factory()->for($this->wedding)->create(['invited_by' => $this->aina->id, 'email' => 'baru@example.com']);

    $this->get(route('invitations.show', $invitation))->assertRedirect(route('register'));

    // The register page names the inviter and prefills the invited email.
    $this->get(route('register'))->assertOk()->assertSee('Aina Zulkifli menjemput anda')->assertSee('baru@example.com');

    $this->post(route('register'), [
        'name' => 'Baru Sekali',
        'email' => 'baru@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
    ])->assertRedirect(route('dashboard'));

    $this->wedding->refresh()->load('members');

    expect($this->wedding->members)->toHaveCount(2)
        ->and($this->wedding->partner()->email)->toBe('baru@example.com')
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});

it('joins an existing user automatically when they log in from the link', function () {
    $invitation = WeddingInvitation::factory()->for($this->wedding)->create(['invited_by' => $this->aina->id, 'email' => 'hakim@example.com']);

    $this->get(route('invitations.show', $invitation))->assertRedirect(route('register'));

    $this->post(route('login'), ['email' => $this->hakim->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    expect($this->wedding->fresh()->hasMember($this->hakim))->toBeTrue();
});

it('shows the shareable link and connected state on the dashboard', function () {
    $invitation = WeddingInvitation::factory()->for($this->wedding)->create(['invited_by' => $this->aina->id, 'email' => 'hakim@example.com']);

    $this->actingAs($this->aina)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Menunggu jawapan')
        ->assertSee(route('invitations.show', $invitation));

    $this->wedding->addMember($this->hakim, WeddingRole::Partner);

    $this->actingAs($this->aina)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Terhubung')
        ->assertSee('Hakim Ismail');
});

it('shows both partners the same wedding, bookings and budget', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['name' => 'ABC Wedding Photography']);
    $package = Package::factory()->for($vendor)->create(['price' => 2500]);
    $this->wedding->addMember($this->hakim, WeddingRole::Partner);

    // Hakim books the photographer.
    $this->actingAs($this->hakim)
        ->post(route('vendors.bookings.store', $vendor), ['package_id' => $package->id, 'event_date' => now()->addMonths(3)->toDateString()])
        ->assertRedirect();

    $booking = Booking::sole();
    expect($booking->wedding_id)->toBe($this->wedding->id);

    // Aina sees it on her dashboard and booking list even though Hakim made it.
    $this->actingAs($this->aina)->get(route('dashboard'))->assertOk()->assertSee('ABC Wedding Photography')->assertSee('Hakim Ismail');
    $this->actingAs($this->aina)->get(route('bookings.index'))->assertOk()->assertSee($booking->reference);
    $this->actingAs($this->aina)->get(route('bookings.show', $booking))->assertOk();
});

it('lets either partner pay for a shared booking', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();
    $this->wedding->addMember($this->hakim, WeddingRole::Partner);
    $booking = Booking::factory()->for($this->aina)->for($vendor)->create(['wedding_id' => $this->wedding->id, 'total_amount' => 2500, 'deposit_amount' => 1000]);
    $deposit = Payment::factory()->for($booking)->create(['amount' => 1000]);

    $this->actingAs($this->hakim)
        ->post(route('bookings.payments.store', [$booking, $deposit]))
        ->assertRedirect(route('bookings.show', $booking));

    expect($deposit->fresh()->isPaid())->toBeTrue();
});

it('lets the partner edit the wedding but not manage membership', function () {
    $this->wedding->addMember($this->hakim, WeddingRole::Partner);

    $this->actingAs($this->hakim)
        ->put(route('weddings.update', $this->wedding), [
            'title' => 'Aina & Hakim 2027',
            'event_date' => now()->addYear()->toDateString(),
            'city' => 'Alor Setar',
            'state' => 'Kedah',
            'budget' => 35000,
        ])
        ->assertRedirect(route('dashboard'));

    expect($this->wedding->fresh()->title)->toBe('Aina & Hakim 2027');

    $this->actingAs($this->hakim)
        ->post(route('weddings.invitations.store', $this->wedding), ['email' => 'someone@example.com'])
        ->assertForbidden();

    $this->actingAs($this->hakim)
        ->delete(route('weddings.members.destroy', [$this->wedding, $this->aina]))
        ->assertForbidden();
});

it('lets the owner remove the partner but never themselves', function () {
    $this->wedding->addMember($this->hakim, WeddingRole::Partner);

    $this->actingAs($this->aina)
        ->delete(route('weddings.members.destroy', [$this->wedding, $this->aina]))
        ->assertSessionHasErrors('member');

    $this->actingAs($this->aina)
        ->delete(route('weddings.members.destroy', [$this->wedding, $this->hakim]))
        ->assertRedirect();

    expect($this->wedding->fresh()->members)->toHaveCount(1);
    $this->actingAs($this->hakim)->get(route('dashboard'))->assertOk()->assertSee('Cipta wedding project');
});

it('rejects inviting yourself, a duplicate invite and a third member', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.invitations.store', $this->wedding), ['email' => 'aina@example.com'])
        ->assertSessionHasErrors('email');

    $this->actingAs($this->aina)->post(route('weddings.invitations.store', $this->wedding), ['email' => 'hakim@example.com'])->assertRedirect();
    $this->actingAs($this->aina)
        ->post(route('weddings.invitations.store', $this->wedding), ['email' => 'hakim@example.com'])
        ->assertSessionHasErrors('email');

    $this->wedding->addMember($this->hakim, WeddingRole::Partner);

    $this->actingAs($this->aina)
        ->post(route('weddings.invitations.store', $this->wedding), ['email' => 'ketiga@example.com'])
        ->assertSessionHasErrors('email');
});

it('refuses an expired invitation and one for a wedding that is already full', function () {
    $expired = WeddingInvitation::factory()->expired()->for($this->wedding)->create(['invited_by' => $this->aina->id]);

    $this->actingAs($this->hakim)->post(route('invitations.accept', $expired))->assertSessionHasErrors('invitation');
    expect($this->wedding->fresh()->members)->toHaveCount(1);

    $third = User::factory()->create();
    $this->wedding->addMember($this->hakim, WeddingRole::Partner);
    $pending = WeddingInvitation::factory()->for($this->wedding)->create(['invited_by' => $this->aina->id]);

    $this->actingAs($third)->post(route('invitations.accept', $pending))->assertSessionHasErrors('invitation');
    expect($this->wedding->fresh()->members)->toHaveCount(2);
});

it('lets the owner cancel a pending invitation', function () {
    $invitation = WeddingInvitation::factory()->for($this->wedding)->create(['invited_by' => $this->aina->id]);

    $this->actingAs($this->aina)
        ->delete(route('weddings.invitations.destroy', [$this->wedding, $invitation]))
        ->assertRedirect();

    expect(WeddingInvitation::count())->toBe(0);
});
