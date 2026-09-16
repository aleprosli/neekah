<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('renders a branded 404 rather than the framework default', function () {
    $this->get('/halaman-yang-tiada')
        ->assertNotFound()
        ->assertSee('Halaman ini tiada')
        ->assertSee('Cari vendor')
        ->assertSee(config('neekah.brand.lockup'))
        ->assertSee('noindex, nofollow', false);
});

it('renders a branded 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden()
        ->assertSee('Anda tiada akses ke sini');
});

it('does not read the database while rendering an error page', function () {
    // An error page has to survive the failure that caused it, so it may not
    // touch settings, the session user or anything else behind a query.
    DB::listen(fn () => throw new RuntimeException('An error page must not query the database.'));

    $this->get('/halaman-yang-tiada')->assertNotFound();
});
