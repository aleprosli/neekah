<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Support\ContactSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The welcome a vendor gets the moment they sign up, while their profile is
 * still pending review.
 */
class VendorRegistered extends Notification
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
            'title' => 'Selamat datang ke Neekah',
            'body' => 'Lengkapkan profil, pakej dan portfolio anda sementara admin menyemak permohonan.',
            'url' => route('vendor.profile.edit'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contact = app(ContactSettings::class);

        $message = (new MailMessage)
            ->subject('Terima kasih kerana mendaftar dengan Neekah')
            ->greeting('Terima kasih, '.$notifiable->name.'!')
            ->line('Permohonan '.$this->vendor->name.' telah kami terima dan kini menunggu semakan admin.')
            ->line('Sementara menunggu, lengkapkan profil, pakej dan portfolio anda. Profil yang lengkap disemak dengan lebih cepat dan muncul lebih tinggi dalam carian pengantin.')
            ->action('Lengkapkan profil', route('vendor.profile.edit'))
            ->line('Kami akan emel anda sebaik sahaja permohonan diluluskan.');

        foreach ($this->supportLines($contact) as $line) {
            $message->line($line);
        }

        return $message->salutation('Terima kasih, Neekah');
    }

    /**
     * How to reach us, using whatever the admin has filled in under Tetapan.
     *
     * @return array<int, string>
     */
    private function supportLines(ContactSettings $contact): array
    {
        $channels = array_filter([
            $contact->email() ?: null,
            $contact->phone() ?: null,
            $contact->whatsappUrl(),
        ]);

        return $channels === []
            ? ['Ada sebarang pertanyaan? Hubungi kami melalui '.config('app.url').'.']
            : ['Ada sebarang pertanyaan? Hubungi kami di '.implode(' · ', $channels).'.'];
    }
}
