<?php

namespace App\Actions;

use App\Enums\CameraTier;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\CameraAlbum;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\CameraActivated;
use App\Support\CameraSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * What a paid Neekah Kenangan payment buys: a new album (a wedding may hold
 * one per majlis) or a higher tier for the album an upgrade names.
 *
 * An album only moves up a tier, keeps its token (printed QR cards keep
 * working), and is kept until retention_days after its event.
 */
class ActivateCameraAlbum
{
    /**
     * Open the album a paid payment bought: a new one for a new purchase,
     * or the higher tier for the album an upgrade names. SettlePayment calls
     * it once, inside its lock.
     */
    public function fulfil(Payment $payment): void
    {
        $wedding = Wedding::query()->findOrFail($payment->wedding_id);
        $tier = CameraTier::from((string) $payment->detail('tier', CameraTier::Basic->value));
        $album = $payment->camera_album_id
            ? CameraAlbum::query()->lockForUpdate()->where('wedding_id', $wedding->id)->findOrFail($payment->camera_album_id)
            : new CameraAlbum([
                'wedding_id' => $wedding->id,
                'token' => CameraAlbum::freshToken(),
                'tier' => $tier,
                'title' => $payment->detail('album_title'),
                'event_date' => $payment->detail('album_event_date'),
            ]);

        if ($tier->rank() > $album->tier->rank()) {
            $album->tier = $tier;
        }

        $album->activated_at ??= now();
        $album->purged_at = null;
        $album->expires_at = self::expiryFor($album->event_date ?? $wedding->event_date);
        $album->save();

        $payment->update(['camera_album_id' => $album->id]);

        DB::afterCommit(fn () => $wedding->members->each(fn (User $member) => $member->notify(new CameraActivated($payment->fresh()))));
    }

    /**
     * An admin recording a bank transfer or a gift, without a gateway: a new
     * album, or a higher tier for $album. It settles like any other payment.
     */
    public function recordManually(Wedding $wedding, CameraTier $tier, User $admin, ?float $amount = null, ?string $note = null, ?CameraAlbum $album = null): Payment
    {
        $payment = Payment::query()->create([
            'purpose' => PaymentPurpose::Kenangan,
            'wedding_id' => $wedding->id,
            'camera_album_id' => $album?->id,
            'recorded_by' => $admin->id,
            'amount' => $amount ?? app(CameraSettings::class)->price($tier),
            'status' => PaymentStatus::Pending,
            'gateway' => Payment::GATEWAY_MANUAL,
            'method' => 'manual',
            'note' => $note,
            'details' => ['tier' => $tier->value, 'kind' => $album ? 'upgrade' : 'new'],
        ]);

        PaymentEvent::record($payment, Payment::GATEWAY_MANUAL, PaymentEvent::MANUAL_RECORDED, meta: ['note' => $note]);
        app(SettlePayment::class)->markPaid($payment);

        return $payment->fresh();
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
