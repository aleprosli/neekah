<?php

use App\Actions\SeedWeddingChecklist;
use App\Enums\PaymentStatus;
use App\Enums\WeddingRole;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create(['budget' => 30000]);
    $this->catering = Category::where('slug', 'catering')->first();
});

it('shows planned against actual per category from real bookings', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);

    $vendor = Vendor::factory()->for($this->catering)->create(['name' => 'Dapur Warisan']);
    $booking = Booking::factory()->confirmed()->for($this->aina)->for($vendor)->create([
        'wedding_id' => $this->wedding->id,
        'total_amount' => 9500,
        'deposit_amount' => 3800,
    ]);
    Payment::factory()->for($booking)->paid()->create(['amount' => 3800]);

    $this->actingAs($this->aina)
        ->get(route('budget.index'))
        ->assertOk()
        ->assertSee('RM30,000')
        ->assertSee('RM9,500')
        ->assertSee('Dapur Warisan')
        ->assertSee('dibayar RM3,800');
});

it('ignores cancelled bookings in the actual column', function () {
    $vendor = Vendor::factory()->for($this->catering)->create();
    Booking::factory()->cancelled()->for($this->aina)->for($vendor)->create(['wedding_id' => $this->wedding->id, 'total_amount' => 5000]);

    $this->actingAs($this->aina)->get(route('budget.index'))->assertOk()->assertDontSee('RM5,000');
});

it('saves the per-category budget and the overall total', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.budget.update', $this->wedding), [
            'budget' => 45000,
            'planned' => [$this->catering->id => 12000],
        ])
        ->assertRedirect();

    expect((float) $this->wedding->fresh()->budget)->toBe(45000.0)
        ->and((float) $this->wedding->budgetItems()->where('category_id', $this->catering->id)->value('planned_amount'))->toBe(12000.0);
});

it('lets the partner edit the budget but keeps strangers out', function () {
    $hakim = User::factory()->create();
    $this->wedding->addMember($hakim, WeddingRole::Partner);

    $this->actingAs($hakim)
        ->put(route('weddings.budget.update', $this->wedding), ['budget' => 40000])
        ->assertRedirect();

    expect((float) $this->wedding->fresh()->budget)->toBe(40000.0);

    $this->actingAs(User::factory()->create())
        ->put(route('weddings.budget.update', $this->wedding), ['budget' => 1])
        ->assertForbidden();
});

it('warns when bookings run over the wedding budget', function () {
    $vendor = Vendor::factory()->for($this->catering)->create();
    Package::factory()->for($vendor)->create();
    Booking::factory()->confirmed()->for($this->aina)->for($vendor)->create(['wedding_id' => $this->wedding->id, 'total_amount' => 35000]);

    $this->actingAs($this->aina)->get(route('budget.index'))->assertOk()->assertSee('Melebihi bajet');
});

it('charts spending by month and by category alongside the table', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $booking = Booking::factory()->confirmed()->for($this->aina)->for($vendor)->create([
        'wedding_id' => $this->wedding->id,
        'total_amount' => 8000,
        'created_at' => now()->subMonth(),
    ]);

    Payment::factory()->for($booking)->create([
        'status' => PaymentStatus::Paid,
        'amount' => 3000,
        'paid_at' => now()->subMonth(),
    ]);

    $this->actingAs($this->aina)->get(route('budget.index'))
        ->assertOk()
        ->assertSee('Komitmen mengikut bulan')
        ->assertSee('Bayaran mengikut bulan')
        ->assertSee('Perbelanjaan mengikut kategori')
        ->assertSee('RM8,000')
        ->assertSee('RM3,000');
});

it('shows an empty chart message rather than a blank box before any booking', function () {
    $this->actingAs($this->aina)->get(route('budget.index'))
        ->assertOk()
        ->assertSee('Belum ada tempahan untuk dipaparkan.')
        ->assertSee('Tempah vendor untuk melihat agihan perbelanjaan anda.');
});
