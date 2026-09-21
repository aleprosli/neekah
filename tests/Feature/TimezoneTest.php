<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingSite;
use App\Models\WeddingTimelineItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('reads and writes in Kuala Lumpur', function () {
    expect(config('app.timezone'))->toBe('Asia/Kuala_Lumpur')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Kuala_Lumpur');
});

it('shows a person the hour they did the thing', function () {
    $user = User::factory()->create();

    // A photo uploaded at 2:27am on the 22nd used to read "6:27 PM" on the
    // 21st, because the app stored and formatted UTC while everyone reading it
    // was on +08.
    $this->travelTo(Carbon::parse('2026-09-22 02:27:09', 'Asia/Kuala_Lumpur'));

    $booking = Booking::factory()
        ->for($user)
        ->for(Vendor::factory()->for(Category::factory()))
        ->create();

    expect($booking->created_at->translatedFormat('j M Y, g:i A'))->toBe('22 Sep 2026, 2:27 AM');
});

it('moves the instants already stored, and leaves the calendar alone', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create(['event_date' => '2027-02-14']);
    $site = WeddingSite::factory()->for($wedding)->create([
        'event_date' => '2027-02-14',
        'starts_at' => '11:00',
        'ends_at' => '16:00',
    ]);
    $item = WeddingTimelineItem::factory()->for($wedding)->create(['starts_at' => '11:00']);

    // Write the rows as the old UTC application would have.
    DB::table('weddings')->where('id', $wedding->id)->update(['created_at' => '2026-09-21 18:27:09']);

    $migration = require database_path('migrations/2026_09_22_024135_move_stored_times_to_kuala_lumpur.php');
    $migration->up();

    expect(DB::table('weddings')->where('id', $wedding->id)->value('created_at'))
        ->toStartWith('2026-09-22 02:27:09');

    // A wedding on the 14th is on the 14th, and it still starts at 11am.
    expect(DB::table('weddings')->where('id', $wedding->id)->value('event_date'))->toStartWith('2027-02-14')
        ->and(DB::table('wedding_sites')->where('id', $site->id)->value('event_date'))->toStartWith('2027-02-14')
        ->and(DB::table('wedding_sites')->where('id', $site->id)->value('starts_at'))->toStartWith('11:00')
        ->and(DB::table('wedding_sites')->where('id', $site->id)->value('ends_at'))->toStartWith('16:00')
        ->and(DB::table('wedding_timeline_items')->where('id', $item->id)->value('starts_at'))->toStartWith('11:00');
});

it('puts every instant back if the move has to be undone', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    DB::table('weddings')->where('id', $wedding->id)->update(['created_at' => '2026-09-21 18:27:09']);

    $migration = require database_path('migrations/2026_09_22_024135_move_stored_times_to_kuala_lumpur.php');
    $migration->up();
    $migration->down();

    expect(DB::table('weddings')->where('id', $wedding->id)->value('created_at'))
        ->toStartWith('2026-09-21 18:27:09');
});

it('shifts each column once, however many databases the server holds', function () {
    // Schema::getTables() with no argument reaches every schema the connection
    // can see. On a MySQL server holding several databases that returned the
    // same table once per schema — 399 columns where there are 87 — and the
    // rehearsal moved every row sixteen hours instead of eight.
    $migration = require database_path('migrations/2026_09_22_024135_move_stored_times_to_kuala_lumpur.php');

    $columns = (new ReflectionMethod($migration, 'timestampColumns'))->invoke($migration);
    $named = array_map(fn (array $pair): string => $pair[0].'.'.$pair[1], $columns);

    expect($named)->toBe(array_values(array_unique($named)))
        ->and($named)->not->toBeEmpty();
});

it('leaves a date and a time column out of the move entirely', function () {
    $migration = require database_path('migrations/2026_09_22_024135_move_stored_times_to_kuala_lumpur.php');
    $columns = (new ReflectionMethod($migration, 'timestampColumns'))->invoke($migration);
    $named = array_map(fn (array $pair): string => $pair[0].'.'.$pair[1], $columns);

    // A wedding on the 14th is on the 14th, and it still starts at 11am.
    expect($named)
        ->not->toContain('weddings.event_date')
        ->not->toContain('wedding_sites.starts_at')
        ->not->toContain('wedding_timeline_items.starts_at')
        ->toContain('weddings.created_at');
});
