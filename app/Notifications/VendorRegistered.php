<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Support\ContactSettings;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The welcome a vendor gets the moment they sign up, while their profile is
 * still pending review.
 */
class VendorRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Vendor $vendor) {}

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
            'icon' => '🎉',
            'title_key' => 'notifications.vendor_registered.title',
            'body_key' => 'notifications.vendor_registered.body',
            'url' => route('vendor.profile.edit'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // NeekahMail already sets the sign-off; this one re-set it by hand.
        return NeekahMail::to($notifiable)
            ->subject(__('notifications.vendor_registered.subject'))
            ->greeting(__('notifications.thanks_name', ['name' => $notifiable->name]))
            ->line(__('notifications.vendor_registered.received', ['vendor' => $this->vendor->name]))
            ->line(__('notifications.vendor_registered.meanwhile'))
            ->action(__('notifications.actions.complete_profile'), route('vendor.profile.edit'))
            ->line(__('notifications.vendor_registered.will_email'))
            ->line(app(ContactSettings::class)->supportSentence());
    }
}
