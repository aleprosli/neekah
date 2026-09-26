<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCameraAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'welcome_message' => ['nullable', 'string', 'max:300'],
            'guests_can_view' => ['nullable', 'boolean'],
            'uploads_open' => ['nullable', 'boolean'],
            // Blank keeps the current passcode; remove_passcode opens the album.
            'passcode' => ['nullable', 'string', 'min:4', 'max:32'],
            'remove_passcode' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('fields.camera_album_title'),
            'welcome_message' => __('fields.camera_welcome'),
            'passcode' => __('fields.camera_passcode'),
        ];
    }
}
