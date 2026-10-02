<?php

namespace App\Notifications;

use App\Models\Quotation;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A client turned the vendor's quotation down, maybe with a reason. */
class QuotationDeclined extends Notification implements ShouldQueue
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
            'icon' => '📄',
            'title_key' => 'notifications.quotation_declined.title',
            'title_params' => ['name' => $this->quotation->client_name, 'number' => $this->quotation->number],
            'body_key' => 'notifications.quotation_declined.body',
            'url' => route('vendor.quotations.show', $this->quotation),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = NeekahMail::to($notifiable)
            ->subject(__('notifications.quotation_declined.title', ['name' => $this->quotation->client_name, 'number' => $this->quotation->number]))
            ->line(__('notifications.quotation_declined.intro', ['name' => $this->quotation->client_name, 'number' => $this->quotation->number]));

        if (filled($this->quotation->decline_reason)) {
            $message->line('"'.$this->quotation->decline_reason.'"');
        }

        return $message
            ->line(__('notifications.quotation_declined.body'))
            ->action(__('notifications.quotation_declined.action'), route('vendor.quotations.show', $this->quotation));
    }
}
