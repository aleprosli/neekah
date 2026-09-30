<?php

use App\Actions\PurgeCameraAlbum;
use App\Enums\CameraWishType;
use App\Jobs\BuildCameraExport;
use App\Models\CameraAlbum;
use App\Models\CameraWish;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->couple = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->couple)->create();
});

/** A voice recording as a browser would send it: WebM's first bytes, then anything. */
function voiceFile(string $head = "\x1A\x45\xDF\xA3"): UploadedFile
{
    return UploadedFile::fake()->createWithContent('ucapan.webm', $head.str_repeat("\0", 2048));
}

it('takes a written wish from a guest on every tier', function () {
    $album = CameraAlbum::factory()->for($this->wedding)->create();

    $this->postJson(route('camera.guest.wish', $album), ['message' => '<b>Selamat</b> pengantin baru!'])->assertOk();

    $wish = CameraWish::sole();
    expect($wish->type)->toBe(CameraWishType::Text)
        ->and($wish->message)->toBe('Selamat pengantin baru!')
        ->and($wish->camera_album_id)->toBe($album->id);
});

it('takes a voice wish on Pro only, checked by its first bytes', function () {
    $basic = CameraAlbum::factory()->for($this->wedding)->create();
    $pro = CameraAlbum::factory()->for($this->wedding)->pro()->create();

    $this->post(route('camera.guest.wish', $basic), ['audio' => voiceFile(), 'seconds' => 12], ['Accept' => 'application/json'])->assertForbidden();
    $this->post(route('camera.guest.wish', $pro), ['audio' => voiceFile('<?php echo 1;'), 'seconds' => 12], ['Accept' => 'application/json'])->assertJsonValidationErrors('audio');
    $this->post(route('camera.guest.wish', $pro), ['audio' => voiceFile(), 'seconds' => 999], ['Accept' => 'application/json'])->assertJsonValidationErrors('seconds');

    $this->post(route('camera.guest.wish', $pro), ['audio' => voiceFile(), 'seconds' => 12], ['Accept' => 'application/json'])->assertOk();

    $wish = CameraWish::sole();
    expect($wish->type)->toBe(CameraWishType::Voice)
        ->and($wish->mime)->toBe('audio/webm')
        ->and($wish->duration_seconds)->toBe(12)
        ->and($wish->audio_path)->toStartWith('camera/'.$pro->id.'/wishes/');
    Storage::disk('public')->assertExists($wish->audio_path);
});

it('takes no wish once the couple closes the album to guests', function () {
    $album = CameraAlbum::factory()->for($this->wedding)->create(['uploads_open' => false]);

    $this->postJson(route('camera.guest.wish', $album), ['message' => 'Tahniah'])->assertStatus(410);

    expect(CameraWish::count())->toBe(0);
});

it('shows the wishes to the couple only, who can delete one', function () {
    $album = CameraAlbum::factory()->for($this->wedding)->pro()->create();
    $voice = CameraWish::factory()->for($album, 'album')->voice()->create();
    Storage::disk('public')->put($voice->audio_path, 'audio');
    CameraWish::factory()->for($album, 'album')->create();

    $this->actingAs($this->couple)->getJson(route('camera.wishes', $album))->assertOk()->assertJsonCount(2, 'items');
    $this->actingAs($this->couple)->getJson(route('camera.wishes', [$album, 'type' => 'voice']))->assertOk()->assertJsonCount(1, 'items');

    $stranger = User::factory()->create();
    Wedding::factory()->for($stranger)->create();
    $this->actingAs($stranger)->getJson(route('camera.wishes', $album))->assertForbidden();
    $this->actingAs($stranger)->deleteJson(route('camera.wishes.destroy', [$album, $voice]))->assertForbidden();

    $this->actingAs($this->couple)->deleteJson(route('camera.wishes.destroy', [$album, $voice]))->assertOk();

    expect(CameraWish::whereKey($voice->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($voice->audio_path);
});

it('puts the wishes in the album ZIP, and deletes them with the album', function () {
    Notification::fake();
    $album = CameraAlbum::factory()->for($this->wedding)->pro()->create();
    CameraWish::factory()->for($album, 'album')->create(['guest_name' => 'Mak Long', 'message' => 'Semoga bahagia']);
    $voice = CameraWish::factory()->for($album, 'album')->voice()->create(['audio_path' => 'camera/'.$album->id.'/wishes/suara.webm']);
    Storage::disk('public')->put($voice->audio_path, 'audio');

    (new BuildCameraExport($album))->handle();

    $zip = new ZipArchive;
    $local = tempnam(sys_get_temp_dir(), 'zip');
    file_put_contents($local, Storage::disk('public')->get($album->fresh()->export_paths[0]));
    $zip->open($local);
    expect($zip->getFromName(__('pages.camera.export_wishes_file')))->toContain('Mak Long')->toContain('Semoga bahagia')
        ->and(collect(range(0, $zip->numFiles - 1))->map(fn (int $index) => $zip->getNameIndex($index))->filter(fn (string $name) => str_ends_with($name, '.webm'))->values()->all())
        ->toHaveCount(1)->each->toStartWith(__('pages.camera.export_voice_folder').'/');
    $zip->close();
    unlink($local);

    app(PurgeCameraAlbum::class)->handle($album->fresh());

    expect(CameraWish::count())->toBe(0);
    Storage::disk('public')->assertMissing($voice->audio_path);
});
