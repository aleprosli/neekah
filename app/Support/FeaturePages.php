<?php

namespace App\Support;

/**
 * The product pages under /ciri, each told on its own page rather than inside
 * About. One list feeds the header dropdown, the About grid, the footer, the
 * sitemap and the "explore" row at the foot of every feature page, so a page
 * nobody can buy yet is missing from all of them at once — and answers 404.
 */
class FeaturePages
{
    public const KEYS = ['marketplace', 'kenangan', 'pro', 'boost'];

    public function __construct(
        private readonly CameraSettings $camera,
        private readonly ProSettings $pro,
        private readonly BoostSettings $boost,
    ) {}

    public function isAvailable(string $key): bool
    {
        return match ($key) {
            'marketplace' => true,
            'kenangan' => $this->camera->isEnabled(),
            'pro' => $this->pro->isEnabled(),
            'boost' => $this->boost->isEnabled(),
            default => false,
        };
    }

    /**
     * @return list<array{key: string, route: string, url: string, icon: string, audience: string, title: string, tagline: string}>
     */
    public function available(?string $except = null): array
    {
        $pages = [];

        foreach (self::KEYS as $key) {
            if ($key === $except || ! $this->isAvailable($key)) {
                continue;
            }

            $pages[] = [
                'key' => $key,
                'route' => 'features.'.$key,
                'url' => route('features.'.$key),
                'icon' => ['marketplace' => 'store', 'kenangan' => 'camera', 'pro' => 'crown', 'boost' => 'rocket'][$key],
                'audience' => in_array($key, ['marketplace', 'kenangan'], true) ? 'couples' : 'vendors',
                'title' => __('pages.features.'.$key.'.name'),
                'tagline' => __('pages.features.'.$key.'.tagline'),
            ];
        }

        return $pages;
    }
}
