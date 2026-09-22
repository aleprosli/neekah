<?php

use Symfony\Component\Finder\Finder;

/**
 * An ornament that hangs past an edge must sit inside something that clips.
 *
 * A peony on the marketplace was placed 6rem past the right edge of a <main>
 * that nothing clipped, and every phone could scroll the whole site sideways.
 * <x-site.florals> exists so the ornaments clip themselves; anything drawn
 * outside it has to be inside an overflow-hidden element of its own.
 *
 * Ancestry is read from indentation, which the Blade in this project keeps
 * consistently: the nearest less-indented opening tag above the ornament is
 * its parent, and so on up.
 */
it('never lets an ornament hang past an edge without something to clip it', function () {
    $offenders = [];

    foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php')->notName('florals.blade.php') as $file) {
        $lines = explode("\n", $file->getContents());

        foreach ($lines as $at => $line) {
            if (! str_contains($line, '<x-site.ornament') || ! preg_match('/\s-(top|right|bottom|left)-/', $line)) {
                continue;
            }

            if (! ornamentIsClipped($lines, $at)) {
                $offenders[] = str_replace(resource_path().'/', '', $file->getPathname()).':'.($at + 1);
            }
        }
    }

    expect($offenders)->toBe([]);
});

/**
 * Walk up the ancestors by indentation until one clips, or none is left.
 *
 * @param  array<int, string>  $lines
 */
function ornamentIsClipped(array $lines, int $at): bool
{
    $indent = strlen($lines[$at]) - strlen(ltrim($lines[$at]));

    for ($line = $at - 1; $line >= 0; $line--) {
        $candidate = $lines[$line];

        if (trim($candidate) === '' || ! preg_match('/^\s*<[a-z]/', $candidate)) {
            continue;
        }

        $candidateIndent = strlen($candidate) - strlen(ltrim($candidate));

        if ($candidateIndent >= $indent) {
            continue;
        }

        if (str_contains($candidate, 'overflow-hidden')) {
            return true;
        }

        $indent = $candidateIndent;
    }

    return false;
}
