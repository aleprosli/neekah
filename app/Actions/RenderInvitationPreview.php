<?php

namespace App\Actions;

use App\Models\SiteTemplate;
use App\Models\WeddingSite;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Draw the link preview a guest sees when a card is shared to WhatsApp.
 *
 * Without this the preview falls back to the Neekah logo, which tells a guest
 * nothing about the couple and puts our brand on their invitation. This paints
 * the couple's own card instead: their template's colours, their script face,
 * their names and their date.
 */
class RenderInvitationPreview
{
    /** The size Facebook, WhatsApp and X all render without recropping. */
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /**
     * The families the twenty templates draw from, mapped to files we ship.
     * A template asking for anything else falls back to the serif.
     */
    private const FONTS = [
        'Great Vibes' => 'GreatVibes-Regular.ttf',
        'Playfair Display' => 'PlayfairDisplay.ttf',
        'Cormorant Garamond' => 'CormorantGaramond.ttf',
        'Instrument Sans' => 'InstrumentSans.ttf',
    ];

    /**
     * The stored path, rendering it first if this version has not been drawn.
     * The key carries everything the picture shows, so editing the card or
     * switching template produces a new file rather than a stale one.
     */
    public function handle(WeddingSite $site, SiteTemplate $template): string
    {
        $key = substr(sha1(implode('|', [
            $site->id, $template->slug, $site->bride_name, $site->groom_name,
            $site->event_date?->toDateString(), $site->venue_name, $site->cover_image,
        ])), 0, 12);

        $path = 'og/'.($site->subdomain ?: 'contoh').'-'.$key.'.png';
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $disk->put($path, $this->draw($site, $template));
        }

        return $path;
    }

    public function draw(WeddingSite $site, SiteTemplate $template): string
    {
        $palette = $template->design['palette'] ?? [];
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $page = $this->colour($canvas, $palette['page'] ?? '#ffffff');
        imagefill($canvas, 0, 0, $page);

        $this->drawCover($canvas, $site, $palette['page'] ?? '#ffffff');

        $accent = $this->colour($canvas, $palette['accent'] ?? '#c19a4b');
        $name = $this->colour($canvas, $palette['name'] ?? '#2b2b2b');
        $body = $this->colour($canvas, $palette['body'] ?? '#5f5f5f');

        // A hairline frame, the way the card itself is bordered.
        imagesetthickness($canvas, 2);
        imagerectangle($canvas, 40, 40, self::WIDTH - 41, self::HEIGHT - 41, $accent);

        $script = $this->font($template->design['type']['script'] ?? '', 'GreatVibes-Regular.ttf');
        $serif = $this->font($template->design['type']['body'] ?? '', 'CormorantGaramond.ttf');

        $this->centred($canvas, Str::upper($template->eyebrow()), $serif, 20, 148, $accent, 8);

        // The names carry the card, so they are sized to fit rather than clipped.
        $this->centred($canvas, $site->bride_name, $script, $this->fitting($site->bride_name, $script, 82), 290, $name);
        $this->centred($canvas, '&', $serif, 34, 360, $accent);
        $this->centred($canvas, $site->groom_name, $script, $this->fitting($site->groom_name, $script, 82), 450, $name);

        $rule = (int) (self::WIDTH / 2);
        imagesetthickness($canvas, 1);
        imageline($canvas, $rule - 90, 500, $rule + 90, 500, $accent);
        imagefilledellipse($canvas, $rule, 500, 9, 9, $accent);

        $when = $site->event_date?->translatedFormat('j F Y') ?? '';
        $this->centred($canvas, trim($when.($site->venue_name ? '  ·  '.$site->venue_name : '')), $serif, 26, 556, $body);

        ob_start();
        imagepng($canvas, null, 8);

        return (string) ob_get_clean();
    }

    /**
     * A couple's own photograph is better than anything we can draw, so it
     * becomes the background, dimmed enough that the names stay readable.
     */
    private function drawCover(GdImage $canvas, WeddingSite $site, string $page): void
    {
        if (! $site->cover_image || ! Storage::disk('public')->exists($site->cover_image)) {
            return;
        }

        $cover = @imagecreatefromstring(Storage::disk('public')->get($site->cover_image));

        if (! $cover) {
            return;
        }

        // Cover the frame, cropping the overflow rather than squashing the photo.
        $scale = max(self::WIDTH / imagesx($cover), self::HEIGHT / imagesy($cover));
        $w = (int) ceil(imagesx($cover) * $scale);
        $h = (int) ceil(imagesy($cover) * $scale);
        imagecopyresampled($canvas, $cover, (int) ((self::WIDTH - $w) / 2), (int) ((self::HEIGHT - $h) / 2), 0, 0, $w, $h, imagesx($cover), imagesy($cover));

        $scrim = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagefill($scrim, 0, 0, $this->colour($scrim, $page));
        imagecopymerge($canvas, $scrim, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, 74);
    }

    /** Shrink a long name until it fits between the margins. */
    private function fitting(string $text, string $font, int $size): int
    {
        while ($size > 34 && $this->width($text, $font, $size) > self::WIDTH - 220) {
            $size -= 2;
        }

        return $size;
    }

    private function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text) ?: [0, 0, 0, 0];

        return (int) abs($box[2] - $box[0]);
    }

    private function centred(GdImage $canvas, string $text, string $font, int $size, int $y, int $colour, int $tracking = 0): void
    {
        if ($text === '') {
            return;
        }

        if ($tracking === 0) {
            imagettftext($canvas, $size, 0, (int) ((self::WIDTH - $this->width($text, $font, $size)) / 2), $y, $colour, $font, $text);

            return;
        }

        // GD has no letter spacing, so tracked text is drawn a glyph at a time.
        $letters = mb_str_split($text);
        $total = collect($letters)->sum(fn (string $letter): int => $this->width($letter, $font, $size) + $tracking);
        $x = (int) ((self::WIDTH - $total) / 2);

        foreach ($letters as $letter) {
            imagettftext($canvas, $size, 0, $x, $y, $colour, $font, $letter);
            $x += $this->width($letter, $font, $size) + $tracking;
        }
    }

    /** Resolve a CSS font stack such as "'Great Vibes', cursive" to a file. */
    private function font(string $stack, string $fallback): string
    {
        foreach (self::FONTS as $family => $file) {
            if (str_contains($stack, $family)) {
                return resource_path('fonts/'.$file);
            }
        }

        return resource_path('fonts/'.$fallback);
    }

    private function colour(GdImage $canvas, string $hex): int
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%2x%2x%2x') ?: [255, 255, 255];

        return imagecolorallocate($canvas, (int) $r, (int) $g, (int) $b);
    }
}
