<?php

namespace App\Http\Controllers;

use App\Actions\RenderInvitationPreview;
use App\Models\SiteTemplate;
use App\Models\WeddingSite;
use App\Support\Card\CardProps;
use App\Support\Seo;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteTemplatePreviewController extends Controller
{
    /**
     * The design gallery, one shelf per category.
     */
    public function index(Request $request, Seo $seo): View
    {
        $templates = SiteTemplate::active()->ordered()->get();
        $category = $request->string('category')->toString();
        $categories = $templates->pluck('category')->unique()->values();

        if (! $categories->contains($category)) {
            $category = '';
        }

        $seo->title($category ? __('seo.templates.title_style', ['style' => __('pages.template_category.'.$category)]) : __('seo.templates.title'))
            ->description(__('seo.templates.description', ['count' => $templates->count()]))
            ->canonical(url()->current().($category ? '?'.http_build_query(['category' => $category]) : ''));

        $shown = $category ? $templates->where('category', $category)->values() : $templates;

        return view('sites.templates', [
            'categories' => $categories,
            'category' => $category,
            'templates' => $shown,
            // Each tile is the real design, drawn by the card renderer at thumbnail
            // size, so the gallery can never show something a card would not.
            'thumbnails' => $shown->mapWithKeys(fn (SiteTemplate $template): array => [
                $template->slug => VueProps::for(CardProps::forThumbnail($template, $this->sample($template))),
            ]),
        ]);
    }

    /**
     * A full sample of one design, so a couple can judge it before signing up.
     */
    public function show(SiteTemplate $template, Seo $seo): View
    {
        abort_unless($template->is_active, 404);

        $seo->image(
            route('sites.templates.image', $template),
            RenderInvitationPreview::WIDTH,
            RenderInvitationPreview::HEIGHT,
        )
            ->title(__('seo.templates.show_title', ['name' => $template->name]))
            ->description(__('seo.templates.show_description', ['name' => $template->name, 'style' => $template->style]));

        $sample = $this->sample($template);

        return view('sites.show', [
            'site' => $sample,
            'template' => $template,
            'preview' => true,
            'sample' => true,
            'props' => VueProps::for(CardProps::forSample($template, $sample)),
        ]);
    }

    /**
     * The link preview for a template page, drawn from the same sample the
     * page itself shows.
     */
    public function previewImage(SiteTemplate $template, RenderInvitationPreview $render): Response
    {
        abort_unless($template->is_active, 404);

        return response($render->draw($this->sample($template), $template), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    private function sample(SiteTemplate $template): WeddingSite
    {
        return new WeddingSite([
            'subdomain' => 'contoh',
            'template' => $template->slug,
            'bride_name' => 'Nur Aina Zulkifli',
            'groom_name' => 'Hakim Ismail',
            'bride_short' => 'Aina',
            'groom_short' => 'Hakim',
            'bride_father' => 'Zulkifli bin Hassan',
            'bride_mother' => 'Rohana binti Ahmad',
            'groom_father' => 'Ismail bin Yusof',
            'groom_mother' => 'Salmah binti Osman',
            'bride_bio' => 'Anak kedua, guru yang gemar berkebun.',
            'groom_bio' => 'Anak bongsu, jurutera yang gemar mendaki.',
            'salutation' => "Dengan penuh kesyukuran, kami menjemput Dato' / Datin / Tuan / Puan / Encik / Cik ke majlis perkahwinan anakanda kami",
            'invitation_note' => 'Dengan penuh rasa syukur, kami menjemput tuan/puan untuk hadir memberkati majlis perkahwinan anakanda kami. Kehadiran dan doa restu tuan/puan amatlah kami hargai.',
            'event_date' => now()->addMonths(4)->startOfDay(),
            'starts_at' => '11:00',
            'ends_at' => '16:00',
            'venue_name' => 'Dewan Seri Melati',
            'venue_address' => "Jalan Sultanah, 05350\nAlor Setar, Kedah",
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
            'wishes_enabled' => true,
            'closing_note' => 'Kehadiran dan doa restu daripada tuan/puan amatlah kami hargai.',
        ]);
    }
}
