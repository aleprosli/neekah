<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactSettingsRequest;
use App\Http\Requests\UpdateImageSettingsRequest;
use App\Http\Requests\UpdatePaymentSettingsRequest;
use App\Http\Requests\UpdateSeoSettingsRequest;
use App\Http\Requests\UpdateTelegramSettingsRequest;
use App\Http\Requests\UpdateTurnstileSettingsRequest;
use App\Support\ContactSettings;
use App\Support\ImageSettings;
use App\Support\Locales;
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
                    $this->paymentSection($payments),
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
            'label' => __('props.admin.perhubungan'),
            'title' => __('props.admin.maklumat_perhubungan'),
            'description' => __('props.admin.dipaparkan_di_footer_setiap_halaman'),
            'action' => route('admin.settings.contact'),
            'submit' => __('props.admin.simpan_maklumat_perhubungan'),
            'columns' => true,
            'fields' => [
                ['name' => 'phone', 'label' => __('props.admin.nombor_telefon'), 'type' => 'tel', 'value' => $values['phone'], 'placeholder' => '03-1234 5678', 'help' => __('props.admin.dipaparkan_sebagai_pautan_panggilan')],
                ['name' => 'whatsapp', 'label' => __('props.admin.nombor_whatsapp'), 'type' => 'tel', 'value' => $values['whatsapp'], 'placeholder' => '60123456789', 'help' => __('props.admin.dengan_kod_negara_kosong_bermakna')],
                ['name' => 'email', 'label' => __('props.admin.emel'), 'type' => 'email', 'value' => $values['email'], 'placeholder' => 'hello@neekah.my'],
                ...$this->hoursFields($values),
                ['name' => 'address', 'label' => __('props.admin.alamat'), 'type' => 'textarea', 'rows' => 2, 'wide' => true, 'value' => $values['address'], 'placeholder' => __('props.admin.no_1_jalan_contoh_50000')],
                ['name' => 'facebook', 'label' => __('props.admin.facebook'), 'type' => 'url', 'value' => $values['facebook'], 'placeholder' => 'https://facebook.com/neekahmy'],
                ['name' => 'instagram', 'label' => __('props.admin.instagram'), 'type' => 'url', 'value' => $values['instagram'], 'placeholder' => 'https://instagram.com/neekahmy'],
                ['name' => 'tiktok', 'label' => __('props.admin.tiktok'), 'type' => 'url', 'value' => $values['tiktok'], 'placeholder' => 'https://tiktok.com/@neekahmy'],
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
            'label' => __('props.admin.seo'),
            'title' => __('props.admin.seo_dan_pratonton_pautan'),
            'description' => __('props.admin.digunakan_pada_halaman_yang_tidak'),
            'action' => route('admin.settings.seo'),
            'submit' => __('props.admin.simpan_tetapan_seo'),
            'preview' => ['site' => config('app.name')],
            'fields' => [
                ...$this->seoTextFields($values),
                ['name' => 'twitter', 'label' => __('props.admin.akaun_x_pilihan'), 'value' => $values['twitter'], 'placeholder' => '@neekahmy', 'help' => __('props.admin.dikreditkan_pada_kad_pratonton_x')],
            ],
        ];
    }

    /**
     * Opening hours, one field per language.
     *
     * "Ahad - Khamis, 9 Pagi - 5 Petang" is not something to show someone
     * reading English, and it sits in the footer of every page. The address
     * beside it stays single: a place reads the same wherever you are from.
     *
     * @param  array<string, mixed>  $values
     * @return array<int, array<string, mixed>>
     */
    private function hoursFields(array $values): array
    {
        $fields = [];

        foreach (ContactSettings::localisedKeys('hours') as $code => $key) {
            $isDefault = $code === Locales::DEFAULT;

            $fields[] = [
                'name' => $key,
                'label' => __('props.admin.waktu_operasi_bahasa', ['language' => Locales::label($code)]),
                'value' => $values[$key],
                'placeholder' => $isDefault
                    ? __('props.admin.isnin_jumaat_9_pagi_6')
                    : __('props.admin.isnin_jumaat_9_pagi_6_en'),
                'help' => $isDefault ? null : __('props.admin.bahasa_kedua_pilihan'),
            ];
        }

        return $fields;
    }

    /**
     * The title tagline and meta description, one field per language. English
     * pages are indexed separately, so they get their own words; leaving the
     * English field empty falls back to the Malay one.
     *
     * @param  array<string, mixed>  $values
     * @return array<int, array<string, mixed>>
     */
    private function seoTextFields(array $values): array
    {
        $fields = [];

        foreach (Locales::ALL as $code => $locale) {
            $suffix = $code === Locales::DEFAULT ? '' : '_'.$code;
            $isDefault = $suffix === '';

            $fields[] = [
                'name' => 'tagline'.$suffix,
                'label' => __('props.admin.tagline_bahasa', ['language' => $locale['label']]),
                'value' => $values['tagline'.$suffix],
                'required' => $isDefault,
                'maxlength' => SeoSettings::TAGLINE_LIMIT,
                'help' => $isDefault ? __('props.admin.muncul_selepas_nama_laman_pada') : __('props.admin.bahasa_kedua_pilihan'),
            ];
            $fields[] = [
                'name' => 'description'.$suffix,
                'label' => __('props.admin.penerangan_lalai_bahasa', ['language' => $locale['label']]),
                'type' => 'textarea',
                'rows' => 3,
                'value' => $values['description'.$suffix],
                'required' => $isDefault,
                'maxlength' => Seo::DESCRIPTION_LIMIT,
                'help' => $isDefault
                    ? __('props.admin.google_memotong_sekitar').__('props.admin.aksara', ['count' => Seo::DESCRIPTION_LIMIT])
                    : __('props.admin.bahasa_kedua_pilihan'),
            ];
        }

        return $fields;
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
            'label' => __('props.admin.keselamatan'),
            'title' => __('props.admin.cloudflare_turnstile'),
            'description' => __('props.admin.semakan_tanpa_teka_teki_pada'),
            'action' => route('admin.settings.turnstile'),
            'submit' => __('props.admin.simpan_tetapan_turnstile'),
            'badge' => ['active' => $active, 'label' => $active ? 'Aktif' : 'Tidak aktif'],
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.hidupkan_turnstile'), 'type' => 'checkbox', 'value' => $values['enabled'], 'help' => __('props.admin.hanya_berjalan_apabila_kedua_dua')],
                ['name' => 'site_key', 'label' => __('props.admin.site_key'), 'value' => $values['site_key'], 'placeholder' => '0x4AAAAAAA...', 'help' => __('props.admin.kunci_awam_dipaparkan_dalam_halaman')],
                ['name' => 'secret_key', 'label' => __('props.admin.secret_key'), 'type' => 'password', 'value' => '', 'placeholder' => $values['secret_key'] ? __('props.common.saved_leave_blank') : __('props.common.not_set'), 'help' => __('props.admin.tidak_pernah_dipaparkan_semula_selepas')],
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
            'label' => __('props.admin.telegram'),
            'title' => __('props.admin.makluman_telegram'),
            'description' => __('props.admin.setiap_pendaftaran_vendor_dan_pengantin'),
            'action' => route('admin.settings.telegram'),
            'submit' => __('props.admin.simpan_tetapan_telegram'),
            'badge' => ['active' => $active, 'label' => $active ? 'Aktif' : 'Tidak aktif'],
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.hantar_makluman_ke_telegram'), 'type' => 'checkbox', 'value' => $values['enabled'], 'help' => __('props.admin.dihantar_melalui_queue_jadi_pendaftaran')],
                ['name' => 'bot_token', 'label' => __('props.admin.bot_token'), 'type' => 'password', 'value' => '', 'placeholder' => $values['bot_token'] ? 'Tersimpan — biarkan kosong untuk kekalkan' : '123456:ABC-DEF...', 'help' => __('props.admin.tidak_pernah_dipaparkan_semula_selepas_2')],
                ['name' => 'chat_id', 'label' => __('props.admin.chat_id'), 'value' => $values['chat_id'], 'placeholder' => '-1001234567890', 'help' => __('props.admin.chat_peribadi_admin_atau_kumpulan')],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentSection(PaymentSettings $payments): array
    {
        $values = $payments->all();
        $offered = $payments->offeredMethods();

        return [
            'id' => 'bayaran',
            'icon' => '💳',
            'label' => __('props.admin.bayaran'),
            'title' => __('props.admin.kaedah_bayaran'),
            'description' => __('props.admin.hidupkan_kaedah_yang_boleh_digunakan'),
            'action' => route('admin.settings.payments'),
            'submit' => __('props.admin.simpan_tetapan_bayaran'),
            'badge' => [
                'active' => $offered !== [],
                'label' => $offered === [] ? 'Tiada kaedah bayaran' : __('props.units.active_list', ['list' => collect($offered)->map->label()->join(', ')]),
            ],
            'fields' => [
                ...collect(PaymentMethod::cases())->map(fn (PaymentMethod $method): array => [
                    'name' => $method->settingKey(),
                    'label' => $method->label(),
                    'type' => 'checkbox',
                    'value' => $values[$method->settingKey()],
                    'help' => $method->isIntegrated()
                        ? $method->description()
                        : $method->description().__('props.admin.not_integrated'),
                ])->all(),
                ['name' => 'instructions', 'label' => __('props.admin.arahan_bayaran_manual'), 'type' => 'textarea', 'rows' => 3, 'value' => $values['instructions'], 'help' => __('props.admin.dipaparkan_kepada_pengantin_di_borang')],
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
            'label' => __('props.admin.gambar'),
            'title' => __('props.admin.gambar_2'),
            'description' => __('props.admin.setiap_gambar_yang_dimuat_naik'),
            'action' => route('admin.settings.update'),
            'submit' => __('props.admin.simpan_tetapan_gambar'),
            'columns' => true,
            'warning' => $images->isLimitedByServer()
                ? __('props.admin.upload_server_warning', ['size' => $images->serverUploadMegabytes()])
                : null,
            'note' => __('props.admin.tetapan_ini_digunakan_untuk_gambar'),
            'fields' => [
                ['name' => 'max_dimension', 'label' => __('props.admin.saiz_maksimum_piksel_sisi_terpanjang'), 'type' => 'number', 'value' => $values['max_dimension'], 'min' => 800, 'max' => 4000, 'step' => 10, 'required' => true, 'help' => __('props.admin.max_dimension_help')],
                ['name' => 'thumbnail_width', 'label' => __('props.admin.lebar_thumbnail_piksel'), 'type' => 'number', 'value' => $values['thumbnail_width'], 'min' => 200, 'max' => 1200, 'step' => 10, 'required' => true, 'help' => __('props.admin.saiz_yang_dipaparkan_dalam_senarai')],
                ['name' => 'quality', 'label' => __('props.admin.kualiti_40_hingga_95'), 'type' => 'number', 'value' => $values['quality'], 'min' => 40, 'max' => 95, 'required' => true, 'help' => '80 ialah titik terbaik: sukar dibezakan daripada asal, tetapi fail jauh lebih kecil.'],
                ['name' => 'format', 'label' => __('props.admin.format'), 'type' => 'select', 'value' => $values['format'], 'required' => true, 'help' => __('props.admin.webp_biasanya_25_hingga_35'), 'options' => collect(ImageSettings::formats())->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])->values()->all()],
                ['name' => 'max_upload_mb', 'label' => __('props.admin.had_saiz_muat_naik_mb'), 'type' => 'number', 'value' => $values['max_upload_mb'], 'min' => 1, 'max' => 15, 'required' => true, 'help' => __('props.admin.saiz_fail_asal_yang_dibenarkan')],
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

        return $this->saved(__('props.admin.seo_saved'));
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

        return $this->saved(__('props.admin.payments_saved'));
    }

    public function update(UpdateImageSettingsRequest $request, ImageSettings $images): RedirectResponse
    {
        $images->save($request->validated());

        return $this->saved(__('props.admin.images_saved'));
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
