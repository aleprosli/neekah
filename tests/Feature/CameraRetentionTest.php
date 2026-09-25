<?php

use App\Actions\DeleteCameraMedia;
use App\Enums\CameraMediaStatus;
use App\Jobs\PurgeCdnUrls;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\CameraPurchase;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\CameraRetentionNotice;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Notification::fake();
    $this->couple = User::factory()->create();
});

/** A paid album whose wedding is on $eventDate and which is kept until $expires. */
function keptAlbum(User $couple, string $eventDate, DateTimeInterface $expires, array $attributes = []): CameraAlbum
{
    $wedding = Wedding::factory()->for($couple)->create(['event_date' => $eventDate]);

    return CameraAlbum::factory()->for($wedding)->create(['expires_at' => $expires, ...$attributes]);
}

it('reminds the couple the day after the event, then a week and a day before deletion, once each', function () {
    $afterEvent = keptAlbum($this->couple, today()->subDay()->toDateString(), now()->addDays(13));
    $week = keptAlbum($this->couple, today()->subDays(7)->toDateString(), today()->addDays(7)->endOfDay());
    $day = keptAlbum($this->couple, today()->subDays(13)->toDateString(), today()->addDay()->endOfDay());
    keptAlbum($this->couple, today()->subDays(7)->toDateString(), today()->addDays(7)->endOfDay(), ['activated_at' => null]);
    keptAlbum($this->couple, today()->subDays(3)->toDateString(), today()->addDays(11)->endOfDay());

    $this->artisan('neekah:camera-retention')->assertSuccessful();

    $moments = Notification::sent($this->couple, CameraRetentionNotice::class)
        ->map(fn (CameraRetentionNotice $notice): string => $notice->album->id.':'.$notice->moment)->sort()->values()->all();

    expect($moments)->toBe(collect(["{$afterEvent->id}:after_event", "{$week->id}:expiring_7", "{$day->id}:expiring_1"])->sort()->values()->all());
});

it('deletes every file of an expired album but keeps the album and its purchase as the record', function () {
    $album = keptAlbum($this->couple, today()->subDays(15)->toDateString(), now()->subMinute(), [
        'photos_count' => 1, 'bytes_used' => 11, 'export_paths' => ['camera/x/export/part1.zip'],
    ]);
    $album->update(['export_paths' => ["camera/{$album->id}/export/part1.zip"]]);
    $purchase = CameraPurchase::factory()->for($album->wedding)->create();
    $media = CameraMedia::factory()->for($album, 'album')->create(['path' => "camera/{$album->id}/photo.webp"]);
    $disk = Storage::disk('public');
    $disk->put($media->path, 'photo');
    $disk->put("camera/{$album->id}/export/part1.zip", 'zip');
    $disk->put("camera/{$album->id}/incoming/left-behind", 'half');

    $kept = keptAlbum($this->couple, today()->addMonth()->toDateString(), now()->addMonths(2));
    $disk->put("camera/{$kept->id}/photo.webp", 'photo');

    $this->artisan('neekah:camera-retention')->assertSuccessful();

    $album->refresh();
    expect($disk->allFiles("camera/{$album->id}"))->toBe([])
        ->and(CameraMedia::whereKey($media->id)->exists())->toBeFalse()
        ->and($album->purged_at)->not->toBeNull()
        ->and($album->isActive())->toBeFalse()
        ->and($album->photos_count)->toBe(0)
        ->and($album->bytes_used)->toBe(0)
        ->and($album->export_paths)->toBeNull()
        ->and($purchase->fresh())->not->toBeNull();
    $disk->assertExists("camera/{$kept->id}/photo.webp");
    Notification::assertSentTo($this->couple, CameraRetentionNotice::class, fn (CameraRetentionNotice $notice): bool => $notice->moment === 'purged');

    $this->get('/k/'.$album->token)->assertOk()->assertSee(__('pages.camera.guest_closed'));
});

it('asks Cloudflare to forget deleted files only when a zone and token are set', function () {
    Bus::fake([PurgeCdnUrls::class]);
    $album = keptAlbum($this->couple, today()->subDays(15)->toDateString(), now()->subMinute());
    CameraMedia::factory()->for($album, 'album')->create(['path' => "camera/{$album->id}/photo.webp"]);

    $this->artisan('neekah:camera-retention');
    Bus::assertNotDispatched(PurgeCdnUrls::class);

    config(['services.cloudflare.zone_id' => 'zone', 'services.cloudflare.api_token' => 'token']);
    Storage::fake('public', ['url' => 'https://media.neekah.my']);
    $second = keptAlbum($this->couple, today()->subDays(15)->toDateString(), now()->subMinute());
    CameraMedia::factory()->for($second, 'album')->create(['path' => "camera/{$second->id}/photo.webp"]);

    $this->artisan('neekah:camera-retention');
    Bus::assertDispatched(PurgeCdnUrls::class, fn (PurgeCdnUrls $job): bool => in_array("https://media.neekah.my/camera/{$second->id}/photo.webp", $job->urls, true));
});

it('sends the purge to the Cloudflare zone', function () {
    config(['services.cloudflare.zone_id' => 'zone123', 'services.cloudflare.api_token' => 'token']);
    Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true])]);

    (new PurgeCdnUrls(['https://media.neekah.my/camera/1/a.webp']))->handle();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.cloudflare.com/client/v4/zones/zone123/purge_cache'
        && $request->hasHeader('Authorization', 'Bearer token')
        && $request['files'] === ['https://media.neekah.my/camera/1/a.webp']);
});

it('gives back the place of an upload whose file never came', function () {
    $album = keptAlbum($this->couple, today()->addMonth()->toDateString(), now()->addMonths(2), ['reserved_count' => 3, 'bytes_reserved' => 300]);
    $abandoned = CameraMedia::factory()->for($album, 'album')->create([
        'status' => CameraMediaStatus::Reserved, 'path' => null, 'declared_bytes' => 100, 'incoming_path' => "camera/{$album->id}/incoming/abc",
    ]);
    Storage::disk('public')->put($abandoned->incoming_path, 'half');
    $abandoned->forceFill(['updated_at' => now()->subHours(3)])->saveQuietly();
    $recent = CameraMedia::factory()->for($album, 'album')->create(['status' => CameraMediaStatus::Reserved, 'path' => null, 'declared_bytes' => 100]);
    $processing = CameraMedia::factory()->for($album, 'album')->create(['status' => CameraMediaStatus::Processing, 'path' => null, 'declared_bytes' => 100]);
    $processing->forceFill(['updated_at' => now()->subHours(3)])->saveQuietly();

    $this->artisan('neekah:camera-retention')->assertSuccessful();

    expect($abandoned->fresh()->status)->toBe(CameraMediaStatus::Failed)
        ->and($recent->fresh()->status)->toBe(CameraMediaStatus::Reserved)
        ->and($processing->fresh()->status)->toBe(CameraMediaStatus::Processing)
        ->and($album->fresh()->reserved_count)->toBe(2)
        ->and($album->fresh()->bytes_reserved)->toBe(200);
    Storage::disk('public')->assertMissing($abandoned->incoming_path);
});

it('deletes the Kamera Majlis files with the account', function () {
    $admin = User::factory()->admin()->create();
    $album = keptAlbum($this->couple, today()->addMonth()->toDateString(), now()->addMonths(2));
    Storage::disk('public')->put("camera/{$album->id}/photo.webp", 'photo');

    $this->actingAs($admin)->delete(route('admin.users.destroy', $this->couple))->assertRedirect();

    expect(Storage::disk('public')->allFiles("camera/{$album->id}"))->toBe([]);
});

it('asks Cloudflare to forget one photo the moment it is deleted', function () {
    Bus::fake([PurgeCdnUrls::class]);
    config(['services.cloudflare.zone_id' => 'zone', 'services.cloudflare.api_token' => 'token']);
    Storage::fake('public', ['url' => 'https://media.neekah.my']);
    $media = CameraMedia::factory()->create(['path' => 'camera/1/photo.webp']);

    app(DeleteCameraMedia::class)->handle($media);

    Bus::assertDispatched(PurgeCdnUrls::class, fn (PurgeCdnUrls $job): bool => $job->urls === ['https://media.neekah.my/camera/1/photo.webp', 'https://media.neekah.my/camera/1/photo-thumb.webp']);
});
