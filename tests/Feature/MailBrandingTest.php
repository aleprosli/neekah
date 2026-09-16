<?php

use App\Models\User;
use App\Notifications\CustomerRegistered;
use App\Support\ContactSettings;
use App\Support\SeoSettings;

it('renders every email in the Neekah template', function () {
    app(ContactSettings::class)->save(['email' => 'hello@neekah.my', 'phone' => '03-1234 5678']);
    app(SeoSettings::class)->save(['tagline' => 'Semua Urusan Majlis, Satu Platform']);

    $user = User::factory()->create(['name' => 'Aina']);
    $html = (string) (new CustomerRegistered)->toMail($user)->render();

    expect($html)
        ->toContain(config('neekah.brand.lockup'))     // the lockup, not Laravel's logo
        ->toContain('#a82133')                          // brand-600, the button and accents
        ->toContain('Semua Urusan Majlis, Satu Platform')
        ->toContain('hello@neekah.my')
        ->not->toContain('laravel.com');
});
