<?php

use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('points at a status page hosted somewhere other than here', function () {
    // A status page served by the thing it reports on is no use when that
    // thing is the problem.
    $html = $this->get(route('landing'))->assertOk()->getContent();

    expect($html)
        ->toContain(config('neekah.status_url'))
        ->toContain(__('pages.footer.status_sistem'));

    expect(parse_url(config('neekah.status_url'), PHP_URL_HOST))
        ->not->toBe(parse_url(config('app.url'), PHP_URL_HOST));
});

it('drops the status link when no status page is configured', function () {
    config(['neekah.status_url' => '']);

    expect($this->get(route('landing'))->assertOk()->getContent())
        ->not->toContain(__('pages.footer.status_sistem'));
});

it('opens the status page in a tab of its own, without leaking the referrer chain', function () {
    $html = $this->get(route('landing'))->assertOk()->getContent();

    expect($html)->toMatch('/<a href="'.preg_quote(config('neekah.status_url'), '/').'"[^>]*rel="noopener"/');
});

it('shows only the language you would move to when the header is narrow', function () {
    $html = $this->get(route('landing'))->assertOk()->getContent();

    // Both halves are in the markup; the one you are already reading is the
    // one that stands down on a phone.
    expect($html)
        ->toContain('>MS</a>')
        ->toContain('>EN</a>')
        ->toContain('hidden bg-brand-600 text-white sm:block');

    // And the one you would move to says where it goes.
    expect($html)->toContain(__('nav.switch_to', ['language' => 'English']));
});

it('lets the logo give ground so the header cannot overflow a phone', function () {
    // The buttons beside it are wider in English than in Malay, so the header
    // must not depend on any of them being a particular width.
    $header = file_get_contents(resource_path('views/components/site/header.blade.php'));

    expect($header)
        ->toContain('min-w-0 shrink items-center')
        ->toContain('max-w-full')
        ->not->toContain('max-w-[42vw]');
});

it('shows the opening hours in the language of the page', function () {
    app(App\Support\ContactSettings::class)->save([
        'hours' => 'Ahad - Khamis, 9 Pagi - 5 Petang',
        'hours_en' => 'Sunday - Thursday, 9am - 5pm',
    ]);

    // They sit in the footer of every page, and "Ahad - Khamis" is not
    // something to show someone reading English.
    $this->get('/')->assertOk()->assertSee('Ahad - Khamis, 9 Pagi - 5 Petang')->assertDontSee('Sunday - Thursday');
    $this->get('/en')->assertOk()->assertSee('Sunday - Thursday, 9am - 5pm')->assertDontSee('Ahad - Khamis');
});

it('falls back to the Malay hours when the English ones are not written', function () {
    app(App\Support\ContactSettings::class)->save(['hours' => 'Ahad - Khamis, 9 Pagi - 5 Petang', 'hours_en' => '']);

    // Better the Malay hours than a blank line where the hours should be.
    $this->get('/en')->assertOk()->assertSee('Ahad - Khamis, 9 Pagi - 5 Petang');
});

it('gives an admin one field for the hours in each language', function () {
    $html = $this->actingAs(App\Models\User::factory()->admin()->create())
        ->get(route('admin.settings.edit'))->assertOk()->getContent();

    expect(html_entity_decode($html))
        ->toContain(__('props.admin.waktu_operasi_bahasa', ['language' => 'Bahasa Melayu']))
        ->toContain(__('props.admin.waktu_operasi_bahasa', ['language' => 'English']));
});

it('accepts the hours an admin writes in each language', function () {
    $this->actingAs(App\Models\User::factory()->admin()->create())
        ->put(route('admin.settings.contact'), [
            'hours' => 'Ahad - Khamis, 9 Pagi - 5 Petang',
            'hours_en' => 'Sunday - Thursday, 9am - 5pm',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(app(App\Support\ContactSettings::class)->all())
        ->toMatchArray(['hours' => 'Ahad - Khamis, 9 Pagi - 5 Petang', 'hours_en' => 'Sunday - Thursday, 9am - 5pm']);
});

it('keeps the address single, because a place reads the same in any language', function () {
    expect(array_keys(App\Support\ContactSettings::defaults()))
        ->toContain('hours')
        ->toContain('hours_en')
        ->toContain('address')
        ->not->toContain('address_en');
});
