<?php

namespace App\Notifications;

use App\Models\Quotation;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A vendor's quotation, emailed to a client who may have no account: the
 * link is all they need.
 */
class QuotationSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quotation $quotation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendor = $this->quotation->vendor->name;

        return NeekahMail::to($this->quotation->client_name)
            ->subject(__('notifications.quotation_sent.subject', ['vendor' => $vendor, 'number' => $this->quotation->number]))
            ->line(__('notifications.quotation_sent.intro', ['vendor' => $vendor]))
            ->line(__('notifications.quotation_sent.total', ['total' => Quotation::money($this->quotation->total)]))
            ->line(__('notifications.quotation_sent.valid_until', ['date' => $this->quotation->valid_until->translatedFormat('j F Y')]))
            ->action(__('notifications.quotation_sent.action'), $this->quotation->publicUrl());
    }
}
