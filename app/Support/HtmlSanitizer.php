<?php

namespace App\Support;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;

/**
 * Cleans rich text from the blog editor down to the handful of tags an article
 * needs, before it is stored and later printed unescaped on a public page.
 *
 * It is an allowlist: unknown tags are unwrapped so their text survives,
 * dangerous ones are dropped with their content, every attribute not listed is
 * removed, and a link or image may only point at http(s) or a relative path.
 * Even an admin's input is cleaned, because an admin session can be
 * impersonated or stolen and the article is served to every visitor.
 */
class HtmlSanitizer
{
    /** @var array<string, array<int, string>> Tag => attributes it may keep. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'blockquote' => [], 'code' => [], 'pre' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
    ];

    /** Removed together with everything inside them. */
    private const DROPPED = ['script', 'style', 'iframe', 'object', 'embed', 'noscript', 'template', 'svg', 'math', 'form', 'textarea', 'select', 'button', 'title', 'head'];

    public function clean(string $html): string
    {
        $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR);
        $body = $document->body;

        if ($body === null) {
            return '';
        }

        $this->cleanChildren($body);

        return trim($body->innerHTML);
    }

    private function cleanChildren(Node $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof Text) {
                continue;
            }

            if (! $node instanceof Element) {
                $node->remove();

                continue;
            }

            $tag = strtolower($node->localName);

            if (in_array($tag, self::DROPPED, true)) {
                $node->remove();

                continue;
            }

            $this->cleanChildren($node);

            if (! array_key_exists($tag, self::ALLOWED)) {
                $node->replaceWith(...iterator_to_array($node->childNodes));

                continue;
            }

            $this->cleanAttributes($node, self::ALLOWED[$tag]);

            if ($tag === 'a') {
                $this->secureLink($node);
            }

            if ($tag === 'img' && ! $this->secureImage($node)) {
                $node->remove();
            }
        }
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function cleanAttributes(Element $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    /**
     * Links to other sites open in a new tab without handing that site a
     * reference back to ours.
     */
    private function secureLink(Element $link): void
    {
        $href = trim((string) $link->getAttribute('href'));

        if (! preg_match('~^(https?://|mailto:|tel:|/(?!/)|#)~i', $href)) {
            $link->removeAttribute('href');

            return;
        }

        $host = parse_url($href, PHP_URL_HOST);

        if (is_string($host) && ! str_ends_with($host, (string) parse_url(config('app.url'), PHP_URL_HOST))) {
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener');
        }
    }

    /**
     * Returns false when the image has no acceptable source and should go.
     */
    private function secureImage(Element $image): bool
    {
        $src = trim((string) $image->getAttribute('src'));

        if (! preg_match('~^(https?://|/(?!/))~i', $src)) {
            return false;
        }

        $image->setAttribute('loading', 'lazy');
        $image->setAttribute('decoding', 'async');

        return true;
    }
}
