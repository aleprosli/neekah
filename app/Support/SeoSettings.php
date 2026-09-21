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
        return $this->localised('tagline');
    }

    public function description(): string
    {
        return $this->localised('description');
    }

    /**
     * The value written for the language being served, falling back to the
     * default language when the admin has not written that one yet — better a
     * Malay description on an English page than an empty one.
     */
    private function localised(string $key): string
    {
        $locale = Locales::current();

        $key_for_locale = $key.'_'.$locale;

        if ($locale !== Locales::DEFAULT && array_key_exists($key_for_locale, static::defaults()) && ($value = $this->string($key_for_locale)) !== '') {
            return $value;
        }

        return $this->string($key);
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
            'tagline_en' => (string) config('neekah.seo.tagline_en'),
            'description_en' => (string) config('neekah.seo.description_en'),
        ];
    }

    protected static function prefix(): string
    {
        return 'seo';
    }
}
