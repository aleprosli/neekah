<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateImageSettingsRequest;
use App\Support\ImageSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(ImageSettings $images): View
    {
        return view('admin.settings.edit', [
            'images' => $images->all(),
            'formats' => ImageSettings::FORMATS,
        ]);
    }

    public function update(UpdateImageSettingsRequest $request, ImageSettings $images): RedirectResponse
    {
        $images->save($request->validated());

        return back()->with('status', 'Tetapan gambar disimpan. Gambar yang dimuat naik selepas ini akan menggunakannya.');
    }
}
