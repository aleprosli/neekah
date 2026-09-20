<?php

use Symfony\Component\Finder\Finder;

/**
 * Every file under resources that draws a <table>.
 *
 * @return array<int, string>
 */
function filesWithTables(): array
{
    $files = [];

    foreach (Finder::create()->files()->in(resource_path())->name(['*.blade.php', '*.vue'])->notPath('views/vendor/mail') as $file) {
        if (str_contains($file->getContents(), '<table')) {
            $files[] = str_replace(resource_path().'/', '', $file->getPathname());
        }
    }

    return $files;
}

it('draws every application table with the one shared component', function () {
    // One table means one place to fix a column, a sort control or a phone
    // layout. A second hand-written table is how the two drift apart.
    $vue = array_filter(filesWithTables(), fn (string $path): bool => str_ends_with($path, '.vue'));

    expect(array_values($vue))->toBe(['js/components/ui/DataTable.vue']);
});

it('never leaves a table able to run off a phone screen', function () {
    // A table wider than the screen must sit in something that scrolls, or the
    // right-hand columns are simply unreachable on a phone.
    $unscrollable = array_filter(
        filesWithTables(),
        fn (string $path): bool => ! str_contains(file_get_contents(resource_path($path)), 'overflow-x-auto'),
    );

    expect($unscrollable)->toBe([]);
});

it('never hands the table an endpoint that already carries a query', function () {
    // The table adds its own page, search and sort parameters to dataUrl. When
    // the url arrived with a filter already on it the two collided — the server
    // read "status=pending?page=2", so the filter did nothing and paging stuck
    // on page one. Filters belong in the `filters` prop, which the table sends
    // as parameters of its own.
    $offenders = [];

    foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
        preg_match_all("/'dataUrl' => route\(([^\n]*)\)/", $file->getContents(), $matches);

        foreach ($matches[1] as $arguments) {
            if (str_contains($arguments, '[')) {
                $offenders[] = str_replace(resource_path().'/', '', $file->getPathname());
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('gives the shared table a card layout for narrow screens', function () {
    $table = file_get_contents(resource_path('js/components/ui/DataTable.vue'));

    expect($table)
        ->toContain('md:hidden')                                  // cards, on a phone
        ->toContain('hidden min-w-0 overflow-x-auto')             // the table, from md up
        ->toContain('Susun ikut');                                // sorting without headers to click
});
