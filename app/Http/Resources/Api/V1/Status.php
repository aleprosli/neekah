<?php

namespace App\Http\Resources\Api\V1;

use BackedEnum;

/**
 * Every status the app shows is the same small object, so it can draw any
 * of them as a coloured chip without knowing what it is a status of.
 */
class Status
{
    /**
     * @return array{value: string, label: string, tone: string}
     */
    public static function of(BackedEnum $status): array
    {
        return [
            'value' => (string) $status->value,
            'label' => $status->label(),
            'tone' => method_exists($status, 'tone') ? $status->tone() : 'muted',
        ];
    }
}
