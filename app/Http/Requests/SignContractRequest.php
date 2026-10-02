<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The client signing from the public page, with no account: the token in
 * the address is what lets them. The signature arrives as the PNG the pad
 * drew, as a data URL, and must really be a PNG of a sensible size.
 */
class SignContractRequest extends FormRequest
{
    /** The largest signature image accepted, in bytes. */
    public const MAX_SIGNATURE_BYTES = 300 * 1024;

    private const PREFIX = 'data:image/png;base64,';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'agree' => ['accepted'],
            'signature' => ['required', 'string', 'max:'.(int) ceil(self::MAX_SIGNATURE_BYTES * 4 / 3 + strlen(self::PREFIX) + 4)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('signature')) {
                    return;
                }

                if ($this->signaturePng() === null) {
                    $validator->errors()->add('signature', __('validation.custom.signature_invalid'));
                }
            },
        ];
    }

    /**
     * The decoded PNG, or null when it is not a PNG drawing worth keeping.
     */
    public function signaturePng(): ?string
    {
        $value = (string) $this->input('signature');

        if (! str_starts_with($value, self::PREFIX)) {
            return null;
        }

        $bytes = base64_decode(substr($value, strlen(self::PREFIX)), true);

        if ($bytes === false || strlen($bytes) > self::MAX_SIGNATURE_BYTES || ! str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        $size = @getimagesizefromstring($bytes);

        if ($size === false || $size[2] !== IMAGETYPE_PNG || $size[0] < 50 || $size[1] < 20 || $size[0] > 4000 || $size[1] > 4000) {
            return null;
        }

        return $bytes;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('fields.nama_penuh'),
            'agree' => __('fields.persetujuan'),
            'signature' => __('fields.tandatangan'),
        ];
    }
}
