<?php

namespace App\Support\Camera;

/**
 * What a voice wish really is, read from its first bytes. Browsers record in
 * WebM or Ogg (Chrome, Firefox, Android) or MP4/M4A (Safari); anything else
 * is refused whatever type the browser claimed.
 */
class AudioSniffer
{
    /**
     * @return array{mime: string, extension: string}|null
     */
    public static function detect(string $head): ?array
    {
        return match (true) {
            str_starts_with($head, "\x1A\x45\xDF\xA3") => ['mime' => 'audio/webm', 'extension' => 'webm'],
            str_starts_with($head, 'OggS') => ['mime' => 'audio/ogg', 'extension' => 'ogg'],
            substr($head, 4, 4) === 'ftyp' && ! in_array(substr($head, 8, 4), ['heic', 'heix', 'mif1', 'msf1', 'heim', 'heis', 'avif', 'qt  '], true) => ['mime' => 'audio/mp4', 'extension' => 'm4a'],
            default => null,
        };
    }
}
