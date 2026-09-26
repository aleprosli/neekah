<?php

namespace App\Actions;

use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use App\Models\CameraAlbum;
use App\Models\CameraPurchase;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\CameraActivated;
use App\Support\CameraSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Mark a Neekah Kenangan purchase paid and open its album: a new album for a
 * new purchase (a wedding may hold one per majlis), or a higher tier for the
 * album an upgrade names. The one place that does it, for the Herepay
 * callback and an admin's manual record alike. Safe to call twice: Herepay
 * retries its callback.
 *
 * An album only moves up a tier, keeps its token (printed QR cards keep
 * working), and is kept until retention_days after its event.
 */
class ActivateCameraAlbum
{
    public function handle(CameraPurchase $purchase, ?string $gatewayReference = null): CameraPurchase
    {
        $activated = DB::transaction(function () use ($purchase, $gatewayReference): bool {
            $purchase = CameraPurchase::query()->lockForUpdate()->findOrFail($purchase->getKey());

            if ($purchase->isPaid()) {
                return false;
            }

            $wedding = Wedding::query()->findOrFail($purchase->wedding_id);
            $album = $purchase->camera_album_id
                ? CameraAlbum::query()->lockForUpdate()->where('wedding_id', $wedding->id)->findOrFail($purchase->camera_album_id)
                : new CameraAlbum([
                    'wedding_id' => $wedding->id,
                    'token' => CameraAlbum::freshToken(),
                    'tier' => $purchase->tier,
                    'title' => $purchase->album_title,
                    'event_date' => $purchase->album_event_date,
                ]);

            if ($purchase->tier->rank() > $album->tier->rank()) {
                $album->tier = $purchase->tier;
            }

            $album->activated_at ??= now();
            $album->purged_at = null;
            $album->expires_at = self::expiryFor($album->event_date ?? $wedding->event_date);
            $album->save();

            $purchase->update([
                'camera_album_id' => $album->id,
                'status' => SubscriptionStatus::Paid,
                'gateway_reference' => $gatewayReference ?? $purchase->gateway_reference,
                'paid_at' => now(),
            ]);

            return true;
        });

        $purchase->refresh();

        if ($activated) {
            $purchase->wedding->members->each(fn (User $member) => $member->notify(new CameraActivated($purchase)));
        }

        return $purchase;
    }

    /**
     * An admin recording a bank transfer or a gift, without Herepay: a new
     * album, or a higher tier for $album.
     */
    public function recordManually(Wedding $wedding, CameraTier $tier, User $admin, ?float $amount = null, ?string $note = null, ?CameraAlbum $album = null): CameraPurchase
    {
        $purchase = $wedding->cameraPurchases()->create([
            'camera_album_id' => $album?->id,
            'reference' => CameraPurchase::generateReference(),
            'tier' => $tier,
            'kind' => $album ? CameraPurchase::KIND_UPGRADE : CameraPurchase::KIND_NEW,
            'amount' => $amount ?? app(CameraSettings::class)->price($tier),
            'status' => SubscriptionStatus::Pending,
            'gateway' => CameraPurchase::GATEWAY_MANUAL,
            'added_by' => $admin->id,
            'note' => $note,
        ]);

        return $this->handle($purchase);
    }

    /**
     * The end of the retention period after the event, and never sooner than
     * three days from now, so a couple who bought late or moved the date
     * still gets their reminders and the ZIP download.
     */
    public static function expiryFor(CarbonInterface $eventDate): CarbonInterface
    {
        $end = $eventDate->copy()->addDays(app(CameraSettings::class)->retentionDays())->endOfDay();

        return $end->max(now()->addDays(3)->endOfDay());
    }
}
