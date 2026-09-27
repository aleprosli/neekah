<?php

use App\Actions\StoreOptimizedImage;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('shows every category on one page, in display order, with counts', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $names = collect($props['categories'])->pluck('name');

            return $names->count() === Category::count()
                && $names->first() === Category::ordered()->first()->name
                && collect($props['stats'])->firstWhere('label', 'Jumlah kategori')['value'] === Category::count();
        });
});

it('shows each category with how many vendors use it and where it lives on the marketplace', function () {
    $category = Category::where('slug', 'photography')->sole();
    Vendor::factory()->for($category)->count(2)->create();
    Vendor::factory()->for($category)->pending()->create();
    $hidden = Category::factory()->create(['is_active' => false]);

    $categories = collect($this->actingAs($this->admin)
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->viewData('props')['categories'])->keyBy('id');

    expect($categories[$category->id])
        ->vendors_count->toBe(3)
        ->vendors_label->toBe('3 vendor')
        ->approved_label->toBe('2 diluluskan')
        ->marketplace_url->toBe(route('vendors.index', ['category' => 'photography']))
        ->and($categories[$hidden->id])
        ->vendors_label->toBe('Tiada vendor')
        ->marketplace_url->toBeNull();
});

it('brings a rejected edit back open on the same category with what was typed', function () {
    $category = Category::ordered()->skip(2)->first();

    $this->actingAs($this->admin)
        ->from(route('admin.categories.index'))
        ->put(route('admin.categories.update', $category), [
            'category_id' => $category->id,
            'name' => ['ms' => '', 'en' => 'Half typed'],
            'icon' => '📷',
            'is_active' => 0,
        ])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasErrors('name.ms');

    $old = $this->actingAs($this->admin)->get(route('admin.categories.index'))->viewData('props')['old'];

    expect($old)
        ->category_id->toEqual($category->id)
        ->name->toBe(['ms' => null, 'en' => 'Half typed'])
        ->icon->toBe('📷')
        ->is_active->toBeFalse();
});

it('opens the page with no form when nothing was rejected', function () {
    expect($this->actingAs($this->admin)->get(route('admin.categories.index'))->viewData('props')['old'])->toBeNull();
});

it('saves the order an admin dragged the categories into', function () {
    [$first, $second] = Category::ordered()->take(2)->get()->all();

    $this->actingAs($this->admin)
        ->putJson(route('admin.categories.order'), [
            'items' => [
                ['id' => $second->id, 'sort_order' => 0],
                ['id' => $first->id, 'sort_order' => 1],
            ],
        ])
        ->assertOk();

    expect(Category::ordered()->first()->id)->toBe($second->id);
});

it('lets only an admin reorder categories', function () {
    $payload = ['items' => [['id' => Category::first()->id, 'sort_order' => 0]]];

    $this->putJson(route('admin.categories.order'), $payload)->assertUnauthorized();
    $this->actingAs(User::factory()->create())->putJson(route('admin.categories.order'), $payload)->assertForbidden();
});

it('puts a new category at the end of the list when no position is given', function () {
    Category::query()->update(['sort_order' => 5]);

    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => ['ms' => 'Kereta Pengantin', 'en' => 'Bridal car'], 'icon' => '🚗', 'is_active' => 1])
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::whereTranslated('name', 'Kereta Pengantin')->sole()->sort_order)->toBe(6);
});

it('uploads a category picture, shows it wherever the illustration goes, and replaces it', function () {
    Storage::fake('public');
    $category = Category::first();

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $category), [
            'name' => ['ms' => $category->name],
            'icon' => $category->icon,
            'is_active' => 1,
            'image' => UploadedFile::fake()->image('kategori.jpg', 512, 512),
        ])
        ->assertRedirect(route('admin.categories.index'));

    $first = $category->fresh();
    expect($first->image)->not->toBeNull()
        ->and($first->illustrationUrl())->toBe(StoreOptimizedImage::thumbnailUrl($first->image));
    Storage::disk('public')->assertExists($first->image);

    // A second upload replaces the first rather than leaving it behind.
    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => ['ms' => $category->name],
        'icon' => $category->icon,
        'is_active' => 1,
        'image' => UploadedFile::fake()->image('baru.jpg', 512, 512),
    ]);

    expect($category->fresh()->image)->not->toBe($first->image);
    Storage::disk('public')->assertMissing($first->image);
});

it('falls back to the shipped illustration when the upload is removed', function () {
    Storage::fake('public');
    $category = Category::where('slug', 'catering')->sole();

    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => ['ms' => $category->name],
        'icon' => $category->icon,
        'is_active' => 1,
        'image' => UploadedFile::fake()->image('kategori.jpg', 512, 512),
    ]);

    $uploaded = $category->fresh()->image;

    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => ['ms' => $category->name],
        'icon' => $category->icon,
        'is_active' => 1,
        'remove_image' => 1,
    ]);

    expect($category->fresh()->image)->toBeNull()
        ->and($category->fresh()->illustrationUrl())->toBe(asset('img/icon/catering.svg'));
    Storage::disk('public')->assertMissing($uploaded);
});

it('removes the picture of a deleted category', function () {
    Storage::fake('public');
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => ['ms' => $category->name],
        'icon' => $category->icon,
        'is_active' => 1,
        'image' => UploadedFile::fake()->image('kategori.jpg', 512, 512),
    ]);

    $path = $category->fresh()->image;

    $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();

    Storage::disk('public')->assertMissing($path);
});
