<?php

namespace App\Http\Controllers;

use App\Enums\CameraTier;
use App\Enums\VendorFeature;
use App\Enums\VendorPlan;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\BoostSettings;
use App\Support\CameraSettings;
use App\Support\Card\SampleCard;
use App\Support\ContactSettings;
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
    public function __invoke(Seo $seo, ContactSettings $contact): View
    {
        $seo->title(__('seo.landing.title'))
            ->description(__('seo.landing.description'));

        $categories = Category::active()->ordered()->get();

        return view('landing', [
            'flow' => $this->flow(),
            // Three real designs fanned out in the hero: the card is the one thing on
            // this page a couple can picture themselves holding.
            'covers' => SampleCard::covers(['neekah-signature', 'royal-songket-gold', 'midnight-luxury']),
            'categories' => $categories,
            'vendors' => Vendor::query()->approved()->with('category')->orderByDesc('score')->orderBy('id')->limit(6)->get(),
            'features' => $this->features(),
            'vendorBenefits' => $this->vendorBenefits(),
            // What Neekah sells, each only once an admin has opened it, so the
            // page never advertises something nobody can buy yet.
            'kenangan' => $this->kenangan(),
            'plans' => $this->plans(),
            'boost' => $this->boost(),
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
            // Calendar and enquiries are Pro once Pro is on sale; the plans
            // section below lists them there instead.
            ...(app(ProSettings::class)->isEnabled() ? [__('pages.landing.benefits.pro')] : [__('pages.landing.benefits.calendar'), __('pages.landing.benefits.enquiries')]),
        ];
    }

    /**
     * Neekah Kenangan's two packages, for couples.
     *
     * @return array{tiers: list<array{label: string, price: float, pro: bool, features: list<string>}>, retention: int}|null
     */
    private function kenangan(): ?array
    {
        $settings = app(CameraSettings::class);

        if (! $settings->isEnabled()) {
            return null;
        }

        return [
            'retention' => $settings->retentionDays(),
            'tiers' => array_map(function (CameraTier $tier) use ($settings): array {
                $limits = $settings->limitsFor($tier);

                return [
                    'label' => $tier->label(),
                    'price' => $settings->price($tier),
                    'pro' => $tier === CameraTier::Pro,
                    'features' => array_values(array_filter([
                        $limits->maxPhotos ? __('ui.camera.feature_photos_capped', ['count' => $limits->maxPhotos]) : __('ui.camera.feature_photos_unlimited'),
                        __($limits->photoPixels >= 3000 ? 'ui.camera.feature_full_hd' : 'ui.camera.feature_hd'),
                        $limits->allowsVideo
                            ? __('ui.camera.feature_video', ['mb' => $limits->videoMaxMegabytes, 'minutes' => (int) round($limits->videoMaxSeconds / 60)])
                            : null,
                        __('ui.camera.feature_messages'),
                        $tier === CameraTier::Pro ? __('ui.camera.feature_voice') : null,
                        __('ui.camera.feature_qr'),
                    ])),
                ];
            }, CameraTier::cases()),
        ];
    }

    /**
     * Basic against Pro for vendors, from the features each plan opens.
     *
     * @return array{basic: list<array{label: string, description: string}>, pro: list<array{label: string, description: string}>, monthly: float, yearly: float, elite: int|null}|null
     */
    private function plans(): ?array
    {
        $pro = app(ProSettings::class);

        if (! $pro->isEnabled()) {
            return null;
        }

        $describe = fn (VendorFeature $feature): array => ['label' => $feature->label(), 'description' => $feature->description()];

        return [
            'basic' => array_values(array_map($describe, array_filter(VendorFeature::cases(), fn (VendorFeature $feature): bool => ! $feature->requiresPro()))),
            'pro' => array_values(array_map($describe, array_filter(VendorFeature::cases(), fn (VendorFeature $feature): bool => $feature->requiresPro()))),
            'monthly' => $pro->price(VendorPlan::Monthly),
            'yearly' => $pro->price(VendorPlan::Yearly),
            'elite' => $pro->eliteEnabled() ? $pro->eliteBonusTokens() : null,
        ];
    }

    /**
     * How boost tokens lift a vendor, and where tokens come from.
     *
     * @return array{welcome: int, pro_monthly: int, packs: list<array{tokens: int, price: float}>}|null
     */
    private function boost(): ?array
    {
        $settings = app(BoostSettings::class);

        if (! $settings->isEnabled()) {
            return null;
        }

        return [
            'welcome' => $settings->welcomeTokens(),
            'pro_monthly' => $settings->proMonthlyTokens(),
            'packs' => array_values(array_map(fn (string $pack): array => $settings->pack($pack), BoostSettings::PACKS)),
        ];
    }
}
