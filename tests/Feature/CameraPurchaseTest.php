<?php

use App\Actions\ActivateCameraAlbum;
use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use App\Models\CameraAlbum;
use App\Models\CameraPurchase;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\CameraActivated;
use App\Support\CameraSettings;
use App\Support\HerepaySettings;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config()->set('services.herepay', ['base_url' => 'https://uat.herepay.org', 'secret_key' => 'neekah-secret', 'private_key' => 'neekah-private']);
    app(HerepaySettings::class)->save(['enabled' => true]);
    app(CameraSettings::class)->save(['enabled' => true]);

    $this->couple = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->couple)->create(['event_date' => now()->addMonths(2)->toDateString()]);
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 200, 'data' => ['pay_url' => 'https://uat.herepay.org/herepay/pay/CAM']])]);
});

/**
 * Herepay's callback for a camera purchase, checksummed with Neekah's key.
 *
 * @param  array<string, string>  $fields
 */
function cameraCallback(CameraPurchase $purchase, array $fields = []): TestResponse
{
    $fields = ['payment_code' => 'PAY-C', 'status_code' => '00', 'amount' => (string) $purchase->amount, ...$fields];
    $sorted = $fields;
    ksort($sorted);

    return test()->post(URL::signedRoute('webhooks.herepay.camera', ['ref' => $purchase->reference], absolute: false), [
        ...$fields,
        'checksum' => hash_hmac('sha256', implode(',', $sorted), 'neekah-private'),
    ]);
}

it('offers both tiers at the admin prices before anything is bought', function () {
    $props = $this->actingAs($this->couple)->get(route('camera.index'))->assertOk()->viewData('props');

    expect($props['albums'])->toBeEmpty()
        ->and($props['buy']['can_checkout'])->toBeTrue()
        ->and(collect($props['buy']['tiers'])->pluck('price', 'value')->all())->toBe(['basic' => 29.0, 'pro' => 99.0])
        ->and($props['buy']['tiers'][0]['limits']['max_photos'])->toBe(500)
        ->and($props['buy']['tiers'][0]['limits']['allows_video'])->toBeFalse()
        ->and($props['buy']['tiers'][1]['limits']['video_max_seconds'])->toBe(180)
        ->and(collect($props['buy']['tiers'])->pluck('voice', 'value')->all())->toBe(['basic' => false, 'pro' => true]);
});

it('sends the couple to pay on Neekah own Herepay account at todays price', function () {
    $this->actingAs($this->couple)
        ->post(route('camera.checkout', $this->wedding), ['tier' => 'pro'])
        ->assertRedirect('https://uat.herepay.org/herepay/pay/CAM');

    $purchase = CameraPurchase::sole();

    expect($purchase->status)->toBe(SubscriptionStatus::Pending)
        ->and((float) $purchase->amount)->toBe(99.0);

    Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader('SecretKey', 'neekah-secret')
        && $request['redirect_url'] === route('camera.done', ['ref' => $purchase->reference])
        && str_contains($request['callback_url'], '/webhooks/herepay/kamera'));
});

it('opens the album on a verified callback, once however often it comes', function () {
    Notification::fake();
    $purchase = CameraPurchase::factory()->for($this->wedding)->create();

    cameraCallback($purchase)->assertOk();
    cameraCallback($purchase)->assertOk();

    $album = CameraAlbum::sole();

    expect($purchase->fresh()->isPaid())->toBeTrue()
        ->and($album->tier)->toBe(CameraTier::Basic)
        ->and($album->isActive())->toBeTrue()
        ->and(strlen($album->token))->toBe(12)
        ->and($album->expires_at->toDateString())->toBe($this->wedding->event_date->copy()->addDays(14)->toDateString());

    Notification::assertSentToTimes($this->couple, CameraActivated::class, 1);
});

it('charges the difference to upgrade, keeps the QR address, and never sells a tier twice', function () {
    $album = CameraAlbum::factory()->for($this->wedding)->create();

    $this->actingAs($this->couple)->post(route('camera.checkout', $this->wedding), ['tier' => 'basic', 'album' => $album->id])->assertSessionHasErrors('tier');
    $this->actingAs($this->couple)->post(route('camera.checkout', $this->wedding), ['tier' => 'pro', 'album' => $album->id]);

    $upgrade = CameraPurchase::sole();
    expect((float) $upgrade->amount)->toBe(70.0)
        ->and($upgrade->kind)->toBe(CameraPurchase::KIND_UPGRADE);

    cameraCallback($upgrade)->assertOk();

    expect($album->fresh()->tier)->toBe(CameraTier::Pro)
        ->and($album->fresh()->token)->toBe($album->token)
        ->and($upgrade->fresh()->camera_album_id)->toBe($album->id)
        ->and(CameraAlbum::count())->toBe(1);
});

it('opens another album for another majlis, with its own name, date and QR', function () {
    $first = CameraAlbum::factory()->for($this->wedding)->create();
    $date = now()->addMonths(3)->toDateString();

    $this->actingAs($this->couple)
        ->post(route('camera.checkout', $this->wedding), ['tier' => 'basic', 'title' => 'Majlis Bertandang', 'event_date' => $date])
        ->assertRedirect('https://uat.herepay.org/herepay/pay/CAM');

    $purchase = CameraPurchase::sole();
    expect($purchase->kind)->toBe(CameraPurchase::KIND_NEW)
        ->and((float) $purchase->amount)->toBe(29.0)
        ->and($purchase->camera_album_id)->toBeNull();

    cameraCallback($purchase)->assertOk();

    $second = $purchase->fresh()->album;
    expect($this->wedding->cameraAlbums()->count())->toBe(2)
        ->and($second->is($first))->toBeFalse()
        ->and($second->title)->toBe('Majlis Bertandang')
        ->and($second->token)->not->toBe($first->token)
        ->and($second->expires_at->toDateString())->toBe(now()->addMonths(3)->addDays(14)->toDateString());
});

it('will not upgrade an album of another wedding', function () {
    $other = CameraAlbum::factory()->create();

    $this->actingAs($this->couple)
        ->post(route('camera.checkout', $this->wedding), ['tier' => 'pro', 'album' => $other->id])
        ->assertSessionHasErrors('album');

    expect(CameraPurchase::count())->toBe(0);
});

it('refuses a callback it cannot verify or that underpays', function () {
    $purchase = CameraPurchase::factory()->for($this->wedding)->pro()->create();

    cameraCallback($purchase, ['amount' => '1.00'])->assertStatus(422);
    $this->post(URL::signedRoute('webhooks.herepay.camera', ['ref' => $purchase->reference], absolute: false), ['status_code' => '00', 'checksum' => 'forged'])->assertForbidden();

    expect(CameraAlbum::count())->toBe(0);
});

it('keeps checkout closed while it is switched off, and away from other weddings', function () {
    app(CameraSettings::class)->save(['enabled' => false]);
    $this->actingAs($this->couple)->post(route('camera.checkout', $this->wedding), ['tier' => 'basic'])->assertNotFound();

    app(CameraSettings::class)->save(['enabled' => true]);
    $stranger = User::factory()->create();
    Wedding::factory()->for($stranger)->create();
    $this->actingAs($stranger)->post(route('camera.checkout', $this->wedding), ['tier' => 'basic'])->assertForbidden();
});

it('moves the deletion date when the couple moves the event, except for an album with its own date', function () {
    $album = app(ActivateCameraAlbum::class)->recordManually($this->wedding, CameraTier::Basic, User::factory()->admin()->create())->album;
    $ownDate = CameraAlbum::factory()->for($this->wedding)->create(['event_date' => now()->addMonths(3)->toDateString(), 'expires_at' => now()->addMonths(3)->addDays(14)]);

    $this->wedding->update(['event_date' => now()->addMonths(5)->toDateString()]);

    expect($album->fresh()->expires_at->toDateString())->toBe(now()->addMonths(5)->addDays(14)->toDateString())
        ->and($ownDate->fresh()->expires_at->toDateString())->toBe(now()->addMonths(3)->addDays(14)->toDateString());
});

it('keeps an album bought after the event for at least a few days', function () {
    $this->wedding->update(['event_date' => now()->subMonth()->toDateString()]);

    expect(ActivateCameraAlbum::expiryFor($this->wedding->event_date)->isAfter(now()->addDays(2)))->toBeTrue();
});

it('lets an admin price and limit the tiers', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.camera'), [
            'enabled' => '1', 'basic_price' => 39, 'pro_price' => 119, 'basic_max_photos' => 800, 'basic_photo_px' => 1600,
            'pro_photo_px' => 3840, 'pro_video_max_mb' => 100, 'pro_video_max_seconds' => 180, 'retention_days' => 21, 'pro_fair_use_gb' => 50,
        ])
        ->assertSessionHasNoErrors();

    $settings = app(CameraSettings::class);
    expect($settings->price(CameraTier::Pro))->toBe(119.0)
        ->and($settings->limitsFor(CameraTier::Basic)->maxPhotos)->toBe(800)
        ->and($settings->retentionDays())->toBe(21);

    $this->actingAs($this->couple)->put(route('admin.settings.camera'), ['enabled' => '1'])->assertForbidden();
});

it('shows a guest the album page and a closed page once it has gone', function () {
    $album = CameraAlbum::factory()->for($this->wedding)->create();

    $this->get(route('camera.show', $album))->assertOk()->assertSee(__('pages.camera.guest_intro'));

    $album->update(['purged_at' => now()]);
    $this->get(route('camera.show', $album))->assertOk()->assertSee(__('pages.camera.guest_closed'));
    $this->get('/k/tiadaalbum123')->assertNotFound();
});
