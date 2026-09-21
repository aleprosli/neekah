<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('binds every prop with the colon in front of the name', function () {
    // An automated rename once turned confirm-label="..." into
    // confirm-:label="$t(...)", which Vue reads as an attribute of that literal
    // name: the prop silently never arrives and the component falls back to its
    // default. Twenty-two confirmation dialogs said "Teruskan" instead of what
    // they meant to say, in both languages, and nothing failed.
    $offenders = collect(File::allFiles(resource_path('js')))
        ->filter(fn ($file): bool => $file->getExtension() === 'vue')
        ->flatMap(function ($file): array {
            preg_match_all('/\b[a-z]+-:[a-z][a-z-]*=/', $file->getContents(), $matches);

            return array_map(fn (string $match): string => $file->getFilename().': '.$match, $matches[0]);
        })
        ->all();

    expect($offenders)->toBe([]);
});

it('never picks between two hardcoded words in a Vue template', function () {
    // `x ? 'Simpan' : 'Tambah pakej'` is not a lone string and not a $t call,
    // so it slipped past every extractor: thirteen buttons stayed Malay on the
    // English side long after the pages around them were translated.
    $malay = '/\?\s*.([^\x27"]{3,60}).\s*:\s*.([^\x27"]{3,60})./u';
    $words = '/\b(Simpan|Tambah|Padam|Hantar|Memuat|Muat|Batal|Tiada|Sila|Anda|Pilih|Tutup|Semak|Kemas|Cipta|Balas|Edit fasa|Edit tugasan|Edit kategori)\b/u';

    $offenders = collect(File::allFiles(resource_path('js')))
        ->filter(fn ($file): bool => $file->getExtension() === 'vue')
        ->flatMap(function ($file) use ($malay, $words): array {
            preg_match_all($malay, Str::after($file->getContents(), '<template>'), $matches);

            return array_values(array_filter(array_map(
                fn (string $match): ?string => preg_match($words, $match) ? $file->getFilename().': '.$match : null,
                $matches[0],
            )));
        })
        ->all();

    expect($offenders)->toBe([]);
});

it('never hardcodes one language inside a Vue template literal', function () {
    // A `${x} sentence` in a binding is invisible to every translation sweep,
    // because it is neither a lone string nor a $t call.
    $malay = '/`[^`\n]*\b(Hantar|Padam|Tiada|Anda|akan|dan|yang|kepada|penerima|tetamu|majlis|Pastikan|Maksimum)\b[^`\n]*`/u';

    $offenders = collect(File::allFiles(resource_path('js')))
        ->filter(fn ($file): bool => $file->getExtension() === 'vue')
        ->flatMap(function ($file) use ($malay): array {
            // Only the template half: script-side strings are checked elsewhere.
            $template = Str::after($file->getContents(), '<template>');
            preg_match_all($malay, $template, $matches);

            return array_map(fn (string $match): string => $file->getFilename().': '.$match, $matches[0]);
        })
        ->all();

    expect($offenders)->toBe([]);
});
