<?php

namespace App\Http\Controllers;

use App\Actions\RenderInvitationPreview;
use App\Models\SiteTemplate;
use App\Support\Card\CardProps;
use App\Support\Card\SampleCard;
use App\Support\Seo;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

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

        // Read once, not once per tile.
        $thumbnails = $this->thumbnails($templates);

        return view('sites.templates', [
            'categories' => $categories,
            'category' => $category,
            'templates' => $shown,
            // Each tile is the real design, drawn by the card renderer at thumbnail
            // size, so the gallery can never show something a card would not.
            //
            // Building all of these is the most expensive thing this page does
            // and the result is the same for every visitor, so it is cached. The
            // per-visitor half - the CSRF token and any validation errors - is
            // added by VueProps::for after the cache, never inside it.
            'thumbnails' => $shown->mapWithKeys(fn (SiteTemplate $template): array => [
                $template->slug => VueProps::for($thumbnails[$template->slug] ?? []),
            ]),
        ]);
    }

    /**
     * The drawing instructions for every active design, by slug.
     *
     * Keyed by language, because the sample content and the renderer's own
     * labels are translated, and by the templates' own version, so an admin
     * editing or hiding a design is picked up without anything to clear. Held
     * for a day rather than forever because the sample date is relative to
     * today; a day old is close enough for a tile nobody reads the date off.
     *
     * @param  Collection<int, SiteTemplate>  $templates
     * @return array<string, array<string, mixed>>
     */
    private function thumbnails(Collection $templates): array
    {
        $version = $templates->count().'-'.($templates->max('updated_at')?->getTimestamp() ?? 0);

        return Cache::remember(
            'card-thumbnails:'.app()->getLocale().':'.$version,
            now()->endOfDay(),
            fn (): array => $templates
                ->mapWithKeys(fn (SiteTemplate $template): array => [
                    $template->slug => CardProps::forThumbnail($template, SampleCard::site($template)),
                ])
                ->all(),
        );
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

        $sample = SampleCard::site($template);

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

        return response($render->draw(SampleCard::site($template), $template), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
