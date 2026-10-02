<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('keeps the motion music control in the action row instead of over the scene label', function () {
    $motion = File::get(resource_path('js/components/card/CardMotionView.vue'));
    $card = File::get(resource_path('js/components/card/CardView.vue'));

    expect(Str::between($motion, '<div class="nkc-motion-actions">', '</div>'))->toContain('<slot name="music" />')
        ->and($card)->toContain('<template #music>')
        ->and($card)->toContain('v-if="!motion" class="nkc-music-wrap"');
});

it('waits for an opening gesture before starting a music-backed motion preview', function () {
    $motion = File::get(resource_path('js/components/card/CardMotionView.vue'));
    $card = File::get(resource_path('js/components/card/CardView.vue'));

    expect($card)->toContain(':auto-open="!music"')
        ->toContain('@opened="openCard"')
        ->and($motion)->toContain('props.autoOpen && (props.preview || !props.gate.enabled)')
        ->toContain('@click="open"');
});

it('draws the motion opening label in the inner-card text colour', function () {
    $css = File::get(resource_path('css/app.css'));

    expect(Str::between($css, '.nkc-motion-envelope {', '}'))->toContain('color: var(--c-ink);');
});

it('keeps dynamic initials over optional wax seal artwork on the opening gate', function () {
    $motion = File::get(resource_path('js/components/card/CardMotionView.vue'));
    $css = File::get(resource_path('css/app.css'));

    expect($motion)->toContain('v-if="gate.seal" class="nkc-motion-seal-art"')
        ->toContain("{{ gate.initials ?? '✦' }}")
        ->and($css)->toContain('.nkc-motion-seal.has-artwork')
        ->toContain('object-fit: contain;');
});

it('applies the optional paper texture across both opening panels', function () {
    $motion = File::get(resource_path('js/components/card/CardMotionView.vue'));
    $css = File::get(resource_path('css/app.css'));

    expect($motion)->toContain("'has-texture': gate.texture")
        ->toContain('--nkc-gate-texture')
        ->and($css)->toContain('.nkc-motion-envelope.has-texture::before,')
        ->toContain('.nkc-motion-envelope.has-texture::after')
        ->toContain('background-size: 200% 100%;')
        ->toContain('filter: blur(0.6px);');
});

it('keeps the motion foreground above widgets without blocking their controls', function () {
    $motion = File::get(resource_path('js/components/card/CardMotionView.vue'));
    $css = File::get(resource_path('css/app.css'));

    expect($motion)->toContain('layer.widgetForeground')
        ->toContain("activeSlide?.kind === 'widget'")
        ->toContain('class="nkc-motion-foreground"')
        ->and(Str::between($css, '.nkc-motion-foreground {', '}'))->toContain('pointer-events: none;')
        ->toContain('animation: nkc-floral-drift 12s ease-in-out infinite alternate;')
        ->and($css)->toContain(".nkc-motion-foreground {\n        animation: none;");
});

it('keeps widget text inside the foreground artwork opening', function () {
    $motion = File::get(resource_path('js/components/card/CardMotionView.vue'));
    $css = File::get(resource_path('css/app.css'));

    expect($motion)->toContain("'has-widget-foreground': widgetForeground")
        ->toContain(':data-widget="slide.kind === \'widget\' ? slide.key : null"')
        ->and($css)->toContain('.nkc-motion-shell.has-widget-foreground .nkc-motion-slide-widget .nkc-section-inner')
        ->toContain('width: min(80%, 21rem);')
        ->toContain('.nkc-motion-shell.has-widget-foreground .nkc-motion-slide-widget .nkc-heading')
        ->toContain('.nkc-motion-slide-widget[data-widget="rsvp"] .nkc-heading');
});

it('shows animated butterflies on motion widgets and respects reduced motion', function () {
    $motion = File::get(resource_path('js/components/card/CardMotionView.vue'));
    $css = File::get(resource_path('css/app.css'));

    expect($motion)->toContain('layer.widgetAnimation')
        ->toContain('class="nkc-motion-butterflies"')
        ->toContain('!reducedMotion')
        ->and($css)->toContain('.nkc-layer-gif,')
        ->toContain('.nkc-motion-butterflies {')
        ->toContain('pointer-events: none;');
});

it('places butterflies in front of the floral arch', function () {
    $css = File::get(resource_path('css/app.css'));

    expect(Str::between($css, '.nkc-motion-foreground {', '}'))->toContain('z-index: 2;')
        ->and(Str::between($css, '.nkc-motion-butterflies {', '}'))->toContain('z-index: 3;');
});

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
