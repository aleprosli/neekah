<?php

namespace App\Support\Camera;

use App\Enums\CameraMediaType;

/**
 * What a file really is, read from its first bytes rather than from the name
 * or type the browser claimed. A guest upload is only kept when this agrees
 * with what was declared, so a script renamed .jpg never reaches the album.
 */
class MediaSniffer
{
    /**
     * @return array{type: CameraMediaType, mime: string, extension: string}|null
     */
    public static function detect(string $head): ?array
    {
        return match (true) {
            str_starts_with($head, "\xFF\xD8\xFF") => self::photo('image/jpeg', 'jpg'),
            str_starts_with($head, "\x89PNG\r\n\x1A\n") => self::photo('image/png', 'png'),
            str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP' => self::photo('image/webp', 'webp'),
            substr($head, 4, 4) === 'ftyp' => self::isoMedia(substr($head, 8, 4)),
            str_starts_with($head, "\x1A\x45\xDF\xA3") => self::video('video/webm', 'webm'),
            default => null,
        };
    }

    /**
     * An ISO media file: MP4 or QuickTime video. HEIC photos share the
     * container and are refused; the browser converts them to JPEG first.
     *
     * @return array{type: CameraMediaType, mime: string, extension: string}|null
     */
    private static function isoMedia(string $brand): ?array
    {
        return match (true) {
            in_array($brand, ['heic', 'heix', 'mif1', 'msf1', 'heim', 'heis', 'avif'], true) => null,
            $brand === 'qt  ' => self::video('video/quicktime', 'mov'),
            default => self::video('video/mp4', 'mp4'),
        };
    }

    /**
     * @return array{type: CameraMediaType, mime: string, extension: string}
     */
    private static function photo(string $mime, string $extension): array
    {
        return ['type' => CameraMediaType::Photo, 'mime' => $mime, 'extension' => $extension];
    }

    /**
     * @return array{type: CameraMediaType, mime: string, extension: string}
     */
    private static function video(string $mime, string $extension): array
    {
        return ['type' => CameraMediaType::Video, 'mime' => $mime, 'extension' => $extension];
    }
}
