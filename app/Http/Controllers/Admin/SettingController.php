<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactSettingsRequest;
use App\Http\Requests\UpdateImageSettingsRequest;
use App\Http\Requests\UpdatePaymentSettingsRequest;
use App\Http\Requests\UpdateSeoSettingsRequest;
use App\Http\Requests\UpdateTelegramSettingsRequest;
use App\Http\Requests\UpdateTurnstileSettingsRequest;
use App\Support\ContactSettings;
use App\Support\ImageSettings;
use App\Support\PaymentSettings;
use App\Support\Seo;
use App\Support\SeoSettings;
use App\Support\TelegramSettings;
use App\Support\TurnstileSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(ContactSettings $contact, SeoSettings $seo, TurnstileSettings $turnstile, TelegramSettings $telegram, ImageSettings $images, PaymentSettings $payments): View
    {
        return view('admin.settings.edit', [
            'props' => VueProps::for([
                'sections' => [
                    $this->contactSection($contact->all()),
                    $this->seoSection($seo->all()),
                    $this->turnstileSection($turnstile->all(), $turnstile->isEnabled()),
                    $this->telegramSection($telegram->all(), $telegram->isEnabled()),
                    $this->paymentSection($payments->all(), $payments->manualTransferEnabled()),
                    $this->imageSection($images),
                ],
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function contactSection(array $values): array
    {
        return [
            'id' => 'perhubungan',
            'icon' => '📞',
            'label' => 'Perhubungan',
            'title' => 'Maklumat perhubungan',
            'description' => 'Dipaparkan di footer setiap halaman awam. Biarkan kosong untuk menyembunyikan satu-satu maklumat.',
            'action' => route('admin.settings.contact'),
            'submit' => 'Simpan maklumat perhubungan',
            'columns' => true,
            'fields' => [
                ['name' => 'phone', 'label' => 'Nombor telefon', 'type' => 'tel', 'value' => $values['phone'], 'placeholder' => '03-1234 5678', 'help' => 'Dipaparkan sebagai pautan panggilan.'],
                ['name' => 'whatsapp', 'label' => 'Nombor WhatsApp', 'type' => 'tel', 'value' => $values['whatsapp'], 'placeholder' => '60123456789', 'help' => 'Dengan kod negara. Kosong bermakna nombor telefon digunakan.'],
                ['name' => 'email', 'label' => 'Emel', 'type' => 'email', 'value' => $values['email'], 'placeholder' => 'hello@neekah.my'],
                ['name' => 'hours', 'label' => 'Waktu operasi', 'value' => $values['hours'], 'placeholder' => 'Isnin – Jumaat, 9 pagi – 6 petang'],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea', 'rows' => 2, 'wide' => true, 'value' => $values['address'], 'placeholder' => 'No. 1, Jalan Contoh, 50000 Kuala Lumpur'],
                ['name' => 'facebook', 'label' => 'Facebook', 'type' => 'url', 'value' => $values['facebook'], 'placeholder' => 'https://facebook.com/neekahmy'],
                ['name' => 'instagram', 'label' => 'Instagram', 'type' => 'url', 'value' => $values['instagram'], 'placeholder' => 'https://instagram.com/neekahmy'],
                ['name' => 'tiktok', 'label' => 'TikTok', 'type' => 'url', 'value' => $values['tiktok'], 'placeholder' => 'https://tiktok.com/@neekahmy'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function seoSection(array $values): array
    {
        return [
            'id' => 'seo',
            'icon' => '🔍',
            'label' => 'SEO',
            'title' => 'SEO dan pratonton pautan',
            'description' => 'Digunakan pada halaman yang tidak menulis meta tag sendiri, dan sebagai pratonton apabila pautan dikongsi.',
            'action' => route('admin.settings.seo'),
            'submit' => 'Simpan tetapan SEO',
            'preview' => ['site' => config('app.name')],
            'fields' => [
                ['name' => 'tagline', 'label' => 'Tagline', 'value' => $values['tagline'], 'required' => true, 'maxlength' => SeoSettings::TAGLINE_LIMIT, 'help' => 'Muncul selepas nama laman pada tajuk halaman utama.'],
                ['name' => 'description', 'label' => 'Penerangan lalai', 'type' => 'textarea', 'rows' => 3, 'value' => $values['description'], 'required' => true, 'maxlength' => Seo::DESCRIPTION_LIMIT, 'help' => 'Google memotong sekitar '.Seo::DESCRIPTION_LIMIT.' aksara.'],
                ['name' => 'twitter', 'label' => 'Akaun X (pilihan)', 'value' => $values['twitter'], 'placeholder' => '@neekahmy', 'help' => 'Dikreditkan pada kad pratonton X.'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function turnstileSection(array $values, bool $active): array
    {
        return [
            'id' => 'keselamatan',
            'icon' => '🛡️',
            'label' => 'Keselamatan',
            'title' => 'Cloudflare Turnstile',
            'description' => 'Semakan tanpa teka-teki pada borang log masuk, pendaftaran pengantin dan vendor, serta borang tempahan. Dapatkan kunci di dash.cloudflare.com → Turnstile.',
            'action' => route('admin.settings.turnstile'),
            'submit' => 'Simpan tetapan Turnstile',
            'badge' => ['active' => $active, 'label' => $active ? 'Aktif' : 'Tidak aktif'],
            'fields' => [
                ['name' => 'enabled', 'label' => 'Hidupkan Turnstile', 'type' => 'checkbox', 'value' => $values['enabled'], 'help' => 'Hanya berjalan apabila kedua-dua kunci diisi, supaya pendaftaran tidak pernah tersekat.'],
                ['name' => 'site_key', 'label' => 'Site key', 'value' => $values['site_key'], 'placeholder' => '0x4AAAAAAA...', 'help' => 'Kunci awam, dipaparkan dalam halaman.'],
                ['name' => 'secret_key', 'label' => 'Secret key', 'type' => 'password', 'value' => '', 'placeholder' => $values['secret_key'] ? 'Tersimpan — biarkan kosong untuk kekalkan' : 'Belum ditetapkan', 'help' => 'Tidak pernah dipaparkan semula selepas disimpan.'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function telegramSection(array $values, bool $active): array
    {
        return [
            'id' => 'telegram',
            'icon' => '📣',
            'label' => 'Telegram',
            'title' => 'Makluman Telegram',
            'description' => 'Setiap pendaftaran vendor dan pengantin dihantar ke chat admin, lengkap dengan emel dan pautan WhatsApp lead itu. Cipta bot dengan @BotFather, kemudian ambil chat id chat atau kumpulan admin.',
            'action' => route('admin.settings.telegram'),
            'submit' => 'Simpan tetapan Telegram',
            'badge' => ['active' => $active, 'label' => $active ? 'Aktif' : 'Tidak aktif'],
            'fields' => [
                ['name' => 'enabled', 'label' => 'Hantar makluman ke Telegram', 'type' => 'checkbox', 'value' => $values['enabled'], 'help' => 'Dihantar melalui queue, jadi pendaftaran tidak pernah menunggu Telegram.'],
                ['name' => 'bot_token', 'label' => 'Bot token', 'type' => 'password', 'value' => '', 'placeholder' => $values['bot_token'] ? 'Tersimpan — biarkan kosong untuk kekalkan' : '123456:ABC-DEF...', 'help' => 'Tidak pernah dipaparkan semula selepas disimpan.'],
                ['name' => 'chat_id', 'label' => 'Chat id', 'value' => $values['chat_id'], 'placeholder' => '-1001234567890', 'help' => 'Chat peribadi admin atau kumpulan. Kumpulan bermula dengan tanda tolak.'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function paymentSection(array $values, bool $active): array
    {
        return [
            'id' => 'bayaran',
            'icon' => '🏦',
            'label' => 'Bayaran',
            'title' => 'Kaedah bayaran',
            'description' => 'Wang tidak melalui Neekah. Pengantin berurusan terus dengan vendor, merekodkan bayaran yang telah dibuat berserta resit, dan vendor mengesahkannya. Booking hanya menjadi Confirmed selepas pengesahan itu.',
            'action' => route('admin.settings.payments'),
            'submit' => 'Simpan tetapan bayaran',
            'badge' => ['active' => $active, 'label' => $active ? 'Manual transfer aktif' : 'Tiada kaedah bayaran'],
            'columns' => true,
            'fields' => [
                ['name' => 'manual_transfer_enabled', 'label' => 'Benarkan rekod bayaran manual', 'type' => 'checkbox', 'value' => $values['manual_transfer_enabled'], 'wide' => true, 'help' => 'Apabila dimatikan, pengantin tidak boleh merekodkan sebarang bayaran pada booking mereka.'],
                ['name' => 'bank_name', 'label' => 'Nama bank', 'value' => $values['bank_name'], 'placeholder' => 'Maybank'],
                ['name' => 'account_holder', 'label' => 'Nama pemegang akaun', 'value' => $values['account_holder'], 'placeholder' => 'Neekah Enterprise'],
                ['name' => 'account_number', 'label' => 'Nombor akaun', 'value' => $values['account_number'], 'placeholder' => '512345678901', 'help' => 'Biarkan kosong jika bayaran dibuat terus kepada akaun vendor.'],
                ['name' => 'instructions', 'label' => 'Arahan bayaran', 'type' => 'textarea', 'rows' => 3, 'wide' => true, 'value' => $values['instructions'], 'help' => 'Dipaparkan kepada pengantin di borang rekod bayaran.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function imageSection(ImageSettings $images): array
    {
        $values = $images->all();

        return [
            'id' => 'gambar',
            'icon' => '🖼️',
            'label' => 'Gambar',
            'title' => 'Gambar',
            'description' => 'Setiap gambar yang dimuat naik oleh vendor, pasangan dan admin diubah saiz, dimampatkan dan dibuang data EXIF (termasuk lokasi GPS) sebelum disimpan. Satu salinan thumbnail turut dijana untuk senarai vendor, grid portfolio dan galeri.',
            'action' => route('admin.settings.update'),
            'submit' => 'Simpan tetapan gambar',
            'columns' => true,
            'warning' => $images->isLimitedByServer()
                ? 'Server ini hanya menerima <strong>'.$images->serverUploadMegabytes().'MB</strong> setiap muat naik, jadi had di bawah tidak digunakan sepenuhnya. Naikkan <code class="font-mono">upload_max_filesize</code> dan <code class="font-mono">post_max_size</code> dalam php.ini (serta <code class="font-mono">client_max_body_size</code> pada nginx), kemudian mulakan semula PHP.'
                : null,
            'note' => 'Tetapan ini digunakan untuk gambar yang dimuat naik selepas ini. Gambar lama diproses dengan menjalankan <code class="font-mono">php artisan neekah:optimize-images</code> pada server.',
            'fields' => [
                ['name' => 'max_dimension', 'label' => 'Saiz maksimum (piksel, sisi terpanjang)', 'type' => 'number', 'value' => $values['max_dimension'], 'min' => 800, 'max' => 4000, 'step' => 10, 'required' => true, 'help' => '1920 sudah tajam untuk skrin penuh. Lebih besar bermakna fail lebih berat.'],
                ['name' => 'thumbnail_width', 'label' => 'Lebar thumbnail (piksel)', 'type' => 'number', 'value' => $values['thumbnail_width'], 'min' => 200, 'max' => 1200, 'step' => 10, 'required' => true, 'help' => 'Saiz yang dipaparkan dalam senarai dan grid.'],
                ['name' => 'quality', 'label' => 'Kualiti (40 hingga 95)', 'type' => 'number', 'value' => $values['quality'], 'min' => 40, 'max' => 95, 'required' => true, 'help' => '80 ialah titik terbaik: sukar dibezakan daripada asal, tetapi fail jauh lebih kecil.'],
                ['name' => 'format', 'label' => 'Format', 'type' => 'select', 'value' => $values['format'], 'required' => true, 'help' => 'WebP biasanya 25 hingga 35% lebih kecil daripada JPEG pada kualiti yang sama.', 'options' => collect(ImageSettings::FORMATS)->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])->values()->all()],
                ['name' => 'max_upload_mb', 'label' => 'Had saiz muat naik (MB)', 'type' => 'number', 'value' => $values['max_upload_mb'], 'min' => 1, 'max' => 15, 'required' => true, 'help' => 'Saiz fail asal yang dibenarkan sebelum diproses. Maksimum 15 MB.'],
            ],
        ];
    }

    public function updateContact(UpdateContactSettingsRequest $request, ContactSettings $contact): RedirectResponse
    {
        $contact->save($request->settings());

        return $this->saved('Maklumat perhubungan disimpan.');
    }

    public function updateSeo(UpdateSeoSettingsRequest $request, SeoSettings $seo): RedirectResponse
    {
        $seo->save($request->validated());

        return $this->saved('Tetapan SEO disimpan. Ia digunakan pada setiap halaman yang tidak menerangkan dirinya sendiri.');
    }

    public function updateTurnstile(UpdateTurnstileSettingsRequest $request, TurnstileSettings $turnstile): RedirectResponse
    {
        $turnstile->save($request->settings());

        return $this->saved('Tetapan Turnstile disimpan.');
    }

    public function updateTelegram(UpdateTelegramSettingsRequest $request, TelegramSettings $telegram): RedirectResponse
    {
        $telegram->save($request->settings());

        return $this->saved('Tetapan Telegram disimpan.');
    }

    public function updatePayments(UpdatePaymentSettingsRequest $request, PaymentSettings $payments): RedirectResponse
    {
        $payments->save($request->settings());

        return $this->saved('Tetapan bayaran disimpan.');
    }

    public function update(UpdateImageSettingsRequest $request, ImageSettings $images): RedirectResponse
    {
        $images->save($request->validated());

        return $this->saved('Tetapan gambar disimpan. Gambar yang dimuat naik selepas ini akan menggunakannya.');
    }

    /**
     * Send the admin back to the section they were editing, not to the top of
     * a long page.
     */
    private function saved(string $message): RedirectResponse
    {
        return back()->with('status', $message);
    }
}
