<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A vendor's contract, emailed to a client who may have no account. */
class ContractSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Contract $contract) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendor = $this->contract->vendor->name;

        return NeekahMail::to($this->contract->client_name)
            ->subject(__('notifications.contract_sent.subject', ['vendor' => $vendor, 'number' => $this->contract->number]))
            ->line(__('notifications.contract_sent.intro', ['vendor' => $vendor]))
            ->line(__('notifications.contract_sent.body'))
            ->action(__('notifications.contract_sent.action'), $this->contract->publicUrl());
    }
}
