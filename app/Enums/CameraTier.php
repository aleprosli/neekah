<?php

namespace App\Enums;

/** The two Kamera Majlis plans a couple can buy. */
enum CameraTier: string
{
    case Basic = 'basic';
    case Pro = 'pro';

    public function label(): string
    {
        return __('enums.camera_tier.'.$this->value);
    }

    public function allowsVideo(): bool
    {
        return $this === self::Pro;
    }

    /** Pro sits above Basic: an album only ever moves up. */
    public function rank(): int
    {
        return match ($this) {
            self::Basic => 1,
            self::Pro => 2,
        };
    }
}
