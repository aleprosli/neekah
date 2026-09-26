<?php

use App\Enums\WeddingRole;
use App\Http\Controllers\Customer\CameraController;
use App\Jobs\BuildCameraExport;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\CameraExportReady;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->couple = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->couple)->create();
    $this->album = CameraAlbum::factory()->for($this->wedding)->create();
});

/** A ready photo in the album, with its file on disk. */
function storedPhoto(CameraAlbum $album, array $attributes = []): CameraMedia
{
    $media = CameraMedia::factory()->for($album, 'album')->create(['path' => 'camera/'.$album->id.'/'.Str::random(10).'.webp', 'bytes' => 11, ...$attributes]);
    Storage::disk('public')->put($media->path, 'photo-bytes');

    return $media;
}

it('saves what guests see, and a new passcode signs every guest out', function () {
    $this->actingAs($this->couple)->put(route('camera.update', $this->album), [
        'title' => 'Majlis Aina & Hakim', 'welcome_message' => 'Kongsi detik anda!',
        'guests_can_view' => '0', 'uploads_open' => '1', 'passcode' => 'KAHWIN',
    ])->assertSessionHasNoErrors();

    $album = $this->album->fresh();
    expect($album->title)->toBe('Majlis Aina & Hakim')
        ->and($album->guests_can_view)->toBeFalse()
        ->and(Hash::check('KAHWIN', $album->passcode_hash))->toBeTrue()
        ->and($album->passcode_version)->toBe(1);

    $this->actingAs($this->couple)->put(route('camera.update', $this->album), ['guests_can_view' => '1', 'uploads_open' => '1', 'remove_passcode' => '1']);
    expect($this->album->fresh()->isRestricted())->toBeFalse();
});

it('changes the guest address so the old QR stops working', function () {
    $old = $this->album->token;

    $this->actingAs($this->couple)->post(route('camera.rotate', $this->album))->assertRedirect();

    expect($this->album->fresh()->token)->not->toBe($old);
    $this->get('/k/'.$old)->assertNotFound();
});

it('lists the album for the couple and their partner, not for anyone else', function () {
    storedPhoto($this->album);
    CameraMedia::factory()->for($this->album, 'album')->create(['type' => 'video', 'path' => 'camera/v.mp4']);
    $partner = User::factory()->create();
    $this->wedding->addMember($partner, WeddingRole::Partner);

    $this->actingAs($partner)->getJson(route('camera.media', $this->album))->assertOk()->assertJsonCount(2, 'items');
    $this->actingAs($this->couple)->getJson(route('camera.media', [$this->album, 'type' => 'video']))->assertOk()->assertJsonCount(1, 'items');

    $stranger = User::factory()->create();
    Wedding::factory()->for($stranger)->create();
    $this->actingAs($stranger)->getJson(route('camera.media', $this->album))->assertForbidden();
});

it('deletes many at once, only from this album, with the files and counters', function () {
    $this->album->update(['photos_count' => 2, 'bytes_used' => 22]);
    [$one, $two] = [storedPhoto($this->album), storedPhoto($this->album)];
    $elsewhere = storedPhoto(CameraAlbum::factory()->create());

    $this->actingAs($this->couple)->postJson(route('camera.media.bulk', $this->album), ['ids' => [$one->id, $two->id, $elsewhere->id]])
        ->assertOk()->assertJson(['deleted' => 2]);

    expect(CameraMedia::whereKey($elsewhere->id)->exists())->toBeTrue()
        ->and($this->album->fresh()->photos_count)->toBe(0)
        ->and($this->album->fresh()->bytes_used)->toBe(0);
    Storage::disk('public')->assertMissing($one->path);
});

it('builds the album into ZIP parts the couple can download, and tells them', function () {
    Notification::fake();
    BuildCameraExport::$partBytes = 15;
    $photos = [storedPhoto($this->album), storedPhoto($this->album)];

    $this->actingAs($this->couple)->post(route('camera.export', $this->album))->assertRedirect();

    $album = $this->album->fresh();
    expect($album->export_paths)->toHaveCount(2)
        ->and($album->exported_at)->not->toBeNull();

    $zip = new ZipArchive;
    $local = tempnam(sys_get_temp_dir(), 'zip');
    file_put_contents($local, Storage::disk('public')->get($album->export_paths[0]));
    $zip->open($local);
    expect($zip->numFiles)->toBe(1)
        ->and($zip->getNameIndex(0))->toStartWith('Gambar/');
    $zip->close();
    unlink($local);

    $this->actingAs($this->couple)->get(route('camera.export.download', [$this->album, 2]))->assertRedirect();
    $this->actingAs($this->couple)->get(route('camera.export.download', [$this->album, 3]))->assertNotFound();
    Notification::assertSentTo($this->couple, CameraExportReady::class);

    BuildCameraExport::$partBytes = 2 * 1024 ** 3;
});

it('lists every album of the wedding, each opening its own page', function () {
    $second = CameraAlbum::factory()->for($this->wedding)->pro()->create(['title' => 'Majlis Bertandang']);

    $props = $this->actingAs($this->couple)->get(route('camera.index'))->assertOk()->viewData('props');

    expect(collect($props['albums'])->pluck('show_url')->all())->toBe([route('camera.album', $second), route('camera.album', $this->album)])
        ->and($props['albums'][0]['title'])->toBe('Majlis Bertandang')
        ->and($props['albums'][0]['allows_voice'])->toBeTrue();
});

it('shows the couple an album\'s settings and links on its own page, and keeps other couples out', function () {
    $props = $this->actingAs($this->couple)->get(route('camera.album', $this->album))->assertOk()->viewData('props');

    expect($props['album']['urls']['update'])->toBe(route('camera.update', $this->album))
        ->and($props['album']['export']['parts'])->toBe([])
        ->and($props['upgrade']['tier'])->toBe('pro');

    $stranger = User::factory()->create();
    Wedding::factory()->for($stranger)->create();
    $this->actingAs($stranger)->get(route('camera.album', $this->album))->assertForbidden();
    $this->actingAs($stranger)->put(route('camera.update', $this->album), ['title' => 'Bukan saya'])->assertForbidden();
});

it('answers 304 while nothing in the album changed', function () {
    storedPhoto($this->album);
    $this->album->update(['photos_count' => 1]);

    $first = $this->actingAs($this->couple)->getJson(route('camera.media', $this->album))->assertOk();
    $tag = $first->headers->get('ETag');

    $this->actingAs($this->couple)->getJson(route('camera.media', $this->album), ['If-None-Match' => $tag])->assertStatus(304);

    $this->album->update(['photos_count' => 2]);
    $this->actingAs($this->couple)->getJson(route('camera.media', $this->album), ['If-None-Match' => $tag])->assertOk();
});

it('zips only the chosen files of this album, straight away', function () {
    $one = storedPhoto($this->album);
    storedPhoto($this->album);
    $elsewhere = storedPhoto(CameraAlbum::factory()->create());

    $response = $this->actingAs($this->couple)->post(route('camera.export.selected', $this->album), ['ids' => [$one->id, $elsewhere->id]])->assertOk();

    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());
    expect($zip->numFiles)->toBe(1)
        ->and($zip->getNameIndex(0))->toEndWith('-'.$one->id.'.webp');
    $zip->close();
});

it('refuses a chosen set too big to zip while the couple waits', function () {
    $one = storedPhoto($this->album, ['bytes' => CameraController::SELECTED_MAX_BYTES + 1]);

    $this->actingAs($this->couple)->post(route('camera.export.selected', $this->album), ['ids' => [$one->id]])->assertSessionHasErrors('ids');
});

it('hands the card designer the QR address and remembers the chosen design', function () {
    $props = $this->actingAs($this->couple)->get(route('camera.album', $this->album))->assertOk()->viewData('props');

    expect($props['album']['print']['url'])->toBe(route('camera.show', $this->album))
        ->and($props['album']['print']['card'])->toBeNull();

    $this->actingAs($this->couple)->putJson(route('camera.design', $this->album), ['design' => 'bunga', 'size' => 'a5', 'per_sheet' => 2, 'headline' => 'Kongsi'])->assertOk();
    $this->actingAs($this->couple)->putJson(route('camera.design', $this->album), ['design' => 'neon', 'size' => 'a5', 'per_sheet' => 2])->assertJsonValidationErrors('design');

    expect($this->album->fresh()->qr_design)->toBe('bunga')
        ->and($this->album->fresh()->qr_options['per_sheet'])->toBe(2);
});
