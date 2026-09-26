<?php

use App\Actions\StoreOptimizedImage;
use App\Enums\CameraMediaStatus;
use App\Enums\CameraTier;
use App\Jobs\ProcessCameraMedia;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Support\Camera\CameraUploadTarget;
use App\Support\CameraSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('public');
    $this->album = CameraAlbum::factory()->create();
});

/** A real JPEG of the given size, as bytes. */
function jpegBytes(int $width = 3000, int $height = 2000): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 90));
    ob_start();
    imagejpeg($image, null, 85);

    return (string) ob_get_clean();
}

/**
 * A guest sends one file the way the phone does: reserve, PUT, complete.
 */
function guestUpload(CameraAlbum $album, string $bytes, string $type = 'photo', string $mime = 'image/jpeg', ?int $seconds = null): TestResponse
{
    $reserved = test()->postJson(route('camera.upload.reserve', $album), ['type' => $type, 'mime' => $mime, 'bytes' => strlen($bytes), 'seconds' => $seconds]);

    if ($reserved->status() !== 200) {
        return $reserved;
    }

    test()->call('PUT', $reserved->json('target.url'), [], [], [], ['CONTENT_TYPE' => $mime, 'HTTP_X_CSRF_TOKEN' => csrf_token()], $bytes)->assertNoContent();

    return test()->postJson($reserved->json('complete'));
}

it('opens the guest page with no account, and says when an album has closed', function () {
    $this->get(route('camera.show', $this->album))->assertOk()->assertSee('data-vue="camera-guest-page"', false);

    $this->album->update(['expires_at' => now()->subDay()]);
    $this->get(route('camera.show', $this->album))->assertOk()->assertSee(__('pages.camera.guest_closed'));
    $this->getJson(route('camera.gallery', $this->album))->assertStatus(410);
});

it('takes a photo into the album at the tier size, with its thumbnail', function () {
    guestUpload($this->album, jpegBytes())->assertOk();

    $media = CameraMedia::sole();
    $album = $this->album->fresh();

    expect($media->status)->toBe(CameraMediaStatus::Ready)
        ->and(max($media->width, $media->height))->toBeLessThanOrEqual(1600)
        ->and($album->photos_count)->toBe(1)
        ->and($album->reserved_count)->toBe(0)
        ->and($album->bytes_reserved)->toBe(0)
        ->and($album->bytes_used)->toBe($media->bytes);

    Storage::disk('public')->assertExists($media->path);
    Storage::disk('public')->assertExists(StoreOptimizedImage::thumbnailPath($media->path));
    expect(Storage::disk('public')->allFiles('camera/'.$album->id.'/incoming'))->toBe([]);
});

it('draws a light grid thumbnail and a display copy, so looking never downloads the original', function () {
    $this->album->update(['tier' => CameraTier::Pro]);

    guestUpload($this->album, jpegBytes(3000, 2000))->assertOk();

    $media = CameraMedia::sole();
    $disk = Storage::disk('public');
    [$thumbWidth] = getimagesizefromstring($disk->get(StoreOptimizedImage::thumbnailPath($media->path)));
    [$displayWidth] = getimagesizefromstring($disk->get($media->display_path));

    expect($media->display_path)->toBe(StoreOptimizedImage::displayPath($media->path))
        ->and($thumbWidth)->toBe(ProcessCameraMedia::THUMBNAIL_WIDTH)
        ->and($displayWidth)->toBe(ProcessCameraMedia::DISPLAY_DIMENSION)
        ->and($media->width)->toBe(3000)
        ->and($media->bytes)->toBe($disk->size($media->path) + $disk->size(StoreOptimizedImage::thumbnailPath($media->path)) + $disk->size($media->display_path));
});

it('keeps Pro photos at full quality size', function () {
    $pro = CameraAlbum::factory()->pro()->create();

    guestUpload($pro, jpegBytes(3000, 2000))->assertOk();

    expect(CameraMedia::sole()->width)->toBe(3000);
});

it('refuses a file that is not what it claims, and gives its place back', function () {
    guestUpload($this->album, "<?php echo 'hi'; ?>".str_repeat(' ', 200))->assertStatus(422);

    expect(CameraMedia::sole()->status)->toBe(CameraMediaStatus::Failed)
        ->and($this->album->fresh()->reserved_count)->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('refuses a body that is not the size reserved', function () {
    $reserved = $this->postJson(route('camera.upload.reserve', $this->album), ['type' => 'photo', 'mime' => 'image/jpeg', 'bytes' => 5000])->assertOk();

    $this->call('PUT', $reserved->json('target.url'), [], [], [], ['CONTENT_TYPE' => 'image/jpeg'], jpegBytes(10, 10))->assertStatus(422);
});

it('keeps videos for Pro only, within the size and length', function () {
    $mp4 = "\x00\x00\x00\x18ftypmp42".str_repeat("\x00", 500);

    $this->postJson(route('camera.upload.reserve', $this->album), ['type' => 'video', 'mime' => 'video/mp4', 'bytes' => 500])
        ->assertJsonValidationErrors(['file' => __('validation.custom.camera_video_not_allowed')]);

    $pro = CameraAlbum::factory()->pro()->create();

    $this->postJson(route('camera.upload.reserve', $pro), ['type' => 'video', 'mime' => 'video/mp4', 'bytes' => 101 * 1024 * 1024])->assertJsonValidationErrors('file');
    $this->postJson(route('camera.upload.reserve', $pro), ['type' => 'video', 'mime' => 'video/mp4', 'bytes' => 500, 'seconds' => 240])->assertJsonValidationErrors('file');

    guestUpload($pro, $mp4, 'video', 'video/mp4', 30)->assertOk();

    $video = CameraMedia::where('type', 'video')->sole();
    expect($video->status)->toBe(CameraMediaStatus::Ready)
        ->and($video->path)->toEndWith('.mp4')
        ->and($pro->fresh()->videos_count)->toBe(1);
});

it('stops a Basic album at its photo limit, even when two phones race for the last place', function () {
    $this->album->update(['photos_count' => 499]);

    $this->postJson(route('camera.upload.reserve', $this->album), ['type' => 'photo', 'mime' => 'image/jpeg', 'bytes' => 1000])->assertOk();
    $this->postJson(route('camera.upload.reserve', $this->album), ['type' => 'photo', 'mime' => 'image/jpeg', 'bytes' => 1000])
        ->assertJsonValidationErrors(['file' => __('validation.custom.camera_limit_reached', ['count' => 500])]);
});

it('slows down a phone sending too much', function () {
    app(CameraSettings::class)->save(['device_uploads_per_hour' => 2]);

    foreach (range(1, 2) as $attempt) {
        $this->postJson(route('camera.upload.reserve', $this->album), ['type' => 'photo', 'mime' => 'image/jpeg', 'bytes' => 1000])->assertOk();
    }

    $this->postJson(route('camera.upload.reserve', $this->album), ['type' => 'photo', 'mime' => 'image/jpeg', 'bytes' => 1000])
        ->assertJsonValidationErrors(['file' => __('validation.custom.camera_slow_down')]);
});

it('asks for the passcode, a few tries at a time, and signs guests out when it changes', function () {
    $this->album->update(['passcode_hash' => Hash::make('KAHWIN'), 'passcode_version' => 1]);

    $this->getJson(route('camera.gallery', $this->album))->assertForbidden();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('camera.enter', $this->album), ['passcode' => 'salah'])->assertSessionHasErrors(['passcode' => __('validation.custom.camera_passcode_wrong')]);
    }
    $this->post(route('camera.enter', $this->album), ['passcode' => 'KAHWIN'])->assertSessionHasErrors('passcode');

    RateLimiter::clear('camera-pass:'.$this->album->id.':127.0.0.1');
    $this->post(route('camera.enter', $this->album), ['passcode' => 'KAHWIN'])->assertRedirect(route('camera.show', $this->album));
    $this->getJson(route('camera.gallery', $this->album))->assertOk();

    $this->album->update(['passcode_version' => 2]);
    $this->getJson(route('camera.gallery', $this->album))->assertForbidden();
});

it('shows guests everyone else photos unless the couple turned that off', function () {
    CameraMedia::factory()->count(2)->for($this->album, 'album')->create();

    $this->getJson(route('camera.gallery', $this->album))->assertOk()->assertJsonCount(2, 'items');

    $this->album->update(['guests_can_view' => false]);
    $this->getJson(route('camera.gallery', $this->album))->assertOk()->assertJsonCount(0, 'items');
});

it('answers a guest 304 while nothing in the album changed', function () {
    $tag = $this->getJson(route('camera.gallery', $this->album))->assertOk()->headers->get('ETag');

    $this->getJson(route('camera.gallery', $this->album), ['If-None-Match' => $tag])->assertStatus(304);

    $this->album->update(['guests_can_view' => false]);
    $this->getJson(route('camera.gallery', $this->album), ['If-None-Match' => $tag])->assertOk();
});

it('lets a guest take back their own upload for a day, and nobody else', function () {
    guestUpload($this->album, jpegBytes(400, 300))->assertOk();
    $mine = CameraMedia::sole();
    $theirs = CameraMedia::factory()->for($this->album, 'album')->create();

    $this->deleteJson(route('camera.media.destroy', [$this->album, $theirs]))->assertForbidden();
    $this->deleteJson(route('camera.media.destroy', [$this->album, $mine]))->assertOk();

    expect(CameraMedia::whereKey($mine->id)->exists())->toBeFalse()
        ->and($this->album->fresh()->photos_count)->toBe(0);
    Storage::disk('public')->assertMissing($mine->path);
});

it('sends files straight to R2 on a presigned address when the media disk is a bucket', function () {
    config()->set('filesystems.disks.public', [
        'driver' => 's3', 'key' => 'test', 'secret' => 'test', 'region' => 'auto', 'bucket' => 'neekah',
        'endpoint' => 'https://account.r2.cloudflarestorage.com', 'use_path_style_endpoint' => true,
    ]);
    Storage::forgetDisk('public');
    $media = CameraMedia::factory()->for($this->album, 'album')->create(['status' => CameraMediaStatus::Reserved, 'incoming_path' => 'camera/1/incoming/abc', 'mime' => 'image/jpeg']);

    $target = CameraUploadTarget::for($media);

    expect($target['url'])->toContain('X-Amz-Signature')->toContain('r2.cloudflarestorage.com')
        ->and($target['headers']['Content-Type'])->toBe('image/jpeg')
        // Albums are deleted after the event, so the CDN keeps a file a day, not a year.
        ->and($target['headers']['Cache-Control'])->toBe(CameraAlbum::CACHE_CONTROL);
});

it('still takes a photo from a phone without JavaScript', function () {
    $file = UploadedFile::fake()->createWithContent('majlis.jpg', jpegBytes(800, 600));

    $this->post(route('camera.upload.fallback', $this->album), ['photo' => $file])->assertRedirect(route('camera.show', $this->album));

    expect(CameraMedia::sole()->status)->toBe(CameraMediaStatus::Ready);
});
