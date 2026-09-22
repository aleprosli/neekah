<?php

use App\Models\CardMusicTrack;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Database\Seeders\SiteTemplateSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
});

it('uploads a track and offers it to couples', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.card-music.store'), [
            'title' => 'Sepohon Kayu',
            'artist' => 'Trad.',
            'audio' => UploadedFile::fake()->create('sepohon.mp3', 800, 'audio/mpeg'),
            'is_active' => 1,
        ])
        ->assertRedirect();

    $track = CardMusicTrack::sole();

    expect($track->title)->toBe('Sepohon Kayu')
        ->and($track->is_active)->toBeTrue()
        ->and($track->label())->toBe('Sepohon Kayu — Trad.');

    Storage::disk('public')->assertExists($track->path);
});

it('refuses anything that is not audio, and anything over the limit', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.card-music.store'), [
            'title' => 'Bukan lagu',
            'audio' => UploadedFile::fake()->create('lagu.exe', 50, 'application/x-msdownload'),
        ])
        ->assertSessionHasErrors('audio');

    $this->actingAs($this->admin)
        ->post(route('admin.card-music.store'), [
            'title' => 'Terlalu besar',
            'audio' => UploadedFile::fake()->create('besar.mp3', 9000, 'audio/mpeg'),
        ])
        ->assertSessionHasErrors('audio');

    expect(CardMusicTrack::count())->toBe(0);
});

it('replaces the file of a track without leaving the old one on disk', function () {
    $this->actingAs($this->admin)->post(route('admin.card-music.store'), [
        'title' => 'Satu',
        'audio' => UploadedFile::fake()->create('satu.mp3', 300, 'audio/mpeg'),
    ]);

    $track = CardMusicTrack::sole();
    $first = $track->path;

    $this->actingAs($this->admin)
        ->put(route('admin.card-music.update', $track), [
            'title' => 'Satu',
            'audio' => UploadedFile::fake()->create('dua.mp3', 300, 'audio/mpeg'),
        ])
        ->assertRedirect();

    expect($track->fresh()->path)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
});

it('refuses to delete a track a card is playing, and deletes an unused one with its file', function () {
    $this->seed(SiteTemplateSeeder::class);
    $inUse = CardMusicTrack::factory()->create();
    $spare = CardMusicTrack::factory()->create();
    Storage::disk('public')->put($spare->path, 'audio');
    WeddingSite::factory()->create(['music_track_id' => $inUse->id, 'music_enabled' => true]);

    // Silencing a card nobody warned is worse than keeping a row nobody offers.
    $this->actingAs($this->admin)->delete(route('admin.card-music.destroy', $inUse))->assertSessionHasErrors('track');
    $this->actingAs($this->admin)->delete(route('admin.card-music.destroy', $spare))->assertRedirect();

    expect(CardMusicTrack::pluck('id')->all())->toBe([$inUse->id]);
    Storage::disk('public')->assertMissing($spare->path);
});

it('keeps a track off the editor once it is switched off', function () {
    $this->seed(SiteTemplateSeeder::class);
    $offered = CardMusicTrack::factory()->create(['title' => 'Ditawarkan']);
    CardMusicTrack::factory()->create(['title' => 'Ditarik', 'is_active' => false]);

    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    WeddingSite::factory()->create(['wedding_id' => $wedding->id]);

    $props = $this->actingAs($couple)->get(route('site.edit'))->assertOk()->viewData('props');

    expect(collect($props['tracks'])->pluck('id')->all())->toBe([$offered->id]);
});

it('lists the library with what each track is used by', function () {
    $this->seed(SiteTemplateSeeder::class);
    $track = CardMusicTrack::factory()->create(['title' => 'Sepohon Kayu']);
    WeddingSite::factory()->create(['music_track_id' => $track->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.card-music.index'))
        ->assertOk()
        ->assertSee('Sepohon Kayu')
        ->assertViewHas('props', fn (array $props): bool => $props['tracks'][0]['cards'] === 1);
});

it('keeps a couple out of the music library', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.card-music.index'))->assertForbidden();
});
