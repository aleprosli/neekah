<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * @return array<int, array{slug: string, name: array<string, string>, icon: string, examples: array<string, string>}>
     */
    public static function categories(): array
    {
        return [
            ['slug' => 'catering', 'name' => ['ms' => 'Catering', 'en' => 'Catering'], 'icon' => '🍽️', 'examples' => ['ms' => 'Buffet, dome, food station', 'en' => 'Buffet, dome, food station']],
            ['slug' => 'pelamin', 'name' => ['ms' => 'Pelamin', 'en' => 'Pelamin (wedding dais)'], 'icon' => '🌸', 'examples' => ['ms' => 'Basic, premium, custom', 'en' => 'Basic, premium, custom']],
            ['slug' => 'decoration', 'name' => ['ms' => 'Decoration', 'en' => 'Decoration'], 'icon' => '✨', 'examples' => ['ms' => 'Dewan, meja, entrance', 'en' => 'Hall, tables, entrance']],
            ['slug' => 'photography', 'name' => ['ms' => 'Photography', 'en' => 'Photography'], 'icon' => '📸', 'examples' => ['ms' => 'Wedding photography', 'en' => 'Wedding photography']],
            ['slug' => 'videography', 'name' => ['ms' => 'Videography', 'en' => 'Videography'], 'icon' => '🎥', 'examples' => ['ms' => 'Highlight, full video', 'en' => 'Highlight, full video']],
            ['slug' => 'emcee', 'name' => ['ms' => 'Emcee', 'en' => 'Emcee'], 'icon' => '🎤', 'examples' => ['ms' => 'Formal, casual, bilingual', 'en' => 'Formal, casual, bilingual']],
            ['slug' => 'makeup', 'name' => ['ms' => 'Makeup', 'en' => 'Makeup'], 'icon' => '💄', 'examples' => ['ms' => 'Bride, groom, family', 'en' => 'Bride, groom, family']],
            ['slug' => 'bridal', 'name' => ['ms' => 'Bridal', 'en' => 'Bridal'], 'icon' => '👗', 'examples' => ['ms' => 'Dress, suit, fitting', 'en' => 'Dress, suit, fitting']],
            ['slug' => 'venue', 'name' => ['ms' => 'Venue', 'en' => 'Venue'], 'icon' => '🏛️', 'examples' => ['ms' => 'Hall, hotel, outdoor', 'en' => 'Hall, hotel, outdoor']],
            ['slug' => 'cake', 'name' => ['ms' => 'Wedding Cake', 'en' => 'Wedding Cake'], 'icon' => '🎂', 'examples' => ['ms' => 'Custom wedding cake', 'en' => 'Custom wedding cake']],
            ['slug' => 'entertainment', 'name' => ['ms' => 'Entertainment', 'en' => 'Entertainment'], 'icon' => '🎶', 'examples' => ['ms' => 'DJ, live band', 'en' => 'DJ, live band']],
            ['slug' => 'invitation', 'name' => ['ms' => 'Invitation', 'en' => 'Invitations'], 'icon' => '💌', 'examples' => ['ms' => 'Digital & physical', 'en' => 'Digital & physical']],
        ];
    }

    public function run(): void
    {
        foreach (self::categories() as $index => $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category + ['sort_order' => $index, 'is_active' => true]);
        }
    }
}
