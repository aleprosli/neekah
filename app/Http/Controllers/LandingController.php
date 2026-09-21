<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Vendor;
use App\Support\ContactSettings;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * The About page. Neekah is a network for now: couples find vendors here
     * and deal with them directly, and the platform takes no payment and no
     * commission. Nothing on this page may promise otherwise.
     */
    public function __invoke(Seo $seo, ContactSettings $contact): View
    {
        $seo->title(__('seo.landing.title'))
            ->description(__('seo.landing.description'));

        $categories = Category::active()->ordered()->get();

        return view('landing', [
            'flow' => $this->flow(),
            'categories' => $categories,
            'vendors' => Vendor::query()->approved()->with('category')->orderByDesc('score')->orderBy('id')->limit(6)->get(),
            'features' => $this->features(),
            'vendorBenefits' => $this->vendorBenefits(),
            'helpUrl' => $contact->whatsappUrl(__('pages.landing.whatsapp_message')),
        ]);
    }

    /**
     * @return array<int, array{label: string, description: string}>
     */
    private function flow(): array
    {
        return [
            ['label' => __('pages.landing.flow.plan'), 'description' => __('pages.landing.flow.plan_detail')],
            ['label' => __('pages.landing.flow.search'), 'description' => __('pages.landing.flow.search_detail')],
            ['label' => __('pages.landing.flow.contact'), 'description' => __('pages.landing.flow.contact_detail')],
            ['label' => __('pages.landing.flow.deal'), 'description' => __('pages.landing.flow.deal_detail')],
            ['label' => __('pages.landing.flow.invite'), 'description' => __('pages.landing.flow.invite_detail')],
            ['label' => __('pages.landing.flow.celebrate'), 'description' => __('pages.landing.flow.celebrate_detail')],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string}>
     */
    private function features(): array
    {
        return [
            ['icon' => '🔎', 'title' => __('pages.landing.features.search'), 'description' => __('pages.landing.features.search_detail')],
            ['icon' => '✉️', 'title' => __('pages.landing.features.card'), 'description' => __('pages.landing.features.card_detail')],
            ['icon' => '📋', 'title' => __('pages.landing.features.checklist'), 'description' => __('pages.landing.features.checklist_detail')],
            ['icon' => '💰', 'title' => __('pages.landing.features.budget'), 'description' => __('pages.landing.features.budget_detail')],
            ['icon' => '🗓️', 'title' => __('pages.landing.features.timeline'), 'description' => __('pages.landing.features.timeline_detail')],
            ['icon' => '👥', 'title' => __('pages.landing.features.guests'), 'description' => __('pages.landing.features.guests_detail')],
        ];
    }

    /**
     * What a vendor gets from being listed. Only what is true today: no
     * commission and no payment through Neekah, so nothing here is about
     * bookings recorded or payments made on the platform.
     *
     * @return array<int, string>
     */
    private function vendorBenefits(): array
    {
        return [
            __('pages.landing.benefits.listing'),
            __('pages.landing.benefits.whatsapp'),
            __('pages.landing.benefits.packages'),
            __('pages.landing.benefits.calendar'),
            __('pages.landing.benefits.enquiries'),
        ];
    }
}
