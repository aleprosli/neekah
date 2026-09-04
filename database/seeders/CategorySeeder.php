<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * @return array<int, array{slug: string, name: string, icon: string, examples: string}>
     */
    public static function categories(): array
    {
        return [
            ['slug' => 'catering', 'name' => 'Catering', 'icon' => '🍽️', 'examples' => 'Buffet, dome, food station'],
            ['slug' => 'pelamin', 'name' => 'Pelamin', 'icon' => '🌸', 'examples' => 'Basic, premium, custom'],
            ['slug' => 'decoration', 'name' => 'Decoration', 'icon' => '✨', 'examples' => 'Dewan, meja, entrance'],
            ['slug' => 'photography', 'name' => 'Photography', 'icon' => '📸', 'examples' => 'Wedding photography'],
            ['slug' => 'videography', 'name' => 'Videography', 'icon' => '🎥', 'examples' => 'Highlight, full video'],
            ['slug' => 'emcee', 'name' => 'Emcee', 'icon' => '🎤', 'examples' => 'Formal, casual, bilingual'],
            ['slug' => 'makeup', 'name' => 'Makeup', 'icon' => '💄', 'examples' => 'Bride, groom, family'],
            ['slug' => 'bridal', 'name' => 'Bridal', 'icon' => '👗', 'examples' => 'Dress, suit, fitting'],
            ['slug' => 'venue', 'name' => 'Venue', 'icon' => '🏛️', 'examples' => 'Hall, hotel, outdoor'],
            ['slug' => 'cake', 'name' => 'Wedding Cake', 'icon' => '🎂', 'examples' => 'Custom wedding cake'],
            ['slug' => 'entertainment', 'name' => 'Entertainment', 'icon' => '🎶', 'examples' => 'DJ, live band'],
            ['slug' => 'invitation', 'name' => 'Invitation', 'icon' => '💌', 'examples' => 'Digital & physical'],
        ];
    }

    public function run(): void
    {
        foreach (self::categories() as $index => $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category + ['sort_order' => $index, 'is_active' => true]);
        }
    }
}
