<?php

namespace App\Support;

use App\Enums\VendorTier;
use App\Models\Vendor;

/**
 * What Neekah Pro gives, told as before and after (VendorProShowcase.vue):
 * the scattered notes of a vendor's day, the Pro feature that replaces each
 * one, and Pro Elite for the vendors who climb. The vendor's Pro page and the
 * About page tell the same story from here, so neither can fall behind.
 *
 * No reach numbers are promised: while traffic is low a row of view counts
 * turns vendors away (owner, 3 Oct 2026).
 */
class ProStory
{
    /** @var list<string> keys under pages.pro.benefits and pages.pro.pains, in the order the notes are scattered */
    public const BENEFITS = ['enquiries', 'quotations', 'contracts', 'booking', 'boost', 'ranking', 'badge'];

    /**
     * The showcase's props. $cta is the last tile of the grid.
     *
     * @param  array{url: string|null, title: string, body: string, label: string|null}  $cta
     * @return array<string, mixed>
     */
    public static function props(?Vendor $vendor, array $cta): array
    {
        $settings = app(ProSettings::class);

        return [
            'eyebrow' => __('pages.pro.story.eyebrow'),
            'headline' => __('pages.pro.story.headline'),
            'lead' => __('pages.pro.story.lead'),
            'footnote' => __('pages.pro.story.footnote'),
            'afterHeading' => __('pages.pro.story.after_heading'),
            'afterLead' => __('pages.pro.story.after_lead'),
            'cta' => $cta,
            'benefits' => self::benefits(),
            'elite' => $settings->eliteEnabled() ? self::elite($vendor, $settings) : null,
        ];
    }

    /**
     * @return list<array{key: string, title: string, body: string, pain: string}>
     */
    public static function benefits(): array
    {
        return array_map(fn (string $key): array => [
            'key' => $key,
            'title' => __('pages.pro.benefits.'.$key.'.title'),
            'body' => __('pages.pro.benefits.'.$key.'.body'),
            'pain' => __('pages.pro.pains.'.$key),
        ], self::BENEFITS);
    }

    /**
     * Pro Elite: what it adds and, for a signed-in vendor, how they get there.
     * The rank it needs comes from VendorTier, so the copy can't drift from
     * the ladder. A visitor is told how it is kept instead.
     *
     * @return array{kicker: string, title: string, body: string, earned: string, isElite: bool, status: string|null, perks: list<array{key: string, title: string, body: string}>}
     */
    private static function elite(?Vendor $vendor, ProSettings $settings): array
    {
        $isElite = (bool) $vendor?->isElite();
        $top = VendorTier::Top->requirements();

        return [
            'kicker' => __('pages.pro.elite.kicker'),
            'title' => $isElite ? __('pages.pro.elite.you_are') : __('pages.pro.elite.title'),
            'body' => __('pages.pro.elite.body'),
            'earned' => __('pages.pro.elite.earned'),
            'isElite' => $isElite,
            'status' => match (true) {
                $isElite => null,
                $vendor === null => __('pages.pro.elite.kept'),
                default => __('pages.pro.elite.how', [
                    'tier' => $vendor->tier->label(),
                    'reviews' => $top['reviews'],
                    'rating' => number_format($top['rating'], 1),
                ]),
            },
            'perks' => array_map(fn (string $perk): array => [
                'key' => $perk,
                'title' => __('pages.pro.elite.perks.'.$perk.'.title'),
                'body' => __('pages.pro.elite.perks.'.$perk.'.body', ['count' => $settings->eliteBonusTokens()]),
            ], ['badge', 'row', 'order', 'tokens']),
        ];
    }
}
