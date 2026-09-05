<?php

namespace Database\Seeders;

use App\Models\SiteTemplate;
use Illuminate\Database\Seeder;

class SiteTemplateSeeder extends Seeder
{
    private const SCRIPT_VIBES = "'Great Vibes', cursive";

    private const SERIF_CORMORANT = "'Cormorant Garamond', serif";

    private const SERIF_PLAYFAIR = "'Playfair Display', serif";

    private const SANS = "'Instrument Sans', system-ui, sans-serif";

    /**
     * Twenty invitation designs. Each differs by layout skeleton, ornament,
     * motion and type pairing, not only by colour.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function templates(): array
    {
        return [
            // ── Klasik ────────────────────────────────────────────────────
            self::design('seri-gangsa', 'Seri Gangsa', 'Klasik', 'Kertas krim dengan dakwat marun dan emas.',
                'centered', 'floral', 'petals', 'Walimatulurus',
                ['page' => '#fbf6ec', 'ink' => '#4a2c1d', 'name' => '#7a2230', 'accent' => '#c19a4b', 'body' => '#7b5c4a', 'muted' => '#b0937c', 'panel' => '#fffdf8', 'line' => '#e6d5bd', 'buttonBg' => '#7a2230', 'buttonText' => '#ffffff'],
                ['#e8c9a0', '#d8a7ae', '#f0dcc0'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            self::design('gerbang-emas', 'Gerbang Emas', 'Klasik', 'Gambar berbentuk gerbang dengan bingkai emas.',
                'arch', 'deco', 'sparkle', 'Majlis Perkahwinan',
                ['page' => '#faf4ea', 'ink' => '#3f3527', 'name' => '#8a6a2f', 'accent' => '#b8912f', 'body' => '#6d6050', 'muted' => '#a99a80', 'panel' => '#ffffff', 'line' => '#e4d7bd', 'buttonBg' => '#8a6a2f', 'buttonText' => '#ffffff'],
                ['#e8d7ad', '#d9c48b'], self::SCRIPT_VIBES, self::SERIF_PLAYFAIR),

            self::design('bingkai-warisan', 'Bingkai Warisan', 'Klasik', 'Bingkai berganda seperti kad cetak tradisional.',
                'frame', 'geometric', 'none', 'Dengan Penuh Kesyukuran',
                ['page' => '#f7f3ec', 'ink' => '#3b3226', 'name' => '#6b4226', 'accent' => '#a8763c', 'body' => '#6b5f4f', 'muted' => '#a8998a', 'panel' => '#fffdf9', 'line' => '#ddd0ba', 'buttonBg' => '#6b4226', 'buttonText' => '#ffffff'],
                ['#e3d3b6'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            self::design('kemboja', 'Kemboja', 'Klasik', 'Putih gading dengan bunga kemboja terapung.',
                'centered', 'botanical', 'petals', 'Walimatulurus',
                ['page' => '#fdfbf6', 'ink' => '#453c33', 'name' => '#8c6f4e', 'accent' => '#cfa96b', 'body' => '#71655a', 'muted' => '#aa9c8c', 'panel' => '#ffffff', 'line' => '#e8dfd0', 'buttonBg' => '#8c6f4e', 'buttonText' => '#ffffff'],
                ['#f3e6cf', '#efd9b8', '#fff4e2'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            // ── Bunga ─────────────────────────────────────────────────────
            self::design('mawar-pagi', 'Mawar Pagi', 'Bunga', 'Merah jambu lembut dengan hujan kelopak.',
                'centered', 'floral', 'petals', 'Kami Akan Berkahwin',
                ['page' => '#fff6f7', 'ink' => '#5b3746', 'name' => '#8f4257', 'accent' => '#e8b06a', 'body' => '#8a6172', 'muted' => '#c096a5', 'panel' => '#fffafb', 'line' => '#f3d7de', 'buttonBg' => '#d98ca6', 'buttonText' => '#ffffff'],
                ['#f7c9d6', '#f3b8c8', '#fbdcc4', '#f8d5e0'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            self::design('taman-lavender', 'Taman Lavender', 'Bunga', 'Ungu pastel dengan sulur menjalar.',
                'arch', 'vine', 'petals', 'Save The Date',
                ['page' => '#f8f5fd', 'ink' => '#463a58', 'name' => '#6b4f93', 'accent' => '#a98ac9', 'body' => '#6f6285', 'muted' => '#a89bbd', 'panel' => '#fffdff', 'line' => '#e2d8f0', 'buttonBg' => '#6b4f93', 'buttonText' => '#ffffff'],
                ['#dcd0f0', '#c9b6e6', '#efe6fb'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            self::design('kebun-sakura', 'Kebun Sakura', 'Bunga', 'Kelopak sakura berguguran sepanjang kad.',
                'banner', 'botanical', 'petals', 'The Wedding Of',
                ['page' => '#fff8f9', 'ink' => '#4f3a41', 'name' => '#b0576f', 'accent' => '#efb0c0', 'body' => '#7d6a70', 'muted' => '#b9a3ab', 'panel' => '#ffffff', 'line' => '#f5dde3', 'buttonBg' => '#b0576f', 'buttonText' => '#ffffff'],
                ['#fbd3dd', '#f7bccb', '#ffe8ee'], self::SCRIPT_VIBES, self::SERIF_PLAYFAIR),

            self::design('bunga-raya', 'Bunga Raya', 'Bunga', 'Merah cerah dan hijau daun, penuh semangat.',
                'ribbon', 'floral', 'leaves', 'Jemputan Walimatulurus',
                ['page' => '#fff7f4', 'ink' => '#4a2b26', 'name' => '#b23a2f', 'accent' => '#2f7a52', 'body' => '#7c5b53', 'muted' => '#b79a92', 'panel' => '#fffcfa', 'line' => '#f2d9d1', 'buttonBg' => '#b23a2f', 'buttonText' => '#ffffff'],
                ['#f6c6b8', '#a8d6b8', '#f9ded4'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            self::design('daun-hijau', 'Daun Hijau', 'Bunga', 'Hijau sage dengan dedaun berayun.',
                'split', 'botanical', 'leaves', 'The Wedding Of',
                ['page' => '#f6f8f4', 'ink' => '#37423a', 'name' => '#3f6b4f', 'accent' => '#8ba888', 'body' => '#5f6b60', 'muted' => '#9aa89a', 'panel' => '#ffffff', 'line' => '#dde5da', 'buttonBg' => '#3f6b4f', 'buttonText' => '#ffffff'],
                ['#cfe0cb', '#b9d3b4', '#e6efe2'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            // ── Moden ─────────────────────────────────────────────────────
            self::design('putih-tenang', 'Putih Tenang', 'Moden', 'Putih hangat, dakwat arang, ruang lapang.',
                'minimal', 'none', 'none', 'The Wedding Of',
                ['page' => '#faf9f6', 'ink' => '#2f2f2d', 'name' => '#1f1f1e', 'accent' => '#93a68b', 'body' => '#5f5f5c', 'muted' => '#a3a29e', 'panel' => '#ffffff', 'line' => '#e5e4df', 'buttonBg' => '#1f1f1e', 'buttonText' => '#ffffff'],
                ['#e8e6df'], self::SERIF_PLAYFAIR, self::SANS),

            self::design('garis-halus', 'Garis Halus', 'Moden', 'Tipografi besar dengan garis penanda nipis.',
                'minimal', 'deco', 'none', 'Kami Berkahwin',
                ['page' => '#ffffff', 'ink' => '#242424', 'name' => '#111111', 'accent' => '#c2a878', 'body' => '#585858', 'muted' => '#9c9c9c', 'panel' => '#fafafa', 'line' => '#e6e6e6', 'buttonBg' => '#111111', 'buttonText' => '#ffffff'],
                ['#ece5d8'], self::SERIF_PLAYFAIR, self::SANS),

            self::design('kanvas-pasir', 'Kanvas Pasir', 'Moden', 'Warna pasir dan tanah, tenang dan moden.',
                'split', 'none', 'none', 'Save The Date',
                ['page' => '#f7f3ee', 'ink' => '#463f37', 'name' => '#7c6650', 'accent' => '#b39373', 'body' => '#6b6157', 'muted' => '#a79a8c', 'panel' => '#fffdfa', 'line' => '#e6dccf', 'buttonBg' => '#7c6650', 'buttonText' => '#ffffff'],
                ['#e8dbc9'], self::SERIF_PLAYFAIR, self::SANS),

            self::design('mozek-kenangan', 'Mozek Kenangan', 'Moden', 'Susunan gambar seperti album.',
                'mosaic', 'none', 'none', 'Our Wedding Day',
                ['page' => '#f4f4f2', 'ink' => '#2c2c2c', 'name' => '#1a1a1a', 'accent' => '#9b8f7a', 'body' => '#5a5a58', 'muted' => '#9b9b98', 'panel' => '#ffffff', 'line' => '#e2e2de', 'buttonBg' => '#1a1a1a', 'buttonText' => '#ffffff'],
                ['#e0ded7'], self::SERIF_PLAYFAIR, self::SANS),

            self::design('pita-moden', 'Pita Moden', 'Moden', 'Pita warna melintang sebagai penanda bahagian.',
                'ribbon', 'deco', 'none', 'The Wedding Of',
                ['page' => '#fbfaf8', 'ink' => '#2e2e2c', 'name' => '#20302c', 'accent' => '#3f6b5c', 'body' => '#5d5d59', 'muted' => '#9d9d98', 'panel' => '#ffffff', 'line' => '#e4e4de', 'buttonBg' => '#20302c', 'buttonText' => '#ffffff'],
                ['#d6e2dc'], self::SERIF_PLAYFAIR, self::SANS),

            // ── Malam ─────────────────────────────────────────────────────
            self::design('malam-emas', 'Malam Emas', 'Malam', 'Biru malam dengan emas berkilau.',
                'centered', 'deco', 'sparkle', 'Resepsi Malam',
                ['page' => '#0d1024', 'ink' => '#e8e6f2', 'name' => '#ffffff', 'accent' => '#d9b06a', 'body' => '#b9b7c9', 'muted' => '#7f7d94', 'panel' => '#171a33', 'line' => '#2b2f4d', 'buttonBg' => '#d9b06a', 'buttonText' => '#0d1024', 'dark' => true],
                ['#d9b06a', '#f0d9a8', '#8b93c9'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            self::design('zamrud', 'Zamrud', 'Malam', 'Hijau zamrud pekat dengan emas lembut.',
                'frame', 'botanical', 'sparkle', 'Majlis Resepsi',
                ['page' => '#0b1f1a', 'ink' => '#e4ece7', 'name' => '#ffffff', 'accent' => '#c9a75f', 'body' => '#adc0b6', 'muted' => '#7c9187', 'panel' => '#122b24', 'line' => '#22443a', 'buttonBg' => '#c9a75f', 'buttonText' => '#0b1f1a', 'dark' => true],
                ['#c9a75f', '#7fae95'], self::SCRIPT_VIBES, self::SERIF_PLAYFAIR),

            self::design('marun-malam', 'Marun Malam', 'Malam', 'Marun gelap dengan bunga emas.',
                'arch', 'floral', 'petals', 'Walimatulurus',
                ['page' => '#22090f', 'ink' => '#f0e3e6', 'name' => '#ffffff', 'accent' => '#d8a657', 'body' => '#c3a9ae', 'muted' => '#8e7379', 'panel' => '#32131b', 'line' => '#4a2029', 'buttonBg' => '#d8a657', 'buttonText' => '#22090f', 'dark' => true],
                ['#d8a657', '#c98da5'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            self::design('langit-senja', 'Langit Senja', 'Malam', 'Ungu senja dengan cahaya lembut.',
                'banner', 'vine', 'sparkle', 'Save The Date',
                ['page' => '#1a1030', 'ink' => '#ece7f7', 'name' => '#ffffff', 'accent' => '#e0a3c4', 'body' => '#bdb3d4', 'muted' => '#8a809f', 'panel' => '#251845', 'line' => '#3a2a63', 'buttonBg' => '#e0a3c4', 'buttonText' => '#1a1030', 'dark' => true],
                ['#e0a3c4', '#a99ae0', '#f3d9e8'], self::SCRIPT_VIBES, self::SERIF_CORMORANT),

            // ── Islamik ───────────────────────────────────────────────────
            self::design('nur-geometri', 'Nur Geometri', 'Islamik', 'Corak geometri Islam dengan warna teduh.',
                'frame', 'geometric', 'none', 'Bismillahirrahmanirrahim',
                ['page' => '#f6f7f4', 'ink' => '#33403a', 'name' => '#2f5d4f', 'accent' => '#b9985a', 'body' => '#5f6d66', 'muted' => '#96a29b', 'panel' => '#ffffff', 'line' => '#dde4de', 'buttonBg' => '#2f5d4f', 'buttonText' => '#ffffff'],
                ['#dbe5dd', '#e8dcc2'], self::SERIF_PLAYFAIR, self::SERIF_CORMORANT),

            self::design('tenun-songket', 'Tenun Songket', 'Islamik', 'Motif songket keemasan atas latar teh.',
                'ribbon', 'geometric', 'none', 'Walimatulurus',
                ['page' => '#f5efe3', 'ink' => '#42372a', 'name' => '#7b4a1e', 'accent' => '#bb8f3c', 'body' => '#6d6151', 'muted' => '#a4977f', 'panel' => '#fffbf3', 'line' => '#e3d5b9', 'buttonBg' => '#7b4a1e', 'buttonText' => '#ffffff'],
                ['#e6d2a8', '#d8bd85'], self::SERIF_PLAYFAIR, self::SERIF_CORMORANT),
        ];
    }

    /**
     * @param  array<string, mixed>  $palette
     * @param  array<int, string>  $petals
     * @return array<string, mixed>
     */
    private static function design(
        string $slug,
        string $name,
        string $style,
        string $description,
        string $layout,
        string $ornament,
        string $motion,
        string $eyebrow,
        array $palette,
        array $petals,
        string $script,
        string $body,
    ): array {
        return [
            'slug' => $slug,
            'name' => $name,
            'style' => $style,
            'description' => $description,
            'design' => [
                'layout' => $layout,
                'ornament' => $ornament,
                'motion' => $motion,
                'eyebrow' => $eyebrow,
                'type' => ['script' => $script, 'body' => $body],
                'palette' => $palette,
                'petals' => $petals,
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::templates() as $index => $template) {
            SiteTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                [...$template, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
