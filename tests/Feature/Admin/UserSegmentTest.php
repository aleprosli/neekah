<?php

use App\Enums\UserRole;
use App\Enums\UserSegment;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use App\Models\WeddingSite;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

/**
 * The emails a segment returns, through the endpoint the table actually calls.
 *
 * @return array<int, string>
 */
function segmentEmails(UserSegment $segment): array
{
    return collect(test()->actingAs(test()->admin)
        ->getJson(route('admin.users.data', ['segment' => $segment->value]))
        ->assertOk()
        ->json('data'))
        ->pluck('email')
        ->sort()
        ->values()
        ->all();
}

it('separates vendors who finished their setup from those who did not', function () {
    $ready = Vendor::factory()->create();
    Package::factory()->for($ready)->create();
    PortfolioItem::factory()->count(3)->for($ready)->create();

    $thin = Vendor::factory()->create(['tagline' => null]);
    Package::factory()->for($thin)->create();
    PortfolioItem::factory()->count(3)->for($thin)->create();

    $noPictures = Vendor::factory()->create();
    Package::factory()->for($noPictures)->create();

    expect(segmentEmails(UserSegment::VendorSetupComplete))->toBe([$ready->user->email]);
    expect(segmentEmails(UserSegment::VendorSetupPending))
        ->toBe(collect([$thin->user->email, $noPictures->user->email])->sort()->values()->all());
});

it('counts a vendor account with no profile row as unfinished', function () {
    $stranded = User::factory()->vendor()->create();

    expect(segmentEmails(UserSegment::VendorSetupPending))->toBe([$stranded->email]);
});

it('finds couples who registered without ever creating a majlis', function () {
    $idle = User::factory()->create();
    $busy = User::factory()->create();
    Wedding::factory()->for($busy)->create();

    $partner = User::factory()->create();
    Wedding::factory()->for(User::factory())->create()->addMember($partner);

    expect(segmentEmails(UserSegment::CoupleNoWedding))->toBe([$idle->email]);
});

it('finds couples whose majlis has no digital card, and leaves out those with no majlis', function () {
    $withoutCard = User::factory()->create();
    Wedding::factory()->for($withoutCard)->create();

    $withCard = User::factory()->create();
    WeddingSite::factory()->for(Wedding::factory()->for($withCard)->create())->create();

    User::factory()->create();

    expect(segmentEmails(UserSegment::CoupleNoCard))->toBe([$withoutCard->email]);
});

it('finds couples planning alone, but not those whose invitation is still open', function () {
    $alone = User::factory()->create();
    Wedding::factory()->for($alone)->create();

    $joined = User::factory()->create();
    Wedding::factory()->for($joined)->create()->addMember(User::factory()->create());

    $waiting = User::factory()->create();
    WeddingInvitation::factory()->for(Wedding::factory()->for($waiting)->create())->create([
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $expired = User::factory()->create();
    WeddingInvitation::factory()->for(Wedding::factory()->for($expired)->create())->create([
        'expires_at' => now()->subDay(),
        'accepted_at' => null,
    ]);

    expect(segmentEmails(UserSegment::CoupleNoPartner))
        ->toBe(collect([$alone->email, $expired->email])->sort()->values()->all());
});

it('shows every segment on the user page, each with its own count and definition', function () {
    // One couple with a majlis and nothing else sits in two segments at once:
    // no card, and no partner. The chips must not have to agree.
    Wedding::factory()->for(User::factory()->create())->create();
    User::factory()->create();

    $page = $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();

    // The chips are the table's own filters, so they travel as its props.
    $group = collect($page->viewData('filters'))->firstWhere('key', 'segment');

    expect(collect($group['options'])->pluck('label')->all())
        ->toBe(collect(UserSegment::cases())->map(fn (UserSegment $segment): string => $segment->label())->all())
        ->and(collect($group['options'])->pluck('description')->all())
        ->toBe(collect(UserSegment::cases())->map(fn (UserSegment $segment): string => $segment->description())->all());

    $counts = collect($group['options'])->mapWithKeys(
        fn (array $option): array => [$option['value'] => (int) str_replace(',', '', $option['count'])],
    );

    expect($counts[UserSegment::CoupleNoWedding->value])->toBe(1)
        ->and($counts[UserSegment::CoupleNoCard->value])->toBe(1)
        ->and($counts[UserSegment::CoupleNoPartner->value])->toBe(1)
        ->and($counts[UserSegment::VendorSetupComplete->value])->toBe(0);
});

it('tells the table which account still needs what', function () {
    $vendor = Vendor::factory()->create();
    Package::factory()->for($vendor)->create();

    $row = collect($this->actingAs($this->admin)
        ->getJson(route('admin.users.data', ['segment' => UserSegment::VendorSetupPending->value]))
        ->json('data'))
        ->firstWhere('email', $vendor->user->email);

    expect($row['progress'])->toContain('Perlu pakej &amp; gambar')
        ->and($row['phone'])->toBe($vendor->user->phone ?: '—');
});

it('ignores a role filter while a segment is chosen, so the two cannot contradict', function () {
    $alone = User::factory()->create();
    Wedding::factory()->for($alone)->create();

    $rows = $this->actingAs($this->admin)
        ->getJson(route('admin.users.data', [
            'segment' => UserSegment::CoupleNoPartner->value,
            'role' => UserRole::Vendor->value,
        ]))
        ->assertOk()
        ->json('data');

    expect(collect($rows)->pluck('email')->all())->toBe([$alone->email]);
});
