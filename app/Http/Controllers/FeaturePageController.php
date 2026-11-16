<?php

namespace App\Http\Controllers;

use App\Enums\CameraTier;
use App\Enums\VendorFeature;
use App\Enums\VendorPlan;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\BoostSettings;
use App\Support\CameraSettings;
use App\Support\ContactSettings;
use App\Support\FeaturePages;
use App\Support\ProSettings;
use App\Support\ProStory;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

/**
 * One page per thing Neekah offers, each built on the same frame
 * (x-site.feature-page): Marketplace and Neekah Kenangan for couples, Neekah
 * Pro and Boost for vendors. A page whose feature is not on sale answers 404,
 * so nothing is advertised before anyone can buy it. Neekah is still a network:
 * nothing here may promise payment or booking through the platform.
 */
class FeaturePageController extends Controller
{
    public function __construct(private readonly FeaturePages $pages) {}

    public function marketplace(Seo $seo, ContactSettings $contact): View
    {
        $this->describe($seo, 'marketplace');

        return view('features.marketplace', [
            'categories' => Category::active()->ordered()->get(),
            'vendors' => Vendor::query()->approved()->with('category')->orderByDesc('score')->orderBy('id')->limit(6)->get(),
            'helpUrl' => $contact->whatsappUrl(__('pages.landing.whatsapp_message')),
            'explore' => $this->pages->available(except: 'marketplace'),
        ]);
    }

    public function kenangan(Seo $seo, CameraSettings $settings): View
    {
        abort_unless($this->pages->isAvailable('kenangan'), 404);

        $this->describe($seo, 'kenangan');

        return view('features.kenangan', [
            'steps' => array_map(fn (string $step): array => [
                'key' => $step,
                'title' => __('pages.landing.kenangan.steps.'.$step.'.title'),
                'body' => __('pages.landing.kenangan.steps.'.$step.'.body', ['days' => $settings->retentionDays()]),
            ], ['scan', 'share', 'wish', 'keep']),
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
            'explore' => $this->pages->available(except: 'kenangan'),
        ]);
    }

    /**
     * Neekah Pro told as before and after (Pro Elite inside it), then Basic
     * against Pro with the admin's prices.
     */
    public function pro(Seo $seo, ProSettings $pro): View
    {
        abort_unless($this->pages->isAvailable('pro'), 404);

        $this->describe($seo, 'pro');

        $describe = fn (VendorFeature $feature): array => ['label' => $feature->label(), 'description' => $feature->description()];

        return view('features.pro', [
            'plans' => [
                'basic' => array_values(array_map($describe, array_filter(VendorFeature::cases(), fn (VendorFeature $feature): bool => ! $feature->requiresPro()))),
                'monthly' => $pro->price(VendorPlan::Monthly),
                'yearly' => $pro->price(VendorPlan::Yearly),
                'elite' => $pro->eliteEnabled() ? $pro->eliteBonusTokens() : null,
            ],
            // The same before-and-after story as the vendor's own Pro page.
            'proStory' => ProStory::props(null, [
                'url' => '#pelan',
                'title' => __('pages.pro.story.cta_guest_title'),
                'body' => __('pages.pro.story.cta_guest_body', ['price' => 'RM'.number_format($pro->price(VendorPlan::Monthly))]),
                'label' => __('pages.pro.story.cta_guest'),
            ]),
            'explore' => $this->pages->available(except: 'pro'),
        ]);
    }

    /**
     * How a boost token lifts a vendor, and every way a token is come by.
     */
    public function boost(Seo $seo, BoostSettings $settings): View
    {
        abort_unless($this->pages->isAvailable('boost'), 404);

        $this->describe($seo, 'boost');

        return view('features.boost', [
            'welcome' => $settings->welcomeTokens(),
            'proMonthly' => $this->pages->isAvailable('pro') ? $settings->proMonthlyTokens() : null,
            'packs' => array_values(array_filter(array_map(fn (string $pack): ?array => $settings->pack($pack), BoostSettings::PACKS))),
            'explore' => $this->pages->available(except: 'boost'),
        ]);
    }

    private function describe(Seo $seo, string $key): void
    {
        $seo->title(__('seo.features.'.$key.'.title'))
            ->description(__('seo.features.'.$key.'.description'))
            ->breadcrumbs([
                'Neekah' => route('vendors.index'),
                __('nav.about') => route('landing'),
                __('pages.features.'.$key.'.name') => route('features.'.$key),
            ]);
    }
}
