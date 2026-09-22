<?php

use App\Actions\StoreOptimizedImage;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ImageSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('lets an admin change how uploads are processed', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update'), [
            'max_dimension' => 1200,
            'thumbnail_width' => 400,
            'quality' => 70,
            'format' => 'jpeg',
            'max_upload_mb' => 5,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(app(ImageSettings::class)->all())->toBe([
        'max_dimension' => 1200,
        'thumbnail_width' => 400,
        'quality' => 70,
        'format' => 'jpeg',
        'max_upload_mb' => 5,
    ]);
});

it('refuses image settings outside the safe range', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update'), [
            'max_dimension' => 50000,
            'thumbnail_width' => 400,
            'quality' => 150,
            'format' => 'gif',
            'max_upload_mb' => 100,
        ])
        ->assertSessionHasErrors(['max_dimension', 'quality', 'format', 'max_upload_mb']);
});

it('keeps image settings away from anyone who is not an admin', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.settings.edit'))
        ->assertForbidden();
});

it('stores an upload shrunk to the admin limit, as webp, with a thumbnail', function () {
    Storage::fake('public');
    app(ImageSettings::class)->save(['max_dimension' => 1000, 'thumbnail_width' => 300]);
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)
        ->post(route('vendor.portfolio.store'), ['images' => [UploadedFile::fake()->image('majlis.jpg', 2400, 1600)]])
        ->assertRedirect();

    $path = PortfolioItem::sole()->path;
    $disk = Storage::disk('public');

    expect($path)->toEndWith('.webp')
        ->and(getimagesizefromstring($disk->get($path)))->toMatchArray([0 => 1000, 1 => 667, 'mime' => 'image/webp'])
        ->and(getimagesizefromstring($disk->get(StoreOptimizedImage::thumbnailPath($path)))[0])->toBe(300);
});

it('writes jpeg when the admin chooses it', function () {
    Storage::fake('public');
    app(ImageSettings::class)->save(['format' => 'jpeg']);
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)
        ->post(route('vendor.portfolio.store'), ['images' => [UploadedFile::fake()->image('majlis.png', 800, 600)]]);

    expect(getimagesizefromstring(Storage::disk('public')->get(PortfolioItem::sole()->path))['mime'])->toBe('image/jpeg');
});

it('never enlarges an image already under the limit', function () {
    Storage::fake('public');
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)
        ->post(route('vendor.portfolio.store'), ['images' => [UploadedFile::fake()->image('kecil.jpg', 500, 400)]]);

    expect(getimagesizefromstring(Storage::disk('public')->get(PortfolioItem::sole()->path))[0])->toBe(500);
});

it('refuses an upload above the admin size limit', function () {
    Storage::fake('public');
    app(ImageSettings::class)->save(['max_upload_mb' => 1]);
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)
        ->post(route('vendor.portfolio.store'), ['images' => [UploadedFile::fake()->image('besar.jpg')->size(2048)]])
        ->assertSessionHasErrors('images.0');

    expect(PortfolioItem::count())->toBe(0);
});

it('never validates against a limit the server itself will refuse', function () {
    app(ImageSettings::class)->save(['max_upload_mb' => 15]);
    $images = app(ImageSettings::class);

    // php.ini decides; the admin's number can only lower it, never raise it.
    expect($images->effectiveUploadMegabytes())->toBe(min(15, $images->serverUploadMegabytes()))
        ->and($images->uploadRules())->toContain('max:'.($images->effectiveUploadMegabytes() * 1024));
});

it('answers an upload posted by the progress bar with somewhere to go', function () {
    Storage::fake('public');
    $vendor = Vendor::factory()->for(Category::first())->create();

    // The progress bar posts over XHR. A 302 would be followed invisibly and
    // would eat the flash message, so the destination comes back as JSON.
    $this->actingAs($vendor->user)
        ->post(
            route('vendor.portfolio.store'),
            ['images' => [UploadedFile::fake()->image('majlis.jpg', 800, 600)]],
            ['Accept' => 'application/json'],
        )
        ->assertOk()
        ->assertJsonPath('redirect', route('vendor.portfolio.index'));

    expect(session('status'))->toBe('1 gambar dimuat naik.')
        ->and(PortfolioItem::count())->toBe(1);
});

it('tells the vendor which formats and size are accepted', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)
        ->get(route('vendor.portfolio.index'))
        ->assertOk()
        ->assertSee('JPG, PNG atau WebP')
        ->assertSee('maksimum '.app(ImageSettings::class)->effectiveUploadMegabytes().'MB');
});

it('keeps a lossless image as png so a qr code still scans', function () {
    Storage::fake('public');

    $path = app(StoreOptimizedImage::class)->handle(UploadedFile::fake()->image('qr.png', 600, 600), 'sites/1', lossless: true);

    expect(getimagesizefromstring(Storage::disk('public')->get($path))['mime'])->toBe('image/png');
});

it('shows listing thumbnails without asking the disk whether each one is there', function () {
    Storage::fake('public');
    $disk = Storage::disk('public');
    $disk->put('vendors/1/baru.webp', 'x');
    $disk->put('vendors/1/baru-thumb.webp', 'x');
    Vendor::factory()->for(Category::first())->create(['cover_image' => 'vendors/1/baru.webp']);
    Vendor::factory()->for(Category::first())->create(['cover_image' => 'vendors/2/lama.jpg']);

    // The thumbnail path is derived, never looked up. On an object store the
    // lookup this replaces was an HTTPS round trip for every image on the page,
    // and the listing draws dozens. neekah:optimize-images is what guarantees
    // the file is there; see StoreOptimizedImage::thumbnailUrl.
    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee($disk->url('vendors/1/baru-thumb.webp'))
        ->assertSee($disk->url('vendors/2/lama-thumb.jpg'))
        ->assertDontSee($disk->url('vendors/2/lama.jpg'));
});

it('optimises images uploaded before optimisation existed, and only once', function () {
    Storage::fake('public');
    $disk = Storage::disk('public');
    $disk->put('portfolio/1/lama.jpg', UploadedFile::fake()->image('lama.jpg', 3000, 2000)->getContent());
    $item = PortfolioItem::factory()
        ->for(Vendor::factory()->for(Category::first()))
        ->create(['path' => 'portfolio/1/lama.jpg']);
    $updatedAt = $item->updated_at;

    $this->artisan('neekah:optimize-images')->assertSuccessful();

    $item->refresh();
    expect($item->path)->toEndWith('.webp')
        ->and($item->updated_at->equalTo($updatedAt))->toBeTrue();
    $disk->assertMissing('portfolio/1/lama.jpg');
    $disk->assertExists(StoreOptimizedImage::thumbnailPath($item->path));

    $this->artisan('neekah:optimize-images')
        ->expectsOutputToContain('0 gambar dioptimumkan')
        ->assertSuccessful();

    expect($item->fresh()->path)->toBe($item->path);
});

it('refuses to hand back a path for a file it could not write', function () {
    Storage::fake('public');

    // The public disk is configured not to throw, so an unwritable directory
    // comes back as false. Ignoring that is how a wedding card ended up
    // pointing at a 404 while the upload reported success.
    Storage::shouldReceive('disk')->with('public')->andReturn($disk = Mockery::mock());
    $disk->shouldReceive('put')->andReturn(false);
    $disk->shouldReceive('delete')->once();

    expect(fn () => app(StoreOptimizedImage::class)
        ->handle(UploadedFile::fake()->image('cover.jpg', 800, 600), 'sites/43'))
        ->toThrow(RuntimeException::class);
});

it('cleans up the half that landed when the other half fails', function () {
    Storage::fake('public');

    Storage::shouldReceive('disk')->with('public')->andReturn($disk = Mockery::mock());
    // The full-size image writes; the thumbnail does not.
    $disk->shouldReceive('put')->once()->andReturn(true);
    $disk->shouldReceive('put')->once()->andReturn(false);
    // A full-size image with no thumbnail is of no use to any page.
    $disk->shouldReceive('delete')->once()->with(Mockery::type('array'));

    expect(fn () => app(StoreOptimizedImage::class)
        ->handle(UploadedFile::fake()->image('cover.jpg', 800, 600), 'sites/43'))
        ->toThrow(RuntimeException::class);
});
