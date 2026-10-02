<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Wedding;

/**
 * What a couple should do next, worked out from what they have actually
 * done. The dashboard shows only the first few still open — one at a time is
 * what gets done; a page full of numbers is why couples stopped coming back.
 *
 * The digital card is one step here, carrying InvitationSetup's own four
 * stages, so the card guide keeps its logic and its wording.
 */
class CoupleNextSteps
{
    /** How many open steps the dashboard shows at once. */
    public const SHOWN = 3;

    /** @var list<array{key: string, title: string, hint: string, action: string, url: string, done: bool, icon: string}>|null */
    private ?array $steps = null;

    public function __construct(private Wedding $wedding) {}

    /**
     * Every step in the order a couple should take them.
     *
     * @return list<array{key: string, title: string, hint: string, action: string, url: string, done: bool, icon: string}>
     */
    public function all(): array
    {
        return $this->steps ??= $this->build();
    }

    /**
     * @return list<array{key: string, title: string, hint: string, action: string, url: string, done: bool, icon: string}>
     */
    private function build(): array
    {
        $wedding = $this->wedding;
        $card = new InvitationSetup($wedding);
        $cardNext = $card->next();
        $cardDone = $card->completed();
        $cardTotal = count($card->steps());

        return [
            [
                'key' => 'card',
                'title' => $cardDone === 0 ? __('pages.card_setup.heading_start') : __('pages.card_setup.heading_continue'),
                'hint' => __('pages.card_setup.progress', ['done' => $cardDone, 'total' => $cardTotal]).($cardNext ? ' '.$cardNext['title'].'.' : ''),
                'action' => $cardNext['action'] ?? '',
                'url' => $cardNext['url'] ?? route('site.edit'),
                'done' => $cardNext === null,
                'icon' => 'mail',
            ],
            [
                'key' => 'partner',
                'title' => __('pages.next_steps.partner_title'),
                'hint' => __('pages.next_steps.partner_hint'),
                'action' => __('pages.next_steps.partner_action'),
                'url' => '#pasangan',
                'done' => $wedding->partner() !== null || $wedding->invitations()->pending()->exists(),
                'icon' => 'rings',
            ],
            [
                'key' => 'guests',
                'title' => __('pages.next_steps.guests_title'),
                'hint' => __('pages.next_steps.guests_hint'),
                'action' => __('pages.next_steps.guests_action'),
                'url' => route('guests.index'),
                'done' => $wedding->guests()->exists(),
                'icon' => 'users',
            ],
            [
                'key' => 'checklist',
                'title' => __('pages.next_steps.checklist_title'),
                'hint' => __('pages.next_steps.checklist_hint'),
                'action' => __('pages.next_steps.checklist_action'),
                'url' => route('checklist.index'),
                'done' => $wedding->tasks()->whereNotNull('completed_at')->exists(),
                'icon' => 'check',
            ],
            [
                'key' => 'vendor',
                'title' => __('pages.next_steps.vendor_title'),
                'hint' => __('pages.next_steps.vendor_hint'),
                'action' => __('pages.next_steps.vendor_action'),
                'url' => route('vendors.index'),
                'done' => $wedding->bookings()->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed, BookingStatus::Completed])->exists(),
                'icon' => 'search',
            ],
            [
                'key' => 'budget',
                'title' => __('pages.next_steps.budget_title'),
                'hint' => __('pages.next_steps.budget_hint'),
                'action' => __('pages.next_steps.budget_action'),
                'url' => route('budget.index'),
                'done' => (float) $wedding->budget > 0,
                'icon' => 'wallet',
            ],
        ];
    }

    /**
     * The first few steps still open.
     *
     * @return list<array{key: string, title: string, hint: string, action: string, url: string, done: bool, icon: string}>
     */
    public function open(): array
    {
        return array_values(array_slice(array_filter($this->all(), fn (array $step): bool => ! $step['done']), 0, self::SHOWN));
    }

    public function completed(): int
    {
        return count(array_filter($this->all(), fn (array $step): bool => $step['done']));
    }
}
