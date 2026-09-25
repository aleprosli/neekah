<?php

namespace App\Notifications;

use App\Enums\VendorFeature;
use App\Models\Enquiry;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A couple wrote to a vendor. On Basic the vendor learns only that someone
 * did: reading and answering enquiries is Neekah Pro.
 */
class EnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enquiry $enquiry) {}

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
        if (! $this->readable()) {
            return [
                'icon' => '🔒',
                'title_key' => 'notifications.enquiry_received.locked_title',
                'body_key' => 'notifications.enquiry_received.locked_body',
                'url' => route('vendor.enquiries.index'),
            ];
        }

        return [
            'icon' => '💬',
            'title_key' => 'notifications.enquiry_received.title',
            'title_params' => ['name' => $this->enquiry->user->name],
            'body_key' => 'notifications.enquiry_received.body',
            'url' => route('vendor.enquiries.show', $this->enquiry),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $this->readable()) {
            return NeekahMail::to($notifiable)
                ->subject(__('notifications.enquiry_received.locked_title'))
                ->line(__('notifications.enquiry_received.locked_body'))
                ->action(__('notifications.enquiry_received.locked_action'), route('vendor.pro.index'));
        }

        $message = NeekahMail::to($notifiable)
            ->subject(__('notifications.enquiry_received.subject', ['name' => $this->enquiry->user->name]))
            ->line(__('notifications.enquiry_received.intro', ['name' => $this->enquiry->user->name]))
            ->line('"'.$this->enquiry->message.'"');

        if ($this->enquiry->event_date) {
            $message->line(__('notifications.event_date', ['date' => $this->enquiry->event_date->translatedFormat('l, j F Y')]));
        }

        return $message
            ->line(__('notifications.enquiry_received.reply_fast'))
            ->action(__('notifications.actions.reply_enquiry'), route('vendor.enquiries.show', $this->enquiry));
    }

    private function readable(): bool
    {
        return $this->enquiry->vendor->hasFeature(VendorFeature::Enquiries);
    }
}
