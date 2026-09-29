<?php

use App\Actions\GrantBoostTokens;
use App\Enums\BoostTokenReason;
use App\Enums\EnquiryStatus;
use App\Enums\PointReason;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBookingSetting;
use App\Models\VendorUnavailableDate;
use App\Notifications\BookingConfirmed;
use App\Notifications\EnquiryReplied;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->create();
    $this->vendor->categories()->syncWithoutDetaching([Category::first()->id]);
    Sanctum::actingAs($this->vendor->user);
});

it('shows a month of the calendar: what is booked and what is closed', function () {
    $this->travelTo('2026-10-05 10:00');
    Booking::factory()->for($this->vendor)->confirmed()->create(['event_date' => '2026-10-17']);
    Booking::factory()->for($this->vendor)->confirmed()->create(['event_date' => '2026-11-07']);
    $this->vendor->unavailableDates()->create(['date' => '2026-10-20', 'source' => VendorUnavailableDate::SOURCE_ICAL]);

    $this->getJson(route('api.v1.calendar.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonPath('month', '2026-10')
        ->assertJsonCount(1, 'booked')
        ->assertJsonPath('booked.0.date', '2026-10-17')
        ->assertJsonPath('closed.0.imported', true)
        ->assertJsonPath('closed.0.can_reopen', false);
});

it('closes a range of days and reopens one, but never an imported day or another vendor own', function () {
    $this->postJson(route('api.v1.calendar.close'), ['from' => now()->addDays(3)->toDateString(), 'to' => now()->addDays(5)->toDateString(), 'reason' => 'Majlis luar'])
        ->assertOk()
        ->assertJsonPath('added', 3);

    $manual = $this->vendor->unavailableDates()->first();
    $imported = $this->vendor->unavailableDates()->create(['date' => now()->addDays(9)->toDateString(), 'source' => VendorUnavailableDate::SOURCE_ICAL]);
    $theirs = VendorUnavailableDate::query()->create(['vendor_id' => Vendor::factory()->create()->id, 'date' => now()->addDay()->toDateString(), 'source' => VendorUnavailableDate::SOURCE_MANUAL]);

    $this->deleteJson(route('api.v1.calendar.reopen', $manual))->assertOk();
    $this->deleteJson(route('api.v1.calendar.reopen', $imported))->assertForbidden();
    $this->deleteJson(route('api.v1.calendar.reopen', $theirs))->assertNotFound();

    expect($this->vendor->unavailableDates()->count())->toBe(3);
    $this->postJson(route('api.v1.calendar.close'), ['from' => now()->subDay()->toDateString()])->assertJsonValidationErrors('from');
});

it('switches online booking on only once a deposit has somewhere to go', function () {
    enableOnlineBooking();

    $this->putJson(route('api.v1.online-booking'), ['enabled' => true])->assertUnprocessable()->assertJsonValidationErrors('enabled');

    VendorBookingSetting::factory()->for($this->vendor)->create(['enabled' => false]);
    Sanctum::actingAs($this->vendor->user->fresh());

    $this->putJson(route('api.v1.online-booking'), ['enabled' => true])->assertOk()->assertJsonPath('online.enabled', true);
    $this->putJson(route('api.v1.online-booking'), ['enabled' => false])->assertOk()->assertJsonPath('online.enabled', false);
});

it('lists enquiries open first with counts, and shows only the vendor own', function () {
    Enquiry::factory()->for($this->vendor)->replied()->create();
    $open = Enquiry::factory()->for($this->vendor)->create();

    $this->getJson(route('api.v1.enquiries.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $open->id)
        ->assertJsonPath('counts.open', 1)
        ->assertJsonPath('counts.replied', 1);
    $this->getJson(route('api.v1.enquiries.index', ['status' => 'replied']))->assertJsonCount(1, 'data');
    $this->getJson(route('api.v1.enquiries.show', Enquiry::factory()->create()))->assertForbidden();
});

it('replies to an enquiry, tells the couple and rewards a reply within the day', function () {
    Notification::fake();
    $enquiry = Enquiry::factory()->for($this->vendor)->create();

    $this->postJson(route('api.v1.enquiries.reply', $enquiry), ['reply' => ''])->assertJsonValidationErrors('reply');
    $this->postJson(route('api.v1.enquiries.reply', $enquiry), ['reply' => 'Tarikh itu masih kosong.'])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'replied')
        ->assertJsonPath('data.reply', 'Tarikh itu masih kosong.');

    expect($enquiry->fresh()->status)->toBe(EnquiryStatus::Replied)
        ->and($this->vendor->points()->where('reason', PointReason::FastResponse)->exists())->toBeTrue();
    Notification::assertSentTo($enquiry->user, EnquiryReplied::class);
});

it('spends tokens to boost a category, never more than the balance', function () {
    app(GrantBoostTokens::class)->handle($this->vendor, 3, BoostTokenReason::Welcome);

    $this->getJson(route('api.v1.boost.index'))->assertOk()->assertJsonPath('balance', 3)->assertJsonPath('categories.0.id', Category::first()->id);
    $this->postJson(route('api.v1.boost.store'), ['category_id' => Category::first()->id, 'days' => 5])->assertUnprocessable()->assertJsonValidationErrors('days');
    $this->postJson(route('api.v1.boost.store'), ['category_id' => Category::first()->id, 'days' => 2])
        ->assertOk()
        ->assertJsonPath('balance', 1)
        ->assertJsonPath('boost.category', Category::first()->name);
});

it('shows points, score and what the next tier needs', function () {
    $this->getJson(route('api.v1.points'))
        ->assertOk()
        ->assertJsonPath('tier.value', 'verified')
        ->assertJsonPath('next_tier.value', 'trusted')
        ->assertJsonStructure(['points_total', 'score', 'breakdown' => [['reason', 'label', 'points_each', 'total']], 'requirements']);
});

it('opens the dashboard with the numbers that matter today', function () {
    Booking::factory()->for($this->vendor)->confirmed()->create(['event_date' => now()->addWeek()]);
    Enquiry::factory()->for($this->vendor)->create();

    $this->getJson(route('api.v1.dashboard'))
        ->assertOk()
        ->assertJsonPath('stats.upcoming_events', 1)
        ->assertJsonPath('stats.open_enquiries', 1)
        ->assertJsonCount(1, 'upcoming')
        ->assertJsonCount(28, 'daily_views');
});

it('lists notifications with what each one is about, and marks them read', function () {
    $couple = User::factory()->create();
    $booking = Booking::factory()->for($this->vendor)->for($couple)->create();
    $this->vendor->user->notify(new BookingConfirmed($booking));

    $this->getJson(route('api.v1.notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonPath('data.0.target.type', 'booking')
        ->assertJsonPath('data.0.target.id', $booking->reference);

    $this->postJson(route('api.v1.notifications.read'))->assertOk()->assertJsonPath('unread', 0);
});
