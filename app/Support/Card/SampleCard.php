<?php

namespace App\Support\Card;

use App\Models\SiteTemplate;
use App\Models\WeddingSite;

/**
 * The couple every design is shown with before anyone has made it their own:
 * the gallery, a design's preview page and the About page all draw the same
 * card, so a visitor recognises it from one place to the next.
 *
 * Made up, and kept away from anyone real: no card on neekah.my is used as an
 * example, and these names are chosen not to match a couple who has one.
 */
class SampleCard
{
    public static function site(SiteTemplate $template): WeddingSite
    {
        return new WeddingSite([
            'subdomain' => 'contoh',
            'template' => $template->slug,
            'bride_name' => 'Irdina Maisarah binti Kamarul',
            'groom_name' => 'Danish Iskandar bin Razali',
            'bride_short' => 'Irdina',
            'groom_short' => 'Danish',
            'bride_father' => 'Kamarul bin Othman',
            'bride_mother' => 'Suraya binti Hamid',
            'groom_father' => 'Razali bin Yaakob',
            'groom_mother' => 'Zaharah binti Ismail',
            'bride_bio' => 'Anak kedua, guru yang gemar berkebun.',
            'groom_bio' => 'Anak bongsu, jurutera yang gemar mendaki.',
            'salutation' => "Dengan penuh kesyukuran, kami menjemput Dato' / Datin / Tuan / Puan / Encik / Cik ke majlis perkahwinan anakanda kami",
            'invitation_note' => 'Dengan penuh rasa syukur, kami menjemput tuan/puan untuk hadir memberkati majlis perkahwinan anakanda kami. Kehadiran dan doa restu tuan/puan amatlah kami hargai.',
            'event_date' => now()->addMonths(4)->startOfDay(),
            'starts_at' => '11:00',
            'ends_at' => '16:00',
            'venue_name' => 'Dewan Seri Kenanga',
            'venue_address' => "Jalan Kenanga 2, 43650\nBandar Baru Bangi, Selangor",
            'map_url' => 'https://maps.google.com',
            'itinerary' => [
                ['time' => '11:00 pagi', 'label' => 'Ketibaan tetamu'],
                ['time' => '12:30 tengah hari', 'label' => 'Ketibaan pengantin'],
                ['time' => '1:00 petang', 'label' => 'Makan beradab'],
                ['time' => '4:00 petang', 'label' => 'Majlis bersurai'],
            ],
            'contacts' => [
                ['name' => 'Puan Suraya', 'phone' => '012-000 0000'],
                ['name' => 'Encik Razali', 'phone' => '013-000 0000'],
            ],
            'rsvp_enabled' => true,
            'wishes_enabled' => true,
            'music_enabled' => in_array($template->slug, ['sutera-zaitun', 'lili-kasih', 'taman-bulan', 'kasih-sutera', 'melur-purnama', 'mihrab-kasih', 'taman-zaitun', 'gerbang-wisteria'], true),
            'closing_note' => 'Kehadiran dan doa restu daripada tuan/puan amatlah kami hargai.',
        ]);
    }

    /**
     * Cover-only props for a handful of designs, for a page that shows the cards
     * fanned out rather than one at a time.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, array{slug: string, name: string, card: array<string, mixed>}>
     */
    public static function covers(array $slugs): array
    {
        return SiteTemplate::active()->whereIn('slug', $slugs)->get()
            ->sortBy(fn (SiteTemplate $template): int => (int) array_search($template->slug, $slugs, true))
            ->map(fn (SiteTemplate $template): array => [
                'slug' => $template->slug,
                'name' => $template->name,
                'card' => CardProps::forThumbnail($template, self::site($template)),
            ])
            ->values()
            ->all();
    }
}
