<?php

namespace App\Notifications;

use App\Models\Enquiry;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnquiryReplied extends Notification implements ShouldQueue
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
            'title_key' => 'notifications.enquiry_replied.title',
            'title_params' => ['vendor' => $this->enquiry->vendor->name],
            'body_key' => 'notifications.enquiry_replied.body',
            'url' => route('enquiries.show', $this->enquiry),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.enquiry_replied.subject', ['vendor' => $this->enquiry->vendor->name]))
            ->line(__('notifications.enquiry_replied.intro', ['vendor' => $this->enquiry->vendor->name]))
            ->line('"'.$this->enquiry->reply.'"')
            ->action(__('notifications.actions.view_reply'), route('enquiries.show', $this->enquiry));
    }
}
