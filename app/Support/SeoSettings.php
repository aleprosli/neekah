<?php

namespace App\Support;

/**
 * The words every public page falls back to when it does not describe itself:
 * the tagline in the home page title, the default meta description and the X
 * handle credited on shared links. Editable under Admin → Tetapan; the values
 * in config/neekah.php are the defaults.
 */
class SeoSettings extends SettingGroup
{
    /** Kept short: the tagline shares the 60-character title with the site name. */
    public const TAGLINE_LIMIT = 70;

    public function tagline(): string
    {
        return $this->string('tagline');
    }

    public function description(): string
    {
        return $this->string('description');
    }

    /** The @handle credited as twitter:site, or an empty string. */
    public function twitter(): string
    {
        $handle = ltrim($this->string('twitter'), '@');

        return $handle === '' ? '' : '@'.$handle;
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'tagline' => (string) config('neekah.seo.tagline'),
            'description' => (string) config('neekah.seo.description'),
            'twitter' => (string) config('neekah.seo.twitter'),
        ];
    }

    protected static function prefix(): string
    {
        return 'seo';
    }
}
