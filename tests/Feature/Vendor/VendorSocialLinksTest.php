<?php

use App\Models\Category;
use App\Models\Review;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->for(Category::first())->create(['name' => 'Bride Assistant']);
});

/** The fields the profile form always posts, plus the social links under test. */
function profileWithSocialLinks(Vendor $vendor, array $socialLinks): array
{
    return [
        'name' => $vendor->name,
        'category_id' => $vendor->category_id,
        'city' => $vendor->city,
        'state' => $vendor->state,
        'price_from' => $vendor->price_from,
        'price_unit' => $vendor->price_unit->value,
        'cover_tone' => $vendor->cover_tone,
        'social_links' => $socialLinks,
    ];
}

it('turns usernames and pasted links into links on the public profile', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWithSocialLinks($this->vendor, [
            'instagram' => '@bride.assistant',
            'facebook' => 'http://facebook.com/brideassistant',
            'tiktok' => '',
            'website' => 'brideassistant.my',
        ]))
        ->assertSessionHasNoErrors();

    expect($this->vendor->fresh()->social_links)->toBe([
        'instagram' => 'https://www.instagram.com/bride.assistant',
        'facebook' => 'https://facebook.com/brideassistant',
        'website' => 'https://brideassistant.my',
    ]);

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('href="https://www.instagram.com/bride.assistant"', false)
        ->assertSee('href="https://brideassistant.my"', false)
        ->assertSee('data-social-icon="instagram"', false)
        ->assertSee('data-social-icon="website"', false)
        ->assertDontSee('data-social-icon="tiktok"', false);
});

it('names the website link in the language of the page', function () {
    $this->vendor->update(['social_links' => ['website' => 'https://brideassistant.my']]);

    $this->get(route('vendors.show', $this->vendor))->assertOk()->assertSeeText('Ikuti Bride Assistant')->assertSeeText('Laman web');

    $this->get(url()->routeIn('en', 'vendors.show', ['vendor' => $this->vendor]))->assertOk()->assertSeeText('Follow Bride Assistant')->assertSeeText('Website');
});

it('refuses a link that points somewhere other than its platform', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWithSocialLinks($this->vendor, [
            'instagram' => 'https://evil.example/instagram.com',
        ]))
        ->assertSessionHasErrors('social_links.instagram');

    expect($this->vendor->fresh()->social_links)->toBeNull();
});

it('does not let the website field carry the vendor number past the login wall', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWithSocialLinks($this->vendor, [
            'website' => 'https://wa.me/60123456789',
        ]))
        ->assertSessionHasErrors('social_links.website');
});

it('refuses a platform the form does not offer', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWithSocialLinks($this->vendor, [
            'onlyfans' => 'https://example.com/x',
        ]))
        ->assertSessionHasErrors('social_links');
});

it('offers share links for the profile without exposing a wa.me link', function () {
    $url = rawurlencode(route('vendors.show', $this->vendor));

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.$url, false)
        ->assertSee('https://www.threads.com/intent/post?text=', false)
        ->assertSee('https://x.com/intent/post?text=', false)
        ->assertSee('https://api.whatsapp.com/send?text=', false)
        ->assertSee('data-copy="'.route('vendors.show', $this->vendor).'"', false)
        ->assertDontSee('wa.me');
});

it('counts open reviews in the profile headline without touching the ranking count', function () {
    Review::factory()->open()->count(2)->create(['vendor_id' => $this->vendor->id]);
    Review::factory()->open()->hidden()->create(['vendor_id' => $this->vendor->id]);

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('2 review</a>', false)
        ->assertDontSee('0 review</a>', false);

    expect($this->vendor->fresh()->reviews_count)->toBe(0);
});
