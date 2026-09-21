<?php

namespace App\Notifications;

use App\Models\Enquiry;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
}
