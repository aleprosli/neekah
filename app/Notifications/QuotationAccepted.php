<?php

namespace App\Notifications;

use App\Models\Quotation;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A client agreed to the vendor's quotation. */
class QuotationAccepted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quotation $quotation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '✅',
            'title_key' => 'notifications.quotation_accepted.title',
            'title_params' => ['name' => $this->quotation->client_name, 'number' => $this->quotation->number],
            'body_key' => 'notifications.quotation_accepted.body',
            'url' => route('vendor.quotations.show', $this->quotation),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.quotation_accepted.title', ['name' => $this->quotation->client_name, 'number' => $this->quotation->number]))
            ->line(__('notifications.quotation_accepted.intro', [
                'name' => $this->quotation->client_name,
                'number' => $this->quotation->number,
                'total' => Quotation::money($this->quotation->total),
            ]))
            ->line(__('notifications.quotation_accepted.body'))
            ->action(__('notifications.quotation_accepted.action'), route('vendor.quotations.show', $this->quotation));
    }
}
