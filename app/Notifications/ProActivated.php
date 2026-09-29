<?php

namespace App\Notifications;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Pro is on, and the date it now runs to. The receipt itself is emailed
 * by IssueReceipt.
 */
class ProActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /**
     * The email is the receipt (PaymentReceipt, sent by IssueReceipt); this
     * only rings the bell.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '⭐',
            'title_key' => 'notifications.pro_activated.title',
            'title_params' => [],
            'body_key' => 'notifications.pro_activated.body',
            'body_params' => ['date' => $this->endsAt()],
            'url' => route('vendor.pro.index'),
        ];
    }

    private function endsAt(): string
    {
        return Carbon::parse($this->payment->detail('ends_at'))->translatedFormat('j F Y');
    }
}
