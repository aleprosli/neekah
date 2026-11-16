<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\Card\SampleCard;
use App\Support\FeaturePages;
use App\Support\ProSettings;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * The About page. Neekah is a network for now: couples find vendors here
     * and deal with them directly, and the platform takes no payment and no
     * commission. Nothing on this page may promise otherwise.
     */
    public function __invoke(Seo $seo, FeaturePages $pages, ProSettings $pro): View
    {
        $seo->title(__('seo.landing.title'))
            ->description(__('seo.landing.description'));

        return view('landing', [
            'flow' => $this->flow(),
            // Three real designs fanned out in the hero: the card is the one thing on
            // this page a couple can picture themselves holding.
            'covers' => SampleCard::covers(['neekah-signature', 'royal-songket-gold', 'midnight-luxury']),
            'categoryCount' => Category::active()->count(),
            'features' => $this->features(),
            'vendorBenefits' => $this->vendorBenefits(),
            // Marketplace, Kenangan, Pro and Boost each have a page of their own
            // (FeaturePageController); About only points at the ones on sale.
            'explore' => $pages->available(),
            'eliteUrl' => $pro->isEnabled() && $pro->eliteEnabled() ? route('features.pro').'#elite' : null,
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string, description: string}>
     */
    private function flow(): array
    {
        return [
            ['key' => 'plan', 'label' => __('pages.landing.flow.plan'), 'description' => __('pages.landing.flow.plan_detail')],
            ['key' => 'search', 'label' => __('pages.landing.flow.search'), 'description' => __('pages.landing.flow.search_detail')],
            ['key' => 'contact', 'label' => __('pages.landing.flow.contact'), 'description' => __('pages.landing.flow.contact_detail')],
            ['key' => 'deal', 'label' => __('pages.landing.flow.deal'), 'description' => __('pages.landing.flow.deal_detail')],
            ['key' => 'invite', 'label' => __('pages.landing.flow.invite'), 'description' => __('pages.landing.flow.invite_detail')],
            ['key' => 'celebrate', 'label' => __('pages.landing.flow.celebrate'), 'description' => __('pages.landing.flow.celebrate_detail')],
        ];
    }

    /**
     * @return array<int, array{icon: string, title: string, description: string}>
     */
    private function features(): array
    {
        return [
            // Line icons from components/nav-icon, not emoji: an emoji is drawn by
            // whichever operating system opens the page, at its own weight.
            ['icon' => 'search', 'title' => __('pages.landing.features.search'), 'description' => __('pages.landing.features.search_detail')],
            ['icon' => 'mail', 'title' => __('pages.landing.features.card'), 'description' => __('pages.landing.features.card_detail')],
            ['icon' => 'check', 'title' => __('pages.landing.features.checklist'), 'description' => __('pages.landing.features.checklist_detail')],
            ['icon' => 'wallet', 'title' => __('pages.landing.features.budget'), 'description' => __('pages.landing.features.budget_detail')],
            ['icon' => 'calendar', 'title' => __('pages.landing.features.timeline'), 'description' => __('pages.landing.features.timeline_detail')],
            ['icon' => 'users', 'title' => __('pages.landing.features.guests'), 'description' => __('pages.landing.features.guests_detail')],
        ];
    }

    /**
     * What a vendor gets from being listed. Only what is true today: no
     * commission and no payment through Neekah, so nothing here is about
     * bookings recorded or payments made on the platform. Pro is an optional
     * extra on top of a listing that stays free.
     *
     * @return array<int, string>
     */
    private function vendorBenefits(): array
    {
        return [
            __('pages.landing.benefits.listing'),
            __('pages.landing.benefits.whatsapp'),
            __('pages.landing.benefits.packages'),
            // Calendar and enquiries are Pro once Pro is on sale; the Pro page
            // lists them there instead.
            ...(app(ProSettings::class)->isEnabled() ? [__('pages.landing.benefits.pro')] : [__('pages.landing.benefits.calendar'), __('pages.landing.benefits.enquiries')]),
        ];
    }
}
