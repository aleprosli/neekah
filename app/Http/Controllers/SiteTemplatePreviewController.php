<?php

namespace App\Http\Controllers;

use App\Models\SiteTemplate;
use App\Models\WeddingSite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SiteTemplatePreviewController extends Controller
{
    /**
     * The design gallery, grouped by style.
     */
    public function index(Request $request): View
    {
        $templates = SiteTemplate::active()->ordered()->get();
        $style = $request->string('style')->toString();

        return view('sites.templates', [
            'styles' => $templates->pluck('style')->unique()->values(),
            'style' => $style,
            'templates' => $style ? $templates->where('style', $style)->values() : $templates,
        ]);
    }

    /**
     * A full sample of one design, so a couple can judge it before signing up.
     */
    public function show(SiteTemplate $template): View
    {
        abort_unless($template->is_active, 404);

        return view('sites.show', [
            'site' => $this->sample($template),
            'template' => $template,
            'preview' => true,
            'sample' => true,
        ]);
    }

    private function sample(SiteTemplate $template): WeddingSite
    {
        return new WeddingSite([
            'subdomain' => 'contoh',
            'template' => $template->slug,
            'bride_name' => 'Aina Zulkifli',
            'groom_name' => 'Hakim Ismail',
            'bride_parents' => 'Zulkifli bin Hassan & Rohana binti Ahmad',
            'groom_parents' => 'Ismail bin Yusof & Salmah binti Osman',
            'salutation' => "Dengan penuh kesyukuran, kami menjemput Dato' / Datin / Tuan / Puan / Encik / Cik ke majlis perkahwinan anakanda kami",
            'event_date' => now()->addMonths(4)->startOfDay(),
            'starts_at' => '11:00',
            'ends_at' => '16:00',
            'venue_name' => 'Dewan Seri Melati',
            'venue_address' => 'Jalan Sultanah, 05350 Alor Setar, Kedah',
            'map_url' => 'https://maps.google.com',
            'itinerary' => [
                ['time' => '11:00 pagi', 'label' => 'Ketibaan tetamu'],
                ['time' => '12:30 tengah hari', 'label' => 'Ketibaan pengantin'],
                ['time' => '1:00 petang', 'label' => 'Makan beradab'],
                ['time' => '4:00 petang', 'label' => 'Majlis bersurai'],
            ],
            'contacts' => [
                ['name' => 'Puan Rohana', 'phone' => '012-345 6789'],
                ['name' => 'Encik Ismail', 'phone' => '013-456 7890'],
            ],
            'rsvp_enabled' => true,
            'closing_note' => 'Kehadiran dan doa restu daripada tuan/puan amatlah kami hargai.',
        ]);
    }
}
