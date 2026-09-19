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
                'title' => 'Pilih template & alamat web',
                'hint' => 'Pilih reka bentuk dan alamat sendiri, contohnya aina-hakim.'.config('neekah.site_domain').'.',
                'done' => $site !== null,
                'action' => 'Pilih template',
                'url' => $editor.'#template',
            ],
            [
                'key' => 'details',
                'title' => 'Lengkapkan maklumat majlis',
                'hint' => 'Tempat, alamat dan atur cara, supaya tetamu tahu ke mana dan bila.',
                'done' => $site !== null && filled($site->venue_name) && filled($site->venue_address) && filled($site->itinerary),
                'action' => 'Isi maklumat',
                'url' => $editor.'#majlis',
            ],
            [
                'key' => 'publish',
                'title' => 'Siarkan kad',
                'hint' => 'Semak pratonton dahulu. Kad hanya boleh dibuka tetamu selepas disiarkan.',
                'done' => (bool) $site?->is_published,
                'action' => 'Pratonton & siarkan',
                'url' => $editor,
            ],
            [
                'key' => 'share',
                'title' => 'Kongsi dengan tetamu',
                'hint' => 'Hantar pautan di WhatsApp, atau pautan peribadi setiap tetamu dari senarai tetamu.',
                'done' => (bool) $site?->is_published && $site->views > 0,
                'action' => 'Buka senarai tetamu',
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
