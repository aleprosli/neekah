<?php

use App\Actions\SettlePayment;
use App\Actions\VerifyManualPayment;
use App\Enums\BookingStatus;
use App\Jobs\SendTelegramAlert;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Support\TelegramSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    Notification::fake();
    Queue::fake();
    app(TelegramSettings::class)->save(['enabled' => true, 'bot_token' => 'bot-token', 'chat_id' => '-100123']);

    $this->owner = User::factory()->vendor()->create(['email' => 'studio@example.com']);
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create(['name' => 'Studio Seri']);
});

function telegramText(SendTelegramAlert $job): string
{
    return (fn () => $this->text())->call($job);
}

it('tells the admin Telegram chat once when a Kenangan album is paid, however often it is settled', function () {
    $payment = Payment::factory()->kenangan()->create();
    $couple = $payment->wedding->user;

    app(SettlePayment::class)->markPaid($payment);
    app(SettlePayment::class)->markPaid($payment);

    Queue::assertPushed(SendTelegramAlert::class, 1);
    Queue::assertPushed(SendTelegramAlert::class, function (SendTelegramAlert $job) use ($payment, $couple): bool {
        $text = telegramText($job);

        return str_contains($text, 'Bayaran diterima: Neekah Kenangan')
            && str_contains($text, 'Jumlah: RM29.00')
            && str_contains($text, $couple->email)
            && str_contains($text, $payment->fresh()->receipt_number)
            && str_contains($text, route('admin.payments.show', $payment));
    });
});

it('tells the admin Telegram chat when a vendor subscribes to Neekah Pro', function () {
    $payment = Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id, 'amount' => 49]);

    app(SettlePayment::class)->markPaid($payment);

    Queue::assertPushed(SendTelegramAlert::class, fn (SendTelegramAlert $job): bool => str_contains(telegramText($job), 'Bayaran diterima: Neekah Pro')
        && str_contains(telegramText($job), 'Studio Seri')
        && str_contains(telegramText($job), 'RM49.00'));
});

it('tells the admin Telegram chat when a vendor confirms a couple\'s bank transfer', function () {
    $booking = Booking::factory()->for($this->vendor)->for(User::factory())->create(['status' => BookingStatus::PendingPayment, 'total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create(['amount' => 900]);

    app(VerifyManualPayment::class)->handle($payment, $this->owner);

    Queue::assertPushed(SendTelegramAlert::class, fn (SendTelegramAlert $job): bool => str_contains(telegramText($job), 'Bayaran diterima: Tempahan')
        && str_contains(telegramText($job), 'Vendor: Studio Seri')
        && str_contains(telegramText($job), 'RM900.00'));
});
