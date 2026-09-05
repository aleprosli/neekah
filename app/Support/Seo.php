<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The single place every public page describes itself to search engines and to
 * anything that unfurls a shared link.
 *
 * Resolved once per request from the container, set by the controller that
 * knows the page, and read by the seo.tags component. Nothing else writes meta
 * tags, so a page can never quietly disagree with itself.
 */
class Seo
{
    /** Google truncates a title around here, so trim rather than let it cut. */
    public const TITLE_LIMIT = 60;

    /** And a description around here. */
    public const DESCRIPTION_LIMIT = 160;

    private ?string $title = null;

    private ?string $description = null;

    private ?string $canonical = null;

    private ?string $image = null;

    private ?int $imageWidth = null;

    private ?int $imageHeight = null;

    private string $type = 'website';

    private bool $indexable = true;

    private bool $withSiteName = true;

    public function title(?string $title): static
    {
        $this->title = $title ?: $this->title;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description ?: $this->description;

        return $this;
    }

    /**
     * Used by the layout for pages that pass only a heading. The controller
     * knows the page better, so anything it already set wins.
     */
    public function fallbackTitle(?string $title): static
    {
        $this->title ??= $title ?: null;

        return $this;
    }

    public function fallbackDescription(?string $description): static
    {
        $this->description ??= $description ?: null;

        return $this;
    }

    /**
     * The address this page should be indexed under. Pass the URL with only the
     * parameters that genuinely make a different page, so a dozen filter
     * combinations do not compete with each other for the same content.
     */
    public function canonical(?string $url): static
    {
        $this->canonical = $url ?: $this->canonical;

        return $this;
    }

    /**
     * Facebook and WhatsApp render the preview faster, and without guessing at
     * the crop, when the dimensions are declared alongside the image.
     */
    public function image(?string $url, ?int $width = null, ?int $height = null): static
    {
        if ($url) {
            $this->image = $url;
            $this->imageWidth = $width;
            $this->imageHeight = $height;
        }

        return $this;
    }

    public function imageWidth(): ?int
    {
        return $this->imageWidth ?? ($this->image === null ? 1200 : null);
    }

    public function imageHeight(): ?int
    {
        return $this->imageHeight ?? ($this->image === null ? 630 : null);
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Keep this page out of search results. Used for anything private, anything
     * behind a token, and search result pages, which are not content of ours.
     */
    /**
     * Drop the "· Neekah" suffix. An invitation card is the couple's, and a
     * shared link should preview under their names, not ours.
     */
    public function withoutSiteName(): static
    {
        $this->withSiteName = false;

        return $this;
    }

    public function noindex(): static
    {
        $this->indexable = false;

        return $this;
    }

    public function isIndexable(): bool
    {
        return $this->indexable;
    }

    public function resolvedType(): string
    {
        return $this->type;
    }

    /**
     * "Page name · Neekah", trimmed to fit. The site name is dropped before the
     * page name is, because the page name is what tells the two results apart.
     */
    public function resolvedTitle(): string
    {
        $site = config('app.name');

        if (blank($this->title)) {
            return Str::limit($site.' — '.config('neekah.seo.tagline'), self::TITLE_LIMIT, '');
        }

        if (! $this->withSiteName) {
            return Str::limit($this->title, self::TITLE_LIMIT - 1, '…');
        }

        $full = $this->title.' · '.$site;

        if (mb_strlen($full) <= self::TITLE_LIMIT) {
            return $full;
        }

        return Str::limit($this->title, self::TITLE_LIMIT - 1, '…');
    }

    public function resolvedDescription(): string
    {
        return Str::limit($this->description ?: config('neekah.seo.description'), self::DESCRIPTION_LIMIT - 1, '…');
    }

    public function resolvedCanonical(): string
    {
        return $this->canonical ?: url()->current();
    }

    public function resolvedImage(): string
    {
        return $this->image ?: asset(config('neekah.seo.image'));
    }
}
