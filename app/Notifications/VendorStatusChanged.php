<?php

namespace App\Notifications;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorStatusChanged extends Notification
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
            'title' => "Status vendor: {$this->vendor->status->label()}",
            'body' => "{$this->vendor->name} kini {$this->vendor->status->label()}.",
            'url' => route('vendor.dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Status vendor anda: '.$this->vendor->status->label())
            ->greeting('Hai '.$notifiable->name.',');

        return match ($this->vendor->status) {
            VendorStatus::Approved => $message
                ->line('Tahniah! '.$this->vendor->name.' telah diluluskan dan kini dipaparkan di marketplace Neekah.')
                ->line('Tahap anda: '.$this->vendor->tier->label().' Vendor.')
                ->action('Lihat profil awam', route('vendors.show', $this->vendor)),
            VendorStatus::Suspended => $message
                ->line($this->vendor->name.' telah digantung sementara dan tidak dipaparkan di marketplace.')
                ->line('Sila hubungi pihak admin untuk maklumat lanjut.')
                ->action('Buka dashboard', route('vendor.dashboard')),
            VendorStatus::Rejected => $message
                ->line('Maaf, permohonan '.$this->vendor->name.' tidak dapat diluluskan pada masa ini.')
                ->line('Anda boleh melengkapkan profil dan menghubungi admin untuk semakan semula.')
                ->action('Buka dashboard', route('vendor.dashboard')),
            VendorStatus::Pending => $message
                ->line('Profil '.$this->vendor->name.' kini menunggu semakan admin.')
                ->action('Buka dashboard', route('vendor.dashboard')),
        };
    }
}
