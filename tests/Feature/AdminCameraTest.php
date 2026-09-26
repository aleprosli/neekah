<?php

use App\Enums\CameraMediaStatus;
use App\Enums\CameraTier;
use App\Enums\PaymentStatus;
use App\Jobs\SendTelegramAlert;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\Payment;
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

    // Ticking two states widens the list to either.
    $both = $this->actingAs($this->admin)->getJson(route('admin.camera.data', ['filter' => 'active,purged']))->assertOk();
    expect($both->json('meta.total'))->toBe(2);
});

it('records a purchase paid outside Herepay for the couple of an email', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();

    $this->actingAs($this->admin)->post(route('admin.camera.store'), ['email' => $couple->email, 'tier' => 'pro', 'amount' => '0', 'note' => 'Hadiah'])
        ->assertSessionHasNoErrors();

    $purchase = $wedding->kenanganPayments()->sole();
    expect($purchase->status)->toBe(PaymentStatus::Paid)
        ->and((float) $purchase->amount)->toBe(0.0)
        ->and($purchase->recorded_by)->toBe($this->admin->id)
        ->and($purchase->gateway)->toBe(Payment::GATEWAY_MANUAL)
        ->and($wedding->cameraAlbums()->sole()->tier)->toBe(CameraTier::Pro);

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

it('sets a couple\'s Kamera Majlis from their account page: on for free, down a tier, and off', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    $camera = fn () => $this->actingAs($this->admin)->get(route('admin.users.show', $couple))->assertOk()->viewData('props')['camera'];
    $set = fn (string $tier) => $this->actingAs($this->admin)->from(route('admin.users.show', $couple))
        ->put(route('admin.users.camera', $couple), ['tier' => $tier])->assertRedirect(route('admin.users.show', $couple));

    expect($camera()['current'])->toBe('off');

    $set('pro');
    $album = $wedding->cameraAlbums()->sole();
    expect($camera()['current'])->toBe('pro')
        ->and($camera()['album']['url'])->toBe($album->url())
        ->and((float) $wedding->kenanganPayments()->sole()->amount)->toBe(0.0);

    $set('basic');
    expect($album->fresh()->tier)->toBe(CameraTier::Basic)
        ->and($wedding->kenanganPayments()->count())->toBe(1);

    Storage::disk('public')->put("camera/{$album->id}/a.webp", 'photo');
    $set('off');
    expect($album->fresh()->purged_at)->not->toBeNull()
        ->and($camera()['current'])->toBe('off')
        ->and(Storage::disk('public')->allFiles("camera/{$album->id}"))->toBe([]);

    $this->actingAs(User::factory()->create())->put(route('admin.users.camera', $couple), ['tier' => 'pro'])->assertForbidden();
});
