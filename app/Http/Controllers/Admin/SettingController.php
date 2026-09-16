<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactSettingsRequest;
use App\Http\Requests\UpdateImageSettingsRequest;
use App\Http\Requests\UpdateSeoSettingsRequest;
use App\Http\Requests\UpdateTelegramSettingsRequest;
use App\Http\Requests\UpdateTurnstileSettingsRequest;
use App\Support\ContactSettings;
use App\Support\ImageSettings;
use App\Support\SeoSettings;
use App\Support\TelegramSettings;
use App\Support\TurnstileSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(ContactSettings $contact, SeoSettings $seo, TurnstileSettings $turnstile, TelegramSettings $telegram, ImageSettings $images): View
    {
        return view('admin.settings.edit', [
            'telegram' => $telegram->all(),
            'telegramActive' => $telegram->isEnabled(),
            'contact' => $contact->all(),
            'seo' => $seo->all(),
            'turnstile' => $turnstile->all(),
            'turnstileActive' => $turnstile->isEnabled(),
            'images' => $images->all(),
            'formats' => ImageSettings::FORMATS,
        ]);
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
