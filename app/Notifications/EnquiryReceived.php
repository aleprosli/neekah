<?php

namespace App\Notifications;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnquiryReceived extends Notification
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
            'title' => "Enquiry baharu daripada {$this->enquiry->user->name}",
            'body' => 'Balas dalam 24 jam untuk mengekalkan response rate anda.',
            'url' => route('vendor.enquiries.show', $this->enquiry),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Enquiry baharu daripada '.$this->enquiry->user->name)
            ->greeting('Hai '.$notifiable->name.',')
            ->line($this->enquiry->user->name.' menghantar enquiry kepada anda:')
            ->line('"'.$this->enquiry->message.'"');

        if ($this->enquiry->event_date) {
            $message->line('Tarikh majlis: '.$this->enquiry->event_date->translatedFormat('l, j F Y'));
        }

        return $message
            ->line('Balas dengan cepat untuk mengekalkan response rate yang tinggi.')
            ->action('Balas enquiry', route('vendor.enquiries.show', $this->enquiry))
            ->salutation('Terima kasih, Neekah');
    }
}
