<?php

namespace App\Notifications;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorStatusChanged extends Notification implements ShouldQueue
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
            'icon' => '🏪',
            'title_key' => 'notifications.vendor_status.title',
            'title_params' => ['status' => $this->vendor->status->label()],
            'body_key' => 'notifications.vendor_status.body',
            'body_params' => ['vendor' => $this->vendor->name, 'status' => $this->vendor->status->label()],
            'url' => route('vendor.dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = NeekahMail::to($notifiable)
            ->subject(__('notifications.vendor_status.subject', ['status' => $this->vendor->status->label()]));

        return match ($this->vendor->status) {
            VendorStatus::Approved => $message
                ->line(__('notifications.vendor_status.approved', ['vendor' => $this->vendor->name]))
                ->line(__('notifications.vendor_status.tier', ['tier' => $this->vendor->tier->label()]))
                ->action(__('notifications.actions.view_public_profile'), route('vendors.show', $this->vendor)),
            VendorStatus::Suspended => $message
                ->line(__('notifications.vendor_status.suspended', ['vendor' => $this->vendor->name]))
                ->line(__('notifications.vendor_status.contact_admin'))
                ->action(__('notifications.actions.open_dashboard'), route('vendor.dashboard')),
            VendorStatus::Rejected => $message
                ->line(__('notifications.vendor_status.rejected', ['vendor' => $this->vendor->name]))
                ->line(__('notifications.vendor_status.rejected_next'))
                ->action(__('notifications.actions.open_dashboard'), route('vendor.dashboard')),
            VendorStatus::Pending => $message
                ->line(__('notifications.vendor_status.pending', ['vendor' => $this->vendor->name]))
                ->action(__('notifications.actions.open_dashboard'), route('vendor.dashboard')),
        };
    }
}
