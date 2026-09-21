<?php

namespace App\Support;

use App\Models\Wedding;
use App\Models\WeddingSite;

/**
 * The four steps from "no card" to "guests have it", worked out from what the
 * couple has actually done. Couples who registered were not making a card at
 * all, so the dashboard and the editor both show where they are and what next.
 */
class InvitationSetup
{
    private ?WeddingSite $site;

    public function __construct(private Wedding $wedding)
    {
        $this->site = $wedding->site;
    }

    /**
     * @return array<int, array{key: string, title: string, hint: string, done: bool, action: string, url: string}>
     */
    public function steps(): array
    {
        $site = $this->site;
        $editor = route('site.edit');

        return [
            [
                'key' => 'template',
                'title' => __('pages.card_setup.template_title'),
                'hint' => __('pages.card_setup.template_hint', ['domain' => config('neekah.site_domain')]),
                'done' => $site !== null,
                'action' => __('pages.card_setup.template_action'),
                'url' => $editor.'#template',
            ],
            [
                'key' => 'details',
                'title' => __('pages.card_setup.details_title'),
                'hint' => __('pages.card_setup.details_hint'),
                'done' => $site !== null && filled($site->venue_name) && filled($site->venue_address) && filled($site->itinerary),
                'action' => __('pages.card_setup.details_action'),
                'url' => $editor.'#majlis',
            ],
            [
                'key' => 'publish',
                'title' => __('pages.card_setup.publish_title'),
                'hint' => __('pages.card_setup.publish_hint'),
                'done' => (bool) $site?->is_published,
                'action' => __('pages.card_setup.publish_action'),
                'url' => $editor,
            ],
            [
                'key' => 'share',
                'title' => __('pages.card_setup.share_title'),
                'hint' => __('pages.card_setup.share_hint'),
                'done' => (bool) $site?->is_published && $site->views > 0,
                'action' => __('pages.card_setup.share_action'),
                'url' => route('guests.index'),
            ],
        ];
    }

    public function completed(): int
    {
        return collect($this->steps())->where('done', true)->count();
    }

    /**
     * The first step not yet done, or null once every step is.
     *
     * @return array{key: string, title: string, hint: string, done: bool, action: string, url: string}|null
     */
    public function next(): ?array
    {
        return collect($this->steps())->firstWhere('done', false);
    }

    public function isFinished(): bool
    {
        return $this->next() === null;
    }
}
