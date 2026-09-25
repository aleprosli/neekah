<?php

use App\Enums\CameraMediaStatus;
use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendTelegramAlert;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\User;
use App\Models\Wedding;
use App\Support\TelegramSettings;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
});

it('lists every album for an admin only, filtered by what state it is in', function () {
    $active = CameraAlbum::factory()->create();
    CameraAlbum::factory()->create(['purged_at' => now()->subDay(), 'expires_at' => now()->subDay()]);

    $this->actingAs($this->admin)->get(route('admin.camera.index'))->assertOk();
    $response = $this->actingAs($this->admin)->getJson(route('admin.camera.data', ['filter' => 'active']))->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.id'))->toBe($active->id)
        ->and($response->json('filters.filter'))->toMatchArray(['' => 2, 'active' => 1, 'purged' => 1]);

    $this->actingAs(User::factory()->create())->get(route('admin.camera.index'))->assertForbidden();
});

it('records a purchase paid outside Herepay for the couple of an email', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();

    $this->actingAs($this->admin)->post(route('admin.camera.store'), ['email' => $couple->email, 'tier' => 'pro', 'amount' => '0', 'note' => 'Hadiah'])
        ->assertSessionHasNoErrors();

    $purchase = $wedding->cameraPurchases()->sole();
    expect($purchase->status)->toBe(SubscriptionStatus::Paid)
        ->and((float) $purchase->amount)->toBe(0.0)
        ->and($purchase->added_by)->toBe($this->admin->id)
        ->and($wedding->cameraAlbum()->first()->tier)->toBe(CameraTier::Pro);

    $this->actingAs($this->admin)->post(route('admin.camera.store'), ['email' => 'tiada@neekah.my', 'tier' => 'basic'])
        ->assertSessionHasErrors('email');
});

it('takes an album down with every file', function () {
    $album = CameraAlbum::factory()->create();
    Storage::disk('public')->put("camera/{$album->id}/photo.webp", 'photo');

    $this->actingAs($this->admin)->delete(route('admin.camera.destroy', $album))->assertRedirect();

    expect($album->fresh()->purged_at)->not->toBeNull()
        ->and(Storage::disk('public')->allFiles("camera/{$album->id}"))->toBe([]);
});

it('lets a guest report someone else\'s photo once, and tells the admin chat', function () {
    Queue::fake();
    app(TelegramSettings::class)->save(['enabled' => true, 'bot_token' => 'bot-token', 'chat_id' => '-100123']);
    $album = CameraAlbum::factory()->create();
    $media = CameraMedia::factory()->for($album, 'album')->create(['path' => "camera/{$album->id}/a.webp"]);

    $item = collect($this->getJson(route('camera.gallery', $album))->json('items'))->firstWhere('id', $media->id);
    expect($item['report_url'])->toBe(route('camera.media.report', [$album, $media]));

    $this->postJson($item['report_url'], ['reason' => 'Tidak sesuai'])->assertOk();
    $this->postJson($item['report_url'], ['reason' => 'Lagi'])->assertOk();

    expect($media->fresh()->reported_at)->not->toBeNull()
        ->and($media->fresh()->report_reason)->toBe('Tidak sesuai');
    Queue::assertPushed(SendTelegramAlert::class, 1);
    $this->actingAs($this->admin)->get(route('admin.camera.index'))->assertSee('Tidak sesuai');
});

it('deletes a reported file or dismisses the report', function () {
    $album = CameraAlbum::factory()->create(['photos_count' => 2]);
    [$bad, $fine] = CameraMedia::factory()->for($album, 'album')->count(2)->create(['reported_at' => now(), 'report_reason' => 'x', 'status' => CameraMediaStatus::Ready]);

    $this->actingAs($this->admin)->delete(route('admin.camera.media.destroy', $bad))->assertRedirect();
    $this->actingAs($this->admin)->post(route('admin.camera.media.dismiss', $fine))->assertRedirect();

    expect(CameraMedia::whereKey($bad->id)->exists())->toBeFalse()
        ->and($fine->fresh()->reported_at)->toBeNull()
        ->and($album->fresh()->photos_count)->toBe(1);
});
