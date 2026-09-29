<?php

use App\Models\Payment;
use App\Support\HerepaySettings;
use App\Support\OnlineBookingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * The props the card renderer was handed.
 *
 * The invitation is one Vue island, so this is the honest place to assert what a
 * guest is shown: the markup is built in the browser from exactly this. Asserting
 * on words alone would also match the card's own dictionary, which travels with
 * every card whether the section is drawn or not.
 *
 * @return array<string, mixed>
 */
function cardProps(TestResponse $response): array
{
    preg_match('/data-vue="card-view" data-props="([^"]*)"/', $response->getContent(), $matches);

    expect($matches)->not->toBeEmpty('The page mounts no card-view island.');

    return json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * The keys of the sections a card draws, in order.
 *
 * @return array<int, string>
 */
function cardWidgets(TestResponse $response): array
{
    return collect(cardProps($response)['widgets'])->pluck('key')->all();
}

/**
 * Open online booking site-wide, which is off until an admin opens it. Pair it
 * with Vendor::factory()->takingOnlineBookings().
 */
function enableOnlineBooking(array $settings = []): void
{
    app(OnlineBookingSettings::class)->save(['enabled' => true, ...$settings]);
}

/**
 * Point Herepay at UAT with test keys, as every payment test does. A real
 * HerepayGateway is used; Http::fake stands in for Herepay itself.
 */
function fakeHerepayKeys(bool $enabled = true, ?string $apiKey = 'test-api-key'): void
{
    config()->set('services.herepay', [
        'base_url' => 'https://uat.herepay.org',
        'secret_key' => 'test-secret',
        'private_key' => 'test-private',
        'api_key' => $apiKey,
    ]);
    app(HerepaySettings::class)->save(['enabled' => $enabled]);
}

/** Herepay making a link: every create-payment-link call answers with this pay_url. */
function fakeHerepayLink(string $payUrl = 'https://uat.herepay.org/herepay/pay/ABC'): void
{
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 200, 'data' => ['pay_url' => $payUrl]])]);
}

/**
 * What Herepay sends, the way it signs it: every field but the checksum,
 * sorted by key, values joined by commas, HMAC-SHA256 with the private key.
 *
 * @param  array<string, string>  $fields
 * @return array<string, string>
 */
function herepayFields(Payment $payment, array $fields = [], string $key = 'test-private'): array
{
    $fields = [
        'reference_code' => 'HP-INV-1',
        'payment_code' => 'HP-PAY-1',
        'transaction_id' => '2609262114370348',
        'status' => 'Success',
        'status_code' => '00',
        'message' => 'Approved',
        'amount' => (string) $payment->amount,
        'currency' => 'MYR',
        'payment_method' => 'FPX',
        ...$fields,
    ];
    $sorted = $fields;
    ksort($sorted);

    return [...$fields, 'checksum' => hash_hmac('sha256', implode(',', $sorted), $key)];
}

/**
 * Herepay's callback for a payment, to the signed address its link was made
 * with.
 *
 * @param  array<string, string>  $fields
 */
function herepayCallback(Payment $payment, array $fields = [], string $key = 'test-private'): TestResponse
{
    return test()->post(URL::signedRoute('payments.webhook', ['gateway' => 'herepay', 'ref' => $payment->reference], absolute: false), herepayFields($payment, $fields, $key));
}
