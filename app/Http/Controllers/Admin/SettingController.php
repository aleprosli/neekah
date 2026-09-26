<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBoostSettingsRequest;
use App\Http\Requests\UpdateCameraSettingsRequest;
use App\Http\Requests\UpdateContactSettingsRequest;
use App\Http\Requests\UpdateHerepaySettingsRequest;
use App\Http\Requests\UpdateImageSettingsRequest;
use App\Http\Requests\UpdateOnlineBookingSettingsRequest;
use App\Http\Requests\UpdatePaymentSettingsRequest;
use App\Http\Requests\UpdateProSettingsRequest;
use App\Http\Requests\UpdateSeoSettingsRequest;
use App\Http\Requests\UpdateTelegramSettingsRequest;
use App\Http\Requests\UpdateTurnstileSettingsRequest;
use App\Support\BoostSettings;
use App\Support\CameraSettings;
use App\Support\ContactSettings;
use App\Support\Herepay\HerepayGateway;
use App\Support\HerepaySettings;
use App\Support\ImageSettings;
use App\Support\Locales;
use App\Support\OnlineBookingSettings;
use App\Support\PaymentSettings;
use App\Support\ProSettings;
use App\Support\Seo;
use App\Support\SeoSettings;
use App\Support\TelegramSettings;
use App\Support\TurnstileSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /**
     * The menu: pages grouped by what they are about. Money is split by who
     * pays whom, because that is the question an admin actually has: Neekah
     * Pro is vendors paying Neekah (through Neekah's own Herepay account),
     * booking payments are couples paying vendors.
     */
    public const MENU = [
        'laman' => ['perhubungan', 'seo', 'gambar'],
        'sistem' => ['keselamatan', 'telegram'],
        'wang' => ['pro', 'boost', 'tempahan', 'kamera', 'bayaran'],
    ];

    /** Every page, in menu order; the first is the default. */
    public const SECTIONS = ['perhubungan', 'seo', 'gambar', 'keselamatan', 'telegram', 'pro', 'boost', 'tempahan', 'kamera', 'bayaran'];

    /**
     * One page at a time, with the menu of the others. A page can hold more
     * than one form (Neekah Pro holds the plan and the gateway that takes its
     * payments); each form still posts on its own and comes back here.
     */
    public function edit(ContactSettings $contact, SeoSettings $seo, TurnstileSettings $turnstile, TelegramSettings $telegram, ImageSettings $images, PaymentSettings $payments, ProSettings $pro, HerepaySettings $herepay, HerepayGateway $herepayClient, OnlineBookingSettings $onlineBooking, CameraSettings $camera, BoostSettings $boost, string $section = self::SECTIONS[0]): View
    {
        $pages = [
            'perhubungan' => [$this->contactSection($contact->all())],
            'seo' => [$this->seoSection($seo->all())],
            'gambar' => [$this->imageSection($images)],
            'keselamatan' => [$this->turnstileSection($turnstile->all(), $turnstile->isEnabled())],
            'telegram' => [$this->telegramSection($telegram->all(), $telegram->isEnabled())],
            'pro' => [$this->proSection($pro, $herepayClient), $this->herepaySection($herepay, $herepayClient)],
            'boost' => [$this->boostSection($boost, $herepayClient)],
            'tempahan' => [$this->onlineBookingSection($onlineBooking)],
            'kamera' => [$this->cameraSection($camera, $herepayClient)],
            'bayaran' => [$this->paymentSection($payments)],
        ];

        abort_unless(isset($pages[$section]), 404);

        $menu = collect(self::MENU)->map(fn (array $ids, string $group): array => [
            'label' => __('props.admin.settings_group_'.$group),
            'items' => collect($ids)->map(fn (string $id): array => [
                'id' => $id,
                'icon' => $pages[$id][0]['icon'],
                'label' => $pages[$id][0]['label'],
                'href' => route('admin.settings.edit', ['section' => $id]),
                'active' => $id === $section,
                'badge' => $pages[$id][0]['badge'] ?? null,
            ])->all(),
        ])->values();

        return view('admin.settings.edit', [
            'current' => $pages[$section][0],
            'menu' => $menu,
            'props' => VueProps::for(['sections' => $pages[$section]]),
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
            'badge' => ['active' => $active, 'label' => $active ? __('props.copy.active') : __('props.copy.inactive')],
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
            'badge' => ['active' => $active, 'label' => $active ? __('props.copy.active') : __('props.copy.inactive')],
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.hantar_makluman_ke_telegram'), 'type' => 'checkbox', 'value' => $values['enabled'], 'help' => __('props.admin.dihantar_melalui_queue_jadi_pendaftaran')],
                ['name' => 'bot_token', 'label' => __('props.admin.bot_token'), 'type' => 'password', 'value' => '', 'placeholder' => $values['bot_token'] ? __('props.common.saved_leave_blank') : '123456:ABC-DEF...', 'help' => __('props.admin.tidak_pernah_dipaparkan_semula_selepas_2')],
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

        // Only a method that actually works is offered here. Gateways for
        // bookings will belong to each vendor (their own account), not to
        // Neekah, so they are not switches on this page yet.
        return [
            'id' => 'bayaran',
            'icon' => '💳',
            'label' => __('props.admin.booking_payments_label'),
            'title' => __('props.admin.booking_payments_title'),
            'description' => __('props.admin.booking_payments_description'),
            'action' => route('admin.settings.payments'),
            'submit' => __('props.admin.simpan_tetapan_bayaran'),
            'badge' => [
                'active' => $offered !== [],
                'label' => $offered === [] ? __('props.copy.no_payment_method') : __('props.units.active_list', ['list' => collect($offered)->map->label()->join(', ')]),
            ],
            'note' => __('props.admin.booking_payments_note'),
            'fields' => [
                ...collect(PaymentMethod::cases())
                    ->filter(fn (PaymentMethod $method): bool => $method->isIntegrated())
                    ->map(fn (PaymentMethod $method): array => [
                        'name' => $method->settingKey(),
                        'label' => $method->label(),
                        'type' => 'checkbox',
                        'value' => $values[$method->settingKey()],
                        'help' => $method->description(),
                    ])->values()->all(),
                ['name' => 'instructions', 'label' => __('props.admin.arahan_bayaran_manual'), 'type' => 'textarea', 'rows' => 3, 'value' => $values['instructions'], 'help' => __('props.admin.dipaparkan_kepada_pengantin_di_borang')],
            ],
        ];
    }

    /**
     * Online booking for Neekah Pro vendors, site-wide. Deposits go to each
     * vendor's own account, so there is no money setting here: only whether it
     * is open, how long an unpaid booking holds its date, and how recently a
     * vendor must have confirmed their calendar.
     *
     * @return array<string, mixed>
     */
    private function onlineBookingSection(OnlineBookingSettings $settings): array
    {
        $values = $settings->all();

        return [
            'id' => 'tempahan',
            'icon' => '📅',
            'label' => __('props.admin.online_booking_label'),
            'title' => __('props.admin.online_booking_title'),
            'description' => __('props.admin.online_booking_description'),
            'action' => route('admin.settings.online-booking'),
            'submit' => __('props.admin.online_booking_submit'),
            'columns' => true,
            'badge' => [
                'active' => $settings->isEnabled(),
                'label' => $settings->isEnabled() ? __('props.copy.active') : __('props.copy.inactive'),
            ],
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.online_booking_enabled'), 'type' => 'checkbox', 'value' => $values['enabled'], 'help' => __('props.admin.online_booking_enabled_help')],
                ['name' => 'hold_hours', 'label' => Str::ucfirst(__('fields.tempoh_pegang')), 'type' => 'number', 'value' => $values['hold_hours'], 'min' => 1, 'max' => 72, 'required' => true, 'help' => __('props.admin.online_booking_hold_help')],
                ['name' => 'calendar_fresh_days', 'label' => Str::ucfirst(__('fields.tempoh_sah_kalendar')), 'type' => 'number', 'value' => $values['calendar_fresh_days'], 'min' => 1, 'max' => 60, 'required' => true, 'help' => __('props.admin.online_booking_fresh_help')],
            ],
        ];
    }

    /**
     * Kamera Majlis: what couples pay Neekah for a shared wedding album, and
     * what each tier allows. Taken on Neekah's own Herepay, like Pro, so the
     * badge also says whether a couple can actually pay.
     *
     * @return array<string, mixed>
     */
    /**
     * Boost tokens: the welcome gift, Pro's monthly tokens, the longest boost
     * and the packs sold. The switch opens buying only; tokens held can
     * always be spent.
     *
     * @return array<string, mixed>
     */
    private function boostSection(BoostSettings $settings, HerepayGateway $gateway): array
    {
        $values = $settings->all();
        $open = $settings->isEnabled() && $gateway->isConfigured();
        $number = fn (string $key, ?string $help = null): array => [
            'name' => $key,
            'label' => Str::ucfirst(__('fields.boost_'.$key)),
            'type' => 'number',
            'value' => $values[$key],
            'min' => UpdateBoostSettingsRequest::NUMBERS[$key][0],
            'max' => UpdateBoostSettingsRequest::NUMBERS[$key][1],
            'required' => true,
            'help' => $help,
        ];

        return [
            'id' => 'boost',
            'icon' => '🚀',
            'label' => __('props.admin.boost_label'),
            'title' => __('props.admin.boost_title'),
            'description' => __('props.admin.boost_description'),
            'action' => route('admin.settings.boost'),
            'submit' => __('props.admin.boost_submit'),
            'columns' => true,
            'badge' => [
                'active' => $open,
                'label' => $open ? __('props.admin.pro_checkout_open') : __('props.admin.pro_checkout_closed'),
            ],
            'note' => $gateway->isConfigured() ? null : __('props.admin.boost_gateway_missing'),
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.boost_enabled'), 'type' => 'checkbox', 'value' => $values['enabled'], 'wide' => true, 'help' => __('props.admin.boost_enabled_help')],
                $number('welcome_tokens', __('props.admin.boost_welcome_help')),
                $number('pro_monthly_tokens', __('props.admin.boost_pro_help')),
                $number('max_days'),
                $number('small_tokens'),
                $number('small_price'),
                $number('large_tokens'),
                $number('large_price'),
            ],
        ];
    }

    private function cameraSection(CameraSettings $settings, HerepayGateway $gateway): array
    {
        $values = $settings->all();
        $open = $settings->isEnabled() && $gateway->isConfigured();
        $number = fn (string $key, ?string $help = null): array => [
            'name' => $key,
            'label' => Str::ucfirst(__('fields.camera_'.$key)),
            'type' => 'number',
            'value' => $values[$key],
            'min' => UpdateCameraSettingsRequest::NUMBERS[$key][0],
            'max' => UpdateCameraSettingsRequest::NUMBERS[$key][1],
            'required' => true,
            'help' => $help,
        ];

        return [
            'id' => 'kamera',
            'icon' => '📸',
            'label' => __('props.admin.camera_label'),
            'title' => __('props.admin.camera_title'),
            'description' => __('props.admin.camera_description'),
            'action' => route('admin.settings.camera'),
            'submit' => __('props.admin.camera_submit'),
            'columns' => true,
            'badge' => [
                'active' => $open,
                'label' => $open ? __('props.admin.pro_checkout_open') : __('props.admin.pro_checkout_closed'),
            ],
            'note' => $gateway->isConfigured() ? null : __('props.admin.camera_gateway_missing'),
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.camera_enabled'), 'type' => 'checkbox', 'value' => $values['enabled'], 'help' => __('props.admin.camera_enabled_help')],
                $number('basic_price'),
                $number('pro_price', __('props.admin.camera_upgrade_help')),
                $number('basic_max_photos'),
                $number('basic_photo_px'),
                $number('pro_photo_px'),
                $number('pro_video_max_mb'),
                $number('pro_video_max_seconds'),
                $number('pro_fair_use_gb', __('props.admin.camera_fair_use_help')),
                $number('retention_days', __('props.admin.camera_retention_help')),
            ],
        ];
    }

    /**
     * The payment gateway behind Neekah Pro. Only the on/off switch is kept
     * here; the keys stay in .env, and the note shows which are in place so an
     * admin knows what is still missing before switching it on.
     *
     * @return array<string, mixed>
     */
    private function herepaySection(HerepaySettings $settings, HerepayGateway $client): array
    {
        $missing = $client->missingKeys();
        $live = $client->isConfigured();
        $keys = collect(HerepayGateway::KEYS)
            ->map(fn (string $env): string => (in_array($env, $missing, true) ? '✗ ' : '✓ ').'<code>'.e($env).'</code>')
            // Optional: only asking Herepay about a payment again needs it.
            ->push(($client->canRequeryNeekah() ? '✓ ' : '– ').'<code>HEREPAY_API_KEY</code> '.e(__('props.admin.herepay_api_key_hint')))
            ->join('<br>');

        return [
            'id' => 'gateway',
            'icon' => '🏦',
            'label' => __('props.admin.herepay_label'),
            'title' => __('props.admin.herepay_title'),
            'description' => __('props.admin.herepay_description'),
            'action' => route('admin.settings.herepay'),
            'submit' => __('props.admin.herepay_submit'),
            'badge' => [
                'active' => $live,
                'label' => match (true) {
                    $live => __('props.admin.herepay_live', ['environment' => $client->environment()]),
                    $missing !== [] => __('props.admin.herepay_keys_missing'),
                    default => __('props.admin.herepay_off'),
                },
            ],
            'note' => __('props.admin.herepay_note', ['environment' => e($client->environment() ?? '—')]).'<br>'.$keys,
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.herepay_enabled'), 'type' => 'checkbox', 'value' => $settings->isEnabled(), 'help' => __('props.admin.herepay_enabled_help')],
            ],
        ];
    }

    /**
     * Neekah Pro: the price of each plan. The badge says whether a vendor can actually pay,
     * which also needs the Herepay keys in .env.
     *
     * @return array<string, mixed>
     */
    private function proSection(ProSettings $pro, HerepayGateway $gateway): array
    {
        $values = $pro->all();
        $open = $pro->isEnabled() && $gateway->isConfigured();

        return [
            'id' => 'pro',
            'icon' => '⭐',
            'label' => __('props.admin.pro'),
            'title' => __('props.admin.pro_title'),
            'description' => __('props.admin.pro_description'),
            'action' => route('admin.settings.pro'),
            'submit' => __('props.admin.pro_submit'),
            'columns' => true,
            'badge' => [
                'active' => $open,
                'label' => $open ? __('props.admin.pro_checkout_open') : __('props.admin.pro_checkout_closed'),
            ],
            'note' => $gateway->isConfigured() ? null : __('props.admin.pro_gateway_missing'),
            'fields' => [
                ['name' => 'enabled', 'label' => __('props.admin.pro_enabled'), 'type' => 'checkbox', 'value' => $values['enabled'], 'wide' => true, 'help' => __('props.admin.pro_enabled_help')],
                ['name' => 'monthly_price', 'label' => Str::ucfirst(__('fields.pro_monthly_price')), 'type' => 'number', 'value' => $values['monthly_price'], 'min' => 1, 'required' => true],
                ['name' => 'yearly_price', 'label' => Str::ucfirst(__('fields.pro_yearly_price')), 'type' => 'number', 'value' => $values['yearly_price'], 'min' => 1, 'required' => true],
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

        return $this->saved(__('flash.admin.contact_saved'));
    }

    public function updateSeo(UpdateSeoSettingsRequest $request, SeoSettings $seo): RedirectResponse
    {
        $seo->save($request->validated());

        return $this->saved(__('props.admin.seo_saved'));
    }

    public function updateTurnstile(UpdateTurnstileSettingsRequest $request, TurnstileSettings $turnstile): RedirectResponse
    {
        $turnstile->save($request->settings());

        return $this->saved(__('flash.admin.turnstile_saved'));
    }

    public function updateTelegram(UpdateTelegramSettingsRequest $request, TelegramSettings $telegram): RedirectResponse
    {
        $telegram->save($request->settings());

        return $this->saved(__('flash.admin.telegram_saved'));
    }

    public function updatePayments(UpdatePaymentSettingsRequest $request, PaymentSettings $payments): RedirectResponse
    {
        $payments->save($request->settings());

        return $this->saved(__('props.admin.payments_saved'));
    }

    public function updatePro(UpdateProSettingsRequest $request, ProSettings $pro): RedirectResponse
    {
        $pro->save($request->settings());

        return $this->saved(__('flash.admin.pro_settings_saved'));
    }

    public function updateHerepay(UpdateHerepaySettingsRequest $request, HerepaySettings $herepay): RedirectResponse
    {
        $herepay->save($request->settings());

        return $this->saved(__('flash.admin.herepay_saved'));
    }

    public function updateBoost(UpdateBoostSettingsRequest $request, BoostSettings $settings): RedirectResponse
    {
        $settings->save($request->settings());

        return $this->saved(__('flash.admin.boost_saved'));
    }

    public function updateCamera(UpdateCameraSettingsRequest $request, CameraSettings $settings): RedirectResponse
    {
        $settings->save($request->settings());

        return $this->saved(__('flash.admin.camera_saved'));
    }

    public function updateOnlineBooking(UpdateOnlineBookingSettingsRequest $request, OnlineBookingSettings $settings): RedirectResponse
    {
        $settings->save($request->settings());

        return $this->saved(__('flash.admin.online_booking_saved'));
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
