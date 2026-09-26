<?php

namespace App\Support;

use App\Enums\CameraTier;

/**
 * Kamera Majlis, set under Admin → Tetapan → Wang → Kamera Majlis: whether it
 * is sold, the price of each tier and what each allows. Couples pay Neekah on
 * Neekah's own Herepay account, like Neekah Pro.
 */
class CameraSettings extends SettingGroup
{
    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled');
    }

    public function price(CameraTier $tier): float
    {
        return (float) $this->value($tier->value.'_price');
    }

    /** Days after the event the album and its files are kept. */
    public function retentionDays(): int
    {
        return max(1, (int) $this->value('retention_days'));
    }

    /** Pro has no cap; past this many GB the admin is alerted, nothing is refused. */
    public function proFairUseGigabytes(): int
    {
        return max(1, (int) $this->value('pro_fair_use_gb'));
    }

    public function deviceUploadsPerHour(): int
    {
        return max(1, (int) $this->value('device_uploads_per_hour'));
    }

    public function limitsFor(CameraTier $tier): CameraLimits
    {
        return match ($tier) {
            CameraTier::Basic => new CameraLimits(
                maxPhotos: max(1, (int) $this->value('basic_max_photos')),
                photoPixels: (int) $this->value('basic_photo_px'),
                photoQuality: (int) $this->value('basic_quality'),
                allowsVideo: false,
                videoMaxMegabytes: 0,
                videoMaxSeconds: 0,
            ),
            CameraTier::Pro => new CameraLimits(
                maxPhotos: null,
                photoPixels: (int) $this->value('pro_photo_px'),
                photoQuality: (int) $this->value('pro_quality'),
                allowsVideo: true,
                videoMaxMegabytes: max(1, (int) $this->value('pro_video_max_mb')),
                videoMaxSeconds: max(1, (int) $this->value('pro_video_max_seconds')),
            ),
        };
    }

    /**
     * @return array<string, int|bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'basic_price' => 29,
            'pro_price' => 99,
            'basic_max_photos' => 500,
            'basic_photo_px' => 1600,
            'basic_quality' => 80,
            'pro_photo_px' => 3840,
            'pro_quality' => 90,
            'pro_video_max_mb' => 100,
            'pro_video_max_seconds' => 180,
            'pro_fair_use_gb' => 50,
            'retention_days' => 14,
            'device_uploads_per_hour' => 120,
        ];
    }

    protected static function prefix(): string
    {
        return 'camera';
    }
}
