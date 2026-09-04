<?php

namespace App\Notifications;

use App\Enums\ViolationAction;
use App\Models\VendorViolation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorViolationRecorded extends Notification
{
    use Queueable;

    public function __construct(public VendorViolation $violation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $action = $this->violation->action;

        $message = (new MailMessage)
            ->subject('Pelanggaran direkod: '.$action->label())
            ->greeting('Hai '.$notifiable->name.',')
            ->line('Satu laporan terhadap '.$this->violation->vendor->name.' telah disahkan oleh admin.')
            ->line('Jenis pelanggaran: '.$this->violation->type->label())
            ->line('Pelanggaran ke-'.$this->violation->offence_number.' · Tindakan: '.$action->label());

        if ($this->violation->admin_note) {
            $message->line('Nota admin: '.$this->violation->admin_note);
        }

        $message->line(match ($action) {
            ViolationAction::Warning => 'Ini adalah amaran pertama. Pastikan semua booking dan pembayaran direkod melalui platform.',
            ViolationAction::PointDeduction => 'Point anda dipotong dan ranking diturunkan satu tahap.',
            ViolationAction::Suspension => 'Akaun anda digantung sementara dan tidak dipaparkan di marketplace.',
            ViolationAction::Removal => 'Akaun anda telah disingkirkan daripada marketplace kerana pelanggaran berulang.',
        });

        return $message
            ->action('Buka dashboard', route('vendor.dashboard'))
            ->salutation('Terima kasih, Neekah');
    }
}
