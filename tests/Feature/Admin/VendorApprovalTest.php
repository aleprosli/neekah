<?php

use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\VendorStatusChanged;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('approves a pending vendor, promotes it to Verified and lists it publicly', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Studio Baharu']);

    $this->get('/')->assertDontSee('Studio Baharu');

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.status', $vendor), ['status' => 'approved'])
        ->assertRedirect();

    $vendor->refresh();

    expect($vendor->status)->toBe(VendorStatus::Approved)
        ->and($vendor->tier)->toBe(VendorTier::Verified)
        ->and($vendor->approved_at)->not->toBeNull()
        ->and((float) $vendor->score)->toBeGreaterThan(0);

    $this->get('/')->assertSee('Studio Baharu');
    $this->get(route('vendors.show', $vendor))->assertOk();
});

it('suspends an approved vendor and hides it from the marketplace', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['name' => 'Suspended Studio']);

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.status', $vendor), ['status' => 'suspended'])
        ->assertRedirect();

    expect($vendor->fresh()->status)->toBe(VendorStatus::Suspended);

    $this->get('/')->assertDontSee('Suspended Studio');
    $this->get(route('vendors.show', $vendor))->assertNotFound();
});

it('rejects an unknown status value', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.status', $vendor), ['status' => 'banana'])
        ->assertSessionHasErrors('status');

    expect($vendor->fresh()->status)->toBe(VendorStatus::Pending);
});

it('pins a tier the admin locks and recalculates the score', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->put(route('admin.vendors.tier', $vendor), ['tier' => 'recommended', 'tier_locked' => 1])
        ->assertRedirect();

    $vendor->refresh();

    expect($vendor->tier)->toBe(VendorTier::Recommended)
        ->and($vendor->tier_locked)->toBeTrue()
        ->and((float) $vendor->score)->toBe($vendor->calculateScore());
});

it('recomputes an unlocked tier from the vendor metrics instead of trusting the form', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->put(route('admin.vendors.tier', $vendor), ['tier' => 'recommended'])
        ->assertRedirect();

    // No completed bookings or reviews yet, so the engine puts them back at Verified.
    expect($vendor->fresh()->tier)->toBe(VendorTier::Verified)
        ->and($vendor->fresh()->tier_locked)->toBeFalse();
});

it('shows a vendor detail page with owner, packages and controls', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create();

    $props = $this->actingAs($this->admin)
        ->get(route('admin.vendors.show', $vendor))
        ->assertOk()
        ->assertSee($vendor->name)
        ->viewData('props');

    expect(collect($props['facts'])->firstWhere('label', 'Pemilik')['detail'])->toContain($vendor->user->email)
        ->and(collect($props['facts'])->pluck('label'))->toContain('Vendor Score')
        ->and($props['vendor']['tier_url'])->toBe(route('admin.vendors.tier', $vendor))
        // A pending vendor can be approved from here, and is not offered its own status.
        ->and(collect($props['statusActions'])->pluck('value'))->toContain('approved')
        ->not->toContain('pending');
});

it('makes every status change ask first, naming the vendor and what happens', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Studio Baharu']);

    // The list: the row action carries its own question.
    $this->actingAs($this->admin)
        ->getJson(route('admin.vendors.data'))
        ->assertOk()
        ->assertJsonPath('data.0.action.confirm.title', 'Luluskan Studio Baharu?')
        ->assertJsonPath('data.0.action.fields.status', VendorStatus::Approved->value)
        ->assertJsonPath('data.0.action.confirm.confirmLabel', 'Ya, luluskan');

    // The detail page: one question per status, none of them generic.
    $this->actingAs($this->admin)
        ->get(route('admin.vendors.show', $vendor))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $actions = collect($props['statusActions']);

            return $actions->every(fn (array $action): bool => filled($action['confirm_title']) && filled($action['confirm_message']))
                && $actions->firstWhere('value', VendorStatus::Approved->value)['confirm_title'] === 'Luluskan Studio Baharu?'
                && $actions->firstWhere('value', VendorStatus::Suspended->value)['tone'] === 'danger';
        });
});

it('offers suspension, not approval, for a vendor already approved', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['name' => 'Studio Lama']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.vendors.data'))
        ->assertOk()
        ->assertJsonPath('data.0.action.label', 'Gantung')
        ->assertJsonPath('data.0.action.confirm.title', 'Gantung Studio Lama?')
        ->assertJsonPath('data.0.action.confirm.tone', 'danger');

    expect($vendor->fresh()->status)->toBe(VendorStatus::Approved);
});

it('approves a whole batch the admin ticked, emailing each one', function () {
    Notification::fake();

    $first = Vendor::factory()->pending()->for(Category::first())->create();
    $second = Vendor::factory()->pending()->for(Category::first())->create();
    $untouched = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.bulk-status'), ['status' => 'approved', 'ids' => [$first->id, $second->id]])
        ->assertRedirect()
        ->assertSessionHas('status', '2 vendor kini Diluluskan.');

    expect($first->fresh()->status)->toBe(VendorStatus::Approved)
        ->and($first->fresh()->tier)->toBe(VendorTier::Verified)
        ->and($second->fresh()->status)->toBe(VendorStatus::Approved)
        ->and($untouched->fresh()->status)->toBe(VendorStatus::Pending);

    Notification::assertSentTo([$first->user, $second->user], VendorStatusChanged::class);
    Notification::assertNotSentTo($untouched->user, VendorStatusChanged::class);
});

it('leaves a vendor already in that status alone, so nobody is emailed twice', function () {
    Notification::fake();

    $already = Vendor::factory()->for(Category::first())->create(['status' => VendorStatus::Approved]);

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.bulk-status'), ['status' => 'approved', 'ids' => [$already->id]])
        ->assertSessionHas('status', 'Tiada perubahan: vendor yang dipilih sudah Diluluskan.');

    Notification::assertNothingSent();
});

it('refuses a batch with no vendors, an unknown status or an id that is not a vendor', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.bulk-status'), ['status' => 'approved', 'ids' => []])
        ->assertSessionHasErrors(['ids' => 'Pilih sekurang-kurangnya satu vendor.']);

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.bulk-status'), ['status' => 'banana', 'ids' => [$vendor->id]])
        ->assertSessionHasErrors('status');

    $this->actingAs($this->admin)
        ->post(route('admin.vendors.bulk-status'), ['status' => 'approved', 'ids' => [$vendor->id, 999999]])
        ->assertSessionHasErrors('ids.1');

    expect($vendor->fresh()->status)->toBe(VendorStatus::Pending);
});

it('keeps batch approval to admins', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.vendors.bulk-status'), ['status' => 'approved', 'ids' => [$vendor->id]])
        ->assertForbidden();

    expect($vendor->fresh()->status)->toBe(VendorStatus::Pending);
});

it('shows at a glance how far each vendor got with their setup', function () {
    $ready = Vendor::factory()->pending()->for(Category::first())->create(['logo' => 'vendors/logo.webp']);
    Package::factory()->for($ready)->create(['is_active' => true]);
    PortfolioItem::factory()->count(3)->for($ready)->create();

    $thin = Vendor::factory()->pending()->for(Category::first())->create([
        'tagline' => null,
        'logo' => null,
    ]);
    PortfolioItem::factory()->count(2)->for($thin)->create();

    $rows = collect($this->actingAs($this->admin)->getJson(route('admin.vendors.data'))->assertOk()->json('data'))
        ->keyBy('id');

    expect($rows[$ready->id]['setup'])->toContain('Lengkap')
        ->toContain('1 pakej')
        ->toContain('3 gambar');

    // What is missing is named, so the admin knows what to ask the vendor for.
    expect($rows[$thin->id]['setup'])->toContain('0 / 4 siap')
        ->toContain('Tiada pakej aktif')
        ->toContain('Perlu sekurang-kurangnya 3 gambar portfolio');
});

it('narrows the list to vendors who finished their setup, alongside the status', function () {
    $ready = Vendor::factory()->pending()->for(Category::first())->create();
    Package::factory()->for($ready)->create(['is_active' => true]);
    PortfolioItem::factory()->count(3)->for($ready)->create();

    $thin = Vendor::factory()->pending()->for(Category::first())->create();
    $approvedAndReady = Vendor::factory()->for(Category::first())->create();
    Package::factory()->for($approvedAndReady)->create(['is_active' => true]);
    PortfolioItem::factory()->count(3)->for($approvedAndReady)->create();

    $ids = fn (array $query): array => collect($this->actingAs($this->admin)
        ->getJson(route('admin.vendors.data', $query))
        ->assertOk()
        ->json('data'))->pluck('id')->sort()->values()->all();

    expect($ids(['setup' => 'complete']))->toBe(collect([$ready->id, $approvedAndReady->id])->sort()->values()->all())
        ->and($ids(['setup' => 'partial']))->toBe([$thin->id])
        // The two chips narrow each other rather than replacing one another.
        ->and($ids(['status' => 'pending', 'setup' => 'complete']))->toBe([$ready->id])
        ->and($ids(['status' => 'pending', 'setup' => 'partial']))->toBe([$thin->id]);
});

it('counts each chip against the other filters, so a count never sits above an empty table', function () {
    // One pending vendor with nothing filled in, one approved and complete.
    $pendingThin = Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Baru Daftar']);

    $approvedReady = Vendor::factory()->for(Category::first())->create(['name' => 'Studio Siap']);
    Package::factory()->for($approvedReady)->create(['is_active' => true]);
    PortfolioItem::factory()->count(3)->for($approvedReady)->create();

    // Nothing chosen: both groups count the whole list.
    $this->actingAs($this->admin)
        ->getJson(route('admin.vendors.data'))
        ->assertOk()
        ->assertJsonPath('filters.setup.complete', 1)
        ->assertJsonPath('filters.setup.partial', 1)
        ->assertJsonPath('filters.status.pending', 1);

    // Asking for the pending ones: no complete vendor is pending, and the chip
    // says so instead of counting the approved one too.
    $this->actingAs($this->admin)
        ->getJson(route('admin.vendors.data', ['status' => 'pending']))
        ->assertOk()
        ->assertJsonPath('filters.setup.complete', 0)
        ->assertJsonPath('filters.setup.partial', 1)
        ->assertJsonCount(1, 'data');

    // And the status chips count within the chosen setup state.
    $this->actingAs($this->admin)
        ->getJson(route('admin.vendors.data', ['setup' => 'complete']))
        ->assertOk()
        ->assertJsonPath('filters.status.approved', 1)
        ->assertJsonPath('filters.status.pending', null);

    // The search narrows the counts too.
    $this->actingAs($this->admin)
        ->getJson(route('admin.vendors.data', ['search' => 'Baru Daftar']))
        ->assertOk()
        ->assertJsonPath('filters.status.pending', 1)
        ->assertJsonPath('filters.setup.complete', 0);
});

it('exports the list as a csv with the phone numbers, following the filters', function () {
    $ready = Vendor::factory()->for(Category::first())->create([
        'name' => 'Studio Siap',
        'phone' => '012-345 6789',
        'whatsapp' => '019-888 7777',
        'city' => 'Alor Setar',
    ]);
    Package::factory()->for($ready)->create(['is_active' => true]);
    PortfolioItem::factory()->count(3)->for($ready)->create();

    $thin = Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Baru Daftar', 'phone' => '011-222 3333']);

    $csv = fn (array $query): string => $this->actingAs($this->admin)
        ->get(route('admin.vendors.export', $query))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->streamedContent();

    // Everything, with the columns the admin came for.
    $all = $csv([]);
    expect($all)->toContain('Nama,Kategori,Telefon,WhatsApp')
        ->toContain('Studio Siap')
        ->toContain('012-345 6789')
        ->toContain('019-888 7777')
        ->toContain('Baru Daftar')
        ->toContain('011-222 3333');

    // The same chips as the table, so a filtered export holds only those rows.
    expect($csv(['setup' => 'complete']))->toContain('Studio Siap')->not->toContain('Baru Daftar');
    expect($csv(['setup' => 'partial']))->toContain('Baru Daftar')->not->toContain('Studio Siap');
    expect($csv(['status' => 'pending']))->toContain('Baru Daftar')->not->toContain('Studio Siap');
    expect($csv(['search' => 'Alor Setar']))->toContain('Studio Siap')->not->toContain('Baru Daftar');
});

it('names the export file after the filters, and keeps it to admins', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.vendors.export', ['status' => 'pending', 'setup' => 'partial']))
        ->assertOk()
        ->assertDownload('neekah-vendor-pending-setup-belum-lengkap-'.now()->format('Y-m-d').'.csv');

    $this->actingAs(User::factory()->create())
        ->get(route('admin.vendors.export'))
        ->assertForbidden();
});
