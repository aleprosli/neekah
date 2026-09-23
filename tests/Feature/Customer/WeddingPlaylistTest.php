<?php

use App\Enums\SongMoment;
use App\Enums\WeddingRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingSong;
use App\Support\SongSuggestions;

beforeEach(function () {
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create();
});

it('files each song under its moment, in the order the day runs', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.songs.store', $this->wedding), ['moment' => 'ending', 'title' => 'Lautan', 'artist' => 'Yuna'])
        ->assertRedirect();
    $this->actingAs($this->aina)
        ->post(route('weddings.songs.store', $this->wedding), ['moment' => 'akad', 'title' => 'Menamakanmu', 'notes' => 'Mula dari korus'])
        ->assertRedirect();

    expect(WeddingSong::where('moment', SongMoment::Akad)->sole()->notes)->toBe('Mula dari korus');

    $this->actingAs($this->aina)
        ->get(route('playlist.index'))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $moments = collect($props['moments']);

            return $moments->pluck('value')->all() === array_column(SongMoment::cases(), 'value')
                && $moments->firstWhere('value', 'akad')['songs'][0]['title'] === 'Menamakanmu'
                && $moments->firstWhere('value', 'ending')['songs'][0]['artist'] === 'Yuna'
                && $moments->firstWhere('value', 'entrance')['songs']->isEmpty()
                && count($props['suggestions']) === count(SongSuggestions::all());
        });
});

it('rejects a song with no title or a moment that does not exist', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.songs.store', $this->wedding), ['moment' => 'karaoke', 'title' => ''])
        ->assertSessionHasErrors(['moment', 'title']);

    expect(WeddingSong::count())->toBe(0);
});

it('lets the partner add and delete songs but keeps strangers out', function () {
    $hakim = User::factory()->create();
    $this->wedding->addMember($hakim, WeddingRole::Partner);
    $song = WeddingSong::factory()->for($this->wedding)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('weddings.songs.store', $this->wedding), ['moment' => 'akad', 'title' => 'Akad'])
        ->assertForbidden();
    $this->actingAs($stranger)
        ->delete(route('weddings.songs.destroy', [$this->wedding, $song]))
        ->assertForbidden();

    $this->actingAs($hakim)
        ->post(route('weddings.songs.store', $this->wedding), ['moment' => 'first_walk', 'title' => 'Perfect', 'artist' => 'Ed Sheeran'])
        ->assertRedirect();
    $this->actingAs($hakim)
        ->delete(route('weddings.songs.destroy', [$this->wedding, $song]))
        ->assertRedirect();

    expect(WeddingSong::pluck('title')->all())->toBe(['Perfect']);
});

it('will not delete a song through another wedding', function () {
    $other = WeddingSong::factory()->create();

    $this->actingAs($this->aina)
        ->delete(route('weddings.songs.destroy', [$this->wedding, $other]))
        ->assertNotFound();

    expect($other->fresh())->not->toBeNull();
});

it('offers every suggested song only once', function () {
    $keys = collect(SongSuggestions::all())->map(fn (array $song): string => mb_strtolower($song['title'].'|'.$song['artist']));

    expect($keys->duplicates())->toBeEmpty();
});
