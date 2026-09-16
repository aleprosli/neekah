<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckMailDelivery extends Command
{
    protected $signature = 'neekah:mail-test {email : Where to send the test}';

    protected $description = 'Send a test email and report the sender this server is actually using';

    /**
     * Whether a message reaches the inbox or the spam folder is decided by the
     * receiving side, not here, so this prints what the receiver will check:
     * the From domain, and whether the transport can authenticate for it.
     */
    public function handle(): int
    {
        $address = (string) $this->argument('email');
        $from = (string) config('mail.from.address');

        $this->components->twoColumnDetail('Mailer', (string) config('mail.default'));
        $this->components->twoColumnDetail('Host', (string) config('mail.mailers.smtp.host'));
        $this->components->twoColumnDetail('Username', (string) config('mail.mailers.smtp.username'));
        $this->components->twoColumnDetail('From', $from);

        try {
            Mail::raw(
                'Ini emel ujian daripada '.config('app.name').' di '.config('app.url').'.'.PHP_EOL.PHP_EOL
                    .'Buka "Show original" dalam Gmail dan semak tiga baris ini:'.PHP_EOL
                    .'  SPF: PASS, DKIM: PASS, DMARC: PASS.'.PHP_EOL
                    .'Mana-mana yang bukan PASS ialah sebab emel ini jatuh ke spam.',
                fn ($message) => $message->to($address)->subject('Ujian penghantaran emel '.config('app.name')),
            );
        } catch (Throwable $exception) {
            $this->components->error('Gagal dihantar: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Dihantar ke '.$address.'. Semak "Show original" untuk keputusan SPF, DKIM dan DMARC.');

        if (! str_ends_with((string) config('mail.mailers.smtp.username'), '@'.$this->domainOf($from))) {
            $this->components->warn(
                'Alamat pengirim ('.$from.') bukan domain yang SMTP ini log masuk sebagai. '
                .'Tanpa DKIM untuk domain itu, penerima tidak boleh mengesahkan emel ini dan akan menganggapnya spam.'
            );
        }

        return self::SUCCESS;
    }

    private function domainOf(string $address): string
    {
        return str_contains($address, '@') ? explode('@', $address)[1] : $address;
    }
}
