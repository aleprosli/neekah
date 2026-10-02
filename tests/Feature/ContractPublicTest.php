<?php

use App\Enums\ContractStatus;
use App\Enums\QuotationStatus;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Quotation;
use App\Models\Vendor;
use App\Notifications\ContractSigned;
use App\Notifications\QuotationAccepted;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->create(['name' => 'Studio Cahaya']);
    Storage::fake('local');
});

/** A drawn signature as the pad posts it. */
function signatureDataUrl(int $width = 600, int $height = 200): string
{
    $image = imagecreatetruecolor($width, $height);
    imageline($image, 10, 10, $width - 10, $height - 10, imagecolorallocate($image, 255, 255, 255));
    ob_start();
    imagepng($image);

    return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
}

/**
 * @return array<string, string>
 */
function signing(array $overrides = []): array
{
    return ['name' => 'Aina Hakim', 'agree' => '1', 'signature' => signatureDataUrl(), ...$overrides];
}

it('opens a sent contract by its token without an account', function () {
    $contract = Contract::factory()->for($this->vendor)->sent()->create(['number' => 'KT-1023']);

    $this->get(route('contracts.public.show', $contract->token))
        ->assertOk()
        ->assertSee('KT-1023')
        ->assertSee('Studio Cahaya')
        ->assertSee('data-doc', false)
        ->assertSee('noindex', false)
        ->assertSee('data-vue="contract-signature-pad"', false);

    expect($contract->fresh()->viewed_at)->not->toBeNull();

    $this->get('/kontrak/KT-1023')->assertNotFound();
});

it('shows a draft only to its own vendor', function () {
    $draft = Contract::factory()->for($this->vendor)->create();

    $this->get(route('contracts.public.show', $draft->token))->assertNotFound();
    $this->actingAs($this->vendor->user)->get(route('contracts.public.show', $draft->token))->assertOk();
});

it('records the signature privately with who, when, where and a hash of what was signed', function () {
    Notification::fake();
    $contract = Contract::factory()->for($this->vendor)->sent()->create(['client_email' => 'aina@example.com']);

    $this->withHeader('User-Agent', 'TestPhone/1.0')
        ->post(route('contracts.public.sign', $contract->token), signing())
        ->assertRedirect($contract->publicUrl())
        ->assertSessionHas('status', __('flash.contract.signed'));

    $contract->refresh();
    expect($contract->status)->toBe(ContractStatus::Signed)
        ->and($contract->signer_name)->toBe('Aina Hakim')
        ->and($contract->signer_ip)->toBe('127.0.0.1')
        ->and($contract->signer_user_agent)->toBe('TestPhone/1.0')
        ->and($contract->content_hash)->toBe($contract->computeHash())
        ->and($contract->signature_path)->toStartWith('contracts/'.$this->vendor->id.'/signatures/');

    Storage::disk('local')->assertExists($contract->signature_path);
    Storage::disk('public')->assertMissing($contract->signature_path);

    Notification::assertSentTo($this->vendor->user, ContractSigned::class);
    Notification::assertSentOnDemand(ContractSigned::class);

    $this->get(route('contracts.public.signature', $contract->token))->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get(route('contracts.public.show', $contract->token))->assertSee(route('contracts.public.signature', $contract->token), false);
});

it('changes the hash when the signed text would differ', function () {
    $contract = Contract::factory()->for($this->vendor)->sent()->create();
    $before = $contract->computeHash();

    $contract->sections = [['key' => 'scope', 'title' => 'Skop', 'body' => 'Lain']];

    expect($contract->computeHash())->not->toBe($before);
});

it('refuses a signature that is not a drawn PNG', function (string $signature) {
    $contract = Contract::factory()->for($this->vendor)->sent()->create();

    $this->post(route('contracts.public.sign', $contract->token), signing(['signature' => $signature]))
        ->assertSessionHasErrors(['signature' => __('validation.custom.signature_invalid')]);

    expect($contract->fresh()->status)->toBe(ContractStatus::Sent);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'not a data url' => 'hello',
    'a jpeg claiming png' => 'data:image/png;base64,'.base64_encode("\xFF\xD8\xFF\xE0".str_repeat('a', 100)),
    'a dot' => fn () => signatureDataUrl(10, 10),
]);

it('needs a name, the tick and a signature', function () {
    $contract = Contract::factory()->for($this->vendor)->sent()->create();

    $this->post(route('contracts.public.sign', $contract->token), [])
        ->assertSessionHasErrors(['name', 'agree', 'signature']);
});

it('cannot be signed twice, or once withdrawn', function (string $state) {
    Notification::fake();
    $contract = Contract::factory()->for($this->vendor)->{$state}()->create();
    $before = $contract->status;

    $this->post(route('contracts.public.sign', $contract->token), signing())
        ->assertSessionHasErrors(['name' => __('validation.custom.contract_closed')]);

    expect($contract->fresh()->status)->toBe($before);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    Notification::assertNothingSent();
})->with(['signed', 'void']);

it('accepts the attached quotation when the contract is signed', function () {
    Notification::fake();
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create();
    $contract = Contract::factory()->for($this->vendor)->sent()->create(['quotation_id' => $quotation->id]);

    // The quotation's page sends the client to the contract instead of its own accept form.
    $this->get(route('quotations.public.show', $quotation->token))
        ->assertSee($contract->publicUrl(), false)
        ->assertDontSee(route('quotations.public.accept', $quotation->token), false);

    $this->post(route('contracts.public.sign', $contract->token), signing())->assertRedirect();

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Accepted)
        ->and($quotation->fresh()->accepted_name)->toBe('Aina Hakim')
        ->and($contract->fresh()->content_hash)->toBe($contract->fresh()->computeHash());

    Notification::assertSentTo($this->vendor->user, QuotationAccepted::class);
});

it('serves no signature for a contract nobody signed', function () {
    $contract = Contract::factory()->for($this->vendor)->sent()->create();

    $this->get(route('contracts.public.signature', $contract->token))->assertNotFound();
});
