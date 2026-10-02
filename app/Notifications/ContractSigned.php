<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A contract was signed: the vendor hears about it in the app and by email;
 * the client gets an emailed link to their signed copy.
 */
class ContractSigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Contract $contract, public bool $forClient = false) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->forClient ? ['mail'] : ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '✍️',
            'title_key' => 'notifications.contract_signed.title',
            'title_params' => ['name' => $this->contract->client_name, 'number' => $this->contract->number],
            'body_key' => 'notifications.contract_signed.body',
            'url' => route('vendor.contracts.show', $this->contract),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = ['name' => $this->contract->client_name, 'number' => $this->contract->number, 'vendor' => $this->contract->vendor->name];

        if ($this->forClient) {
            return NeekahMail::to($this->contract->client_name)
                ->subject(__('notifications.contract_signed.client_subject', $params))
                ->line(__('notifications.contract_signed.client_intro', $params))
                ->action(__('notifications.contract_signed.client_action'), $this->contract->publicUrl());
        }

        return NeekahMail::to($notifiable)
            ->subject(__('notifications.contract_signed.title', $params))
            ->line(__('notifications.contract_signed.intro', $params))
            ->line(__('notifications.contract_signed.body'))
            ->action(__('notifications.contract_signed.action'), route('vendor.contracts.show', $this->contract));
    }
}
