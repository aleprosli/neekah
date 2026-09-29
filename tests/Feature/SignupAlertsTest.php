<?php

use App\Http\Controllers\Auth\PhoneNumberController;
use App\Jobs\SendTelegramAlert;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\CustomerRegistered;
use App\Notifications\VendorRegistered;
use App\Support\ContactSettings;
use App\Support\TelegramSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

function enableTelegram(): void
{
    app(TelegramSettings::class)->save(['enabled' => true, 'bot_token' => 'bot-token', 'chat_id' => '-100123']);
}

function registerVendor(): void
{
    test()->post(route('vendor.register'), [
        'business_name' => 'ABC Wedding Photography',
        'category_id' => Category::first()->id,
        'city' => 'Alor Setar',
        'district' => 'Kota Setar',
        'state' => 'Kedah',
        'name' => 'Ahmad Bakri',
        'phone' => '012-345 6789',
        'email' => 'abc@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
    ])->assertRedirect(route('vendor.dashboard'));
}

it('thanks a new vendor by email and points them at our contact details', function () {
    Notification::fake();
    app(ContactSettings::class)->save(['email' => 'hello@neekah.my', 'phone' => '03-1234 5678']);

    registerVendor();

    $vendor = Vendor::sole();

    Notification::assertSentTo($vendor->user, VendorRegistered::class, function (VendorRegistered $notification) use ($vendor) {
        $mail = $notification->toMail($vendor->user);

        return $mail->subject === 'Terima kasih kerana mendaftar dengan Neekah'
            && str_contains(implode(' ', $mail->introLines), 'ABC Wedding Photography')
            && str_contains(implode(' ', $mail->outroLines), 'hello@neekah.my');
    });
});

it('tells the admin Telegram chat about a new vendor, with a WhatsApp link', function () {
    Queue::fake();
    enableTelegram();

    registerVendor();

    Queue::assertPushed(SendTelegramAlert::class, function (SendTelegramAlert $job): bool {
        $text = (fn () => $this->text())->call($job);

        return str_contains($text, 'NEEKAH TESTING')
            && str_contains($text, 'New vendor has been registered')
            && str_contains($text, 'https://wa.me/60123456789')
            && str_contains($text, 'abc@example.com');
    });
});

it('welcomes a new couple by email', function () {
    Notification::fake();
    app(ContactSettings::class)->save(['email' => 'hello@neekah.my']);

    $this->post(route('register'), [
        'name' => 'Aina',
        'email' => 'aina@example.com',
        'password' => 'kata-laluan-kuat',
        'password_confirmation' => 'kata-laluan-kuat',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'aina@example.com')->sole();

    Notification::assertSentTo($user, CustomerRegistered::class, function (CustomerRegistered $notification) use ($user) {
        $mail = $notification->toMail($user);

        return $mail->subject === 'Selamat datang ke Neekah'
            && str_contains(implode(' ', $mail->outroLines), 'hello@neekah.my');
    });
});

it('tells the admin Telegram chat about a new couple', function () {
    Queue::fake();
    enableTelegram();

    $this->post(route('register'), [
        'name' => 'Aina',
        'email' => 'aina@example.com',
        'phone' => '012-345 6789',
        'password' => 'kata-laluan-kuat',
        'password_confirmation' => 'kata-laluan-kuat',
    ])->assertSessionHasNoErrors();

    Queue::assertPushed(SendTelegramAlert::class, function (SendTelegramAlert $job): bool {
        $text = (fn () => $this->text())->call($job);

        return str_contains($text, 'New user has been registered') && str_contains($text, 'https://wa.me/60123456789');
    });
});

it('stays quiet while Telegram is not configured', function () {
    Queue::fake();

    registerVendor();

    Queue::assertNotPushed(SendTelegramAlert::class);
});

it('sends the alert to the Telegram bot API', function () {
    enableTelegram();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    (new SendTelegramAlert('New vendor has been registered', ['Emel' => 'abc@example.com']))
        ->handle(app(TelegramSettings::class));

    Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/botbot-token/sendMessage'
        && $request['chat_id'] === '-100123'
        && str_contains($request['text'], 'abc@example.com'));
});

it('finishes a Google signup once when the phone form is sent twice', function () {
    enableTelegram();
    Notification::fake();
    Queue::fake();

    $couple = User::factory()->create(['google_id' => 'g-123', 'phone' => null]);

    // Both requests carry the signup flag: a second tap arrives while the first
    // is still running, before it has written the session back. That is what
    // sent two welcome emails and two Telegram alerts on production.
    $this->actingAs($couple)
        ->withSession([PhoneNumberController::NEW_SIGNUP_KEY => true])
        ->post(route('phone.store'), ['phone' => '012-345 6789'])
        ->assertRedirect();

    $this->actingAs($couple)
        ->withSession([PhoneNumberController::NEW_SIGNUP_KEY => true])
        ->post(route('phone.store'), ['phone' => '012-345 6789'])
        ->assertRedirect();

    Notification::assertSentToTimes($couple, CustomerRegistered::class, 1);
    Queue::assertPushed(SendTelegramAlert::class, 1);
    expect($couple->fresh()->phone)->toBe('012-345 6789');
});
