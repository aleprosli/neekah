<?php

namespace App\Notifications;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnquiryReplied extends Notification
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
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '💬',
            'title' => "{$this->enquiry->vendor->name} membalas enquiry anda",
            'body' => 'Lihat balasan dan teruskan ke tempahan.',
            'url' => route('enquiries.show', $this->enquiry),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->enquiry->vendor->name.' telah membalas enquiry anda')
            ->greeting('Hai '.$notifiable->name.',')
            ->line($this->enquiry->vendor->name.' membalas:')
            ->line('"'.$this->enquiry->reply.'"')
            ->action('Lihat balasan', route('enquiries.show', $this->enquiry))
            ->salutation('Terima kasih, Neekah');
    }
}
