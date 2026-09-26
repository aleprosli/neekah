<?php

namespace App\Support\Camera;

use App\Models\CameraAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * A guest on a Kamera Majlis album. There is no account: the phone is known by
 * a long random cookie (only its hash is stored), an optional name the guest
 * types, and whether this session has entered the album's passcode.
 */
class CameraGuest
{
    public const COOKIE = 'nk_cam';

    public const NAME_COOKIE = 'nk_cam_name';

    public function __construct(private Request $request) {}

    /**
     * The device's hash. The token lives in a long-lived cookie and, as a
     * fallback for phones that drop cookies, in the session; created on the
     * first visit.
     */
    public function deviceHash(): string
    {
        $token = $this->request->cookie(self::COOKIE);

        if (! is_string($token) || strlen($token) < 32) {
            $token = $this->request->session()->get(self::COOKIE);
        }

        if (! is_string($token) || strlen($token) < 32) {
            $token = Str::random(40);
        }

        $this->request->session()->put(self::COOKIE, $token);

        if ($this->request->cookie(self::COOKIE) !== $token) {
            Cookie::queue(self::COOKIE, $token, 60 * 24 * 400, httpOnly: true, sameSite: 'lax');
            $this->request->cookies->set(self::COOKIE, $token);
        }

        return hash('sha256', $token);
    }

    public function name(): ?string
    {
        $name = $this->request->session()->get(self::NAME_COOKIE) ?? $this->request->cookie(self::NAME_COOKIE);

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function rememberName(string $name): void
    {
        $name = mb_substr(trim(strip_tags($name)), 0, 40);

        $this->request->session()->put(self::NAME_COOKIE, $name);
        Cookie::queue(self::NAME_COOKIE, $name, 60 * 24 * 400, httpOnly: true, sameSite: 'lax');
    }

    /** Let in: an open album, or one whose current passcode this session entered. */
    public function mayEnter(CameraAlbum $album): bool
    {
        return ! $album->isRestricted()
            || $this->request->session()->get(self::sessionKey($album)) === $album->passcode_version;
    }

    public function enter(CameraAlbum $album): void
    {
        $this->request->session()->regenerate();
        $this->request->session()->put(self::sessionKey($album), $album->passcode_version);
    }

    private static function sessionKey(CameraAlbum $album): string
    {
        return 'camera.'.$album->id;
    }
}
