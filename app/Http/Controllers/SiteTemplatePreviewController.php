<?php

namespace App\Http\Controllers;

use App\Actions\RenderInvitationPreview;
use App\Models\SiteTemplate;
use App\Support\Card\CardProps;
use App\Support\Card\SampleCard;
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
                $template->slug => VueProps::for(CardProps::forThumbnail($template, SampleCard::site($template))),
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
