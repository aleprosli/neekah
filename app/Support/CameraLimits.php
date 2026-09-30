<?php

namespace App\Support;

/**
 * What one Kamera Majlis tier allows, as the admin set it. Null means no cap.
 */
final readonly class CameraLimits
{
    public function __construct(
        public ?int $maxPhotos,
        public int $photoPixels,
        public int $photoQuality,
        public bool $allowsVideo,
        public int $videoMaxMegabytes,
        public int $videoMaxSeconds,
    ) {}

    /**
     * @return array{max_photos: int|null, photo_pixels: int, photo_quality: int, allows_video: bool, video_max_megabytes: int, video_max_seconds: int}
     */
    public function toArray(): array
    {
        return [
            'max_photos' => $this->maxPhotos,
            'photo_pixels' => $this->photoPixels,
            'photo_quality' => $this->photoQuality,
            'allows_video' => $this->allowsVideo,
            'video_max_megabytes' => $this->videoMaxMegabytes,
            'video_max_seconds' => $this->videoMaxSeconds,
        ];
    }
}
