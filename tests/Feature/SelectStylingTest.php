<?php

use Symfony\Component\Finder\Finder;

/**
 * Every <select> written anywhere under resources, with the markup of the tag
 * itself — attributes may run over several lines in a Vue template.
 *
 * @return array<string, array<int, string>>
 */
function selectTags(): array
{
    $found = [];

    foreach (Finder::create()->files()->in(resource_path())->name(['*.blade.php', '*.vue'])->notPath('views/vendor/mail') as $file) {
        // Blade attributes contain "->", whose > would otherwise end the tag.
        $markup = str_replace('->', '__ARROW__', $file->getContents());

        preg_match_all('/<select\b[^>]*>/s', $markup, $matches);

        if ($matches[0] !== []) {
            $found[str_replace(resource_path().'/', '', $file->getPathname())] = $matches[0];
        }
    }

    return $found;
}

it('draws every select with the shared arrow utility', function () {
    // A native select is drawn by the operating system, which ignores the
    // border, radius and padding set on it and lands at a different height
    // from the input beside it. nk-select turns that off — and owes the page
    // the arrow the browser used to draw.
    $bare = [];

    foreach (selectTags() as $path => $tags) {
        foreach ($tags as $tag) {
            if (! str_contains($tag, 'nk-select')) {
                $bare[] = $path;
            }
        }
    }

    expect(array_values(array_unique($bare)))->toBe([]);
});

it('keeps every select clear of its own arrow', function () {
    // The arrow is painted into the right-hand padding, so a select whose text
    // runs to its own edge prints the longest option straight through it.
    $crowded = [];

    foreach (selectTags() as $path => $tags) {
        foreach ($tags as $tag) {
            if (str_contains($tag, 'nk-select') && ! preg_match('/\bpr-\d+\b/', $tag)) {
                $crowded[] = $path;
            }
        }
    }

    expect(array_values(array_unique($crowded)))->toBe([]);
});

it('defines the utility the markup asks for', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('@utility nk-select')
        ->toContain('appearance: none')
        // Without a drawn arrow, nothing says the field is a dropdown at all.
        ->toContain('background-image: url("data:image/svg+xml');
});
