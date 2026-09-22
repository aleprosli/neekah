<?php

namespace App\Support\Card;

/**
 * Builds the layer stacks of one marketplace template from its catalogue definition.
 * A definition picks a cover composition, an invitation scene and an event scene and
 * supplies palette, fonts, background and ornament choices — every layer here is real
 * artwork (shapes, tinted SVG ornaments, text, photo frames), never a flat image.
 */
class SceneComposer
{
    use Covers;
    use CoversB;
    use Inner;

    /** Every canvas is drawn on one portrait phone-shaped stage. */
    public const WIDTH = 1080;

    public const HEIGHT = 1920;

    protected int $n = 0;

    /**
     * @param  array<string, mixed>  $d  catalogue definition
     */
    public function __construct(protected array $d) {}

    /**
     * The three designed canvases. Everything after them (tentatif, lokasi, RSVP,
     * ucapan, hadiah) is a widget rendered from the couple's own data, not artwork,
     * so it is not composed here.
     *
     * @return array<int, array{key: string, name: string, width: int, height: int, layers: array<int, array<string, mixed>>}>
     */
    public function scenes(): array
    {
        $cover = method_exists($this, 'cover_'.$this->d['cover']) ? 'cover_'.$this->d['cover'] : throw new \InvalidArgumentException('Unknown cover '.$this->d['cover']);

        return [
            ['key' => 'cover', 'name' => 'Kulit kad', 'width' => self::WIDTH, 'height' => self::HEIGHT, 'layers' => $this->{$cover}()],
            ['key' => 'invitation', 'name' => 'Jemputan', 'width' => self::WIDTH, 'height' => self::HEIGHT, 'layers' => $this->{'inv_'.$this->d['inv']}()],
            ['key' => 'event', 'name' => 'Butiran majlis', 'width' => self::WIDTH, 'height' => self::HEIGHT, 'layers' => $this->{'evt_'.$this->d['evt']}()],
        ];
    }

    /**
     * Content keys of every photo slot used by the template's layers.
     *
     * @param  array<int, array<string, mixed>>  $scenes
     * @return array<int, string>
     */
    public static function photoSlots(array $scenes): array
    {
        $keys = [];
        foreach ($scenes as $scene) {
            foreach ($scene['layers'] as $layer) {
                if (($layer['bind'] ?? null) && ! in_array($layer['bind'], $keys, true)) {
                    $keys[] = $layer['bind'];
                }
            }
        }

        return $keys;
    }

    // ------------------------------------------------------------------ layer helpers

    protected function c(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }

        if (str_starts_with($role, '#')) {
            return $role;
        }

        return in_array($role, Palettes::ROLES, true) ? 'role:'.$role : '#888888';
    }

    /**
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    protected function L(string $type, string $name, array $o): array
    {
        $layer = LayerSchema::sanitize(['type' => $type, 'name' => $name] + $o);
        $layer['id'] = 'ly'.(++$this->n);

        return $layer;
    }

    /**
     * Soft drop shadow presets: true = neutral, 'glow' = accent glow, 'deep' = strong.
     *
     * @return array<string, mixed>|null
     */
    protected function shadow(mixed $sh): ?array
    {
        return match ($sh) {
            true, 'soft' => ['x' => 0, 'y' => 8, 'blur' => 22, 'color' => '#000000', 'opacity' => 26],
            'deep' => ['x' => 0, 'y' => 22, 'blur' => 44, 'color' => '#000000', 'opacity' => 40],
            'glow' => ['x' => 0, 'y' => 0, 'blur' => 24, 'color' => 'role:acc', 'opacity' => 55],
            default => null,
        };
    }

    /**
     * Text layer. o: f font role (d,s,r,n) · s size · c colour · wt weight · ls spacing · lh line height · up · it · al · va · sh · op · rot
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    protected function t(string $text, int $x, int $y, int $w, int $h, array $o = []): array
    {
        $text = str_replace('\\n', "\n", $text);

        return $this->L('text', $o['name'] ?? trim(preg_replace('/[{}_\n]+/', ' ', mb_substr($text, 0, 22))), [
            'text' => $text, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'font' => 'role:'.(in_array($o['f'] ?? 'r', Fonts::ROLES, true) ? $o['f'] ?? 'r' : 'r'), 'size' => $o['s'] ?? 40, 'weight' => $o['wt'] ?? 400, 'italic' => $o['it'] ?? false,
            'color' => $this->c($o['c'] ?? 'ink'), 'align' => $o['al'] ?? 'center', 'valign' => $o['va'] ?? 'middle',
            'spacing' => $o['ls'] ?? 0, 'lineHeight' => $o['lh'] ?? 1.2, 'transform' => ($o['up'] ?? false) ? 'uppercase' : 'none',
            'opacity' => $o['op'] ?? 100, 'rotation' => $o['rot'] ?? 0, 'shadow' => $this->shadow($o['sh'] ?? null), 'blend' => $o['bl'] ?? 'normal',
        ]);
    }

    /**
     * Designer shape. o: fill · fill2 · ang · kind · r · bd · bc · op · rot · bl · sh · blur
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    protected function s(int $x, int $y, int $w, int $h, array $o = []): array
    {
        return $this->L('shape', $o['name'] ?? 'Shape', [
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'fill' => $this->c($o['fill'] ?? 'acc'), 'fill2' => $this->c($o['fill2'] ?? null),
            'angle' => $o['ang'] ?? 160, 'kind' => $o['kind'] ?? 'rect', 'radius' => $o['r'] ?? 0,
            'border' => isset($o['bd']) ? ['width' => $o['bd'], 'color' => $this->c($o['bc'] ?? 'acc')] : null,
            'opacity' => $o['op'] ?? 100, 'rotation' => $o['rot'] ?? 0, 'blend' => $o['bl'] ?? 'normal',
            'shadow' => $this->shadow($o['sh'] ?? null), 'blur' => $o['blur'] ?? 0, 'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * Tinted SVG ornament. o: c · c2 · ang · op · rot · tile · bl · sh · name
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    protected function o(string $asset, int $x, int $y, int $w, int $h, array $o = []): array
    {
        return $this->L('ornament', $o['name'] ?? ucfirst(str_replace('-', ' ', $asset)), [
            'src' => '/img/layers/'.$asset.'.svg', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'color' => $this->c($o['c'] ?? 'acc'), 'color2' => $this->c($o['c2'] ?? null), 'angle' => $o['ang'] ?? 135,
            'opacity' => $o['op'] ?? 100, 'rotation' => $o['rot'] ?? 0, 'tile' => $o['tile'] ?? 0, 'blend' => $o['bl'] ?? 'normal',
            'shadow' => $this->shadow($o['sh'] ?? null), 'blur' => $o['blur'] ?? 0, 'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * Baked-colour artwork / texture tile (grain, paper, linen…).
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    protected function g(string $asset, int $x, int $y, int $w, int $h, array $o = []): array
    {
        return $this->L('image', $o['name'] ?? ucfirst(str_replace('-', ' ', $asset)), [
            'src' => '/img/layers/'.$asset.'.svg', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'tile' => $o['tile'] ?? 0, 'shape' => 'rect',
            'opacity' => $o['op'] ?? 100, 'blend' => $o['bl'] ?? 'normal', 'rotation' => $o['rot'] ?? 0, 'shadow' => $this->shadow($o['sh'] ?? null),
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * Customer photo slot bound to a content key, with a demo placeholder.
     * o: shape · r · bd · bc · sh · rot · zoom · ph (placeholder asset) · op
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    protected function i(string $bind, int $x, int $y, int $w, int $h, array $o = []): array
    {
        $ph = $o['ph'] ?? (in_array($bind, ['groom_image', 'bride_image'], true) ? 'photo-portrait' : 'photo-couple');

        return $this->L('image', $o['name'] ?? ucfirst(str_replace('_', ' ', $bind)), [
            'bind' => $bind, 'src' => '/img/layers/'.$ph.'.svg', 'placeholder' => true, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'shape' => $o['shape'] ?? 'rect', 'radius' => $o['r'] ?? 24, 'zoom' => $o['zoom'] ?? 100,
            'border' => isset($o['bd']) ? ['width' => $o['bd'], 'color' => $this->c($o['bc'] ?? 'acc')] : null,
            'shadow' => $this->shadow($o['sh'] ?? null), 'rotation' => $o['rot'] ?? 0, 'opacity' => $o['op'] ?? 100,
        ]);
    }

    // ------------------------------------------------------------------ shared building blocks

    /**
     * Cover/inner background from the definition: base colour treatment, pattern, light, texture.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function bg(string $role = 'cover'): array
    {
        $kind = $this->d['bg'];
        $inner = $role !== 'cover';
        $a = $inner ? 'card' : 'bg';
        $b = $inner ? ($this->d['card2'] ?? 'card') : 'bg2';
        $layers = [$this->s(0, 0, 1080, 1920, ['name' => 'Background', 'fill' => $a, 'fill2' => $b, 'ang' => 165])];

        $pattern = ['songket' => ['pattern-lattice', 120], 'islamic' => ['pattern-islamic', 130], 'hex' => ['pattern-hex', 120], 'rebung' => ['pattern-rebung', 120],
            'tenun' => ['pattern-tenun', 96], 'pucuk' => ['pattern-pucuk', 110], 'dots' => ['pattern-dots', 60], 'diag' => ['pattern-diagonal', 60], 'lines' => ['pattern-lines', 40]][$kind] ?? null;
        if ($pattern && ! ($inner && ($this->d['bgInner'] ?? true) === false)) {
            $layers[] = $this->o($pattern[0], 0, 0, 1080, 1920, ['name' => 'Pattern', 'tile' => $pattern[1], 'c' => $this->d['patternColor'] ?? 'acc', 'op' => $this->d['patternOp'] ?? ($inner ? 12 : 24)]);
        }
        if ($kind === 'night' && ! $inner) {
            $layers[] = $this->o('particles', 0, 0, 1080, 1920, ['name' => 'Star particles', 'c' => 'acc2', 'op' => 75]);
        }
        if (in_array($kind, ['paper', 'linen'], true)) {
            $layers[] = $this->g($kind === 'paper' ? 'paper' : 'linen', 0, 0, 1080, 1920, ['name' => 'Texture', 'tile' => $kind === 'paper' ? 400 : 200, 'op' => $kind === 'paper' ? 70 : 60]);
        }
        if (! empty($this->d['glow'])) {
            $layers[] = $this->s(90, $inner ? 400 : 480, 900, 900, ['name' => 'Soft light', 'kind' => 'glow', 'fill' => $this->d['glow'], 'op' => $inner ? 18 : ($this->d['glowOp'] ?? 30)]);
        }
        if (! empty($this->d['vignette']) && ! $inner) {
            $layers[] = $this->s(0, 0, 1080, 520, ['name' => 'Top shade', 'kind' => 'fade', 'fill' => '#000000', 'ang' => 180, 'op' => 38]);
            $layers[] = $this->s(0, 1400, 1080, 520, ['name' => 'Bottom shade', 'kind' => 'fade', 'fill' => '#000000', 'ang' => 0, 'op' => 42]);
        }
        if (! empty($this->d['grain'])) {
            $layers[] = $this->g('grain', 0, 0, 1080, 1920, ['name' => 'Film grain', 'tile' => 300, 'op' => $this->d['grain'], 'bl' => in_array($kind, ['velvet', 'night', 'obsidian'], true) ? 'screen' : 'multiply']);
        }

        return $layers;
    }

    /**
     * Ornamental floral/leaf corners: diag (TL+BR), all (4), top (TL+TR), bottom (BL+BR).
     *
     * @param  array<string, mixed>  $o
     * @return array<int, array<string, mixed>>
     */
    protected function corners(?string $asset, string $mode, int $size, int $inset = 0, array $o = []): array
    {
        if ($asset === null) {
            return [];
        }

        $spots = [
            'tl' => [$inset, $inset, 0], 'tr' => [1080 - $size - $inset, $inset, 90],
            'br' => [1080 - $size - $inset, 1920 - $size - $inset, 180], 'bl' => [$inset, 1920 - $size - $inset, 270],
        ];
        $use = ['diag' => ['tl', 'br'], 'all' => ['tl', 'tr', 'br', 'bl'], 'top' => ['tl', 'tr'], 'bottom' => ['bl', 'br'], 'tl' => ['tl'], 'br' => ['br'], 'anti' => ['tr', 'bl']][$mode];
        $out = [];
        foreach ($use as $k) {
            [$x, $y, $r] = $spots[$k];
            $out[] = $this->o($asset, $x, $y, $size, $size, $o + ['name' => 'Corner '.strtoupper($k), 'rot' => $r, 'c' => 'acc', 'sh' => $this->d['ornShadow'] ?? true]);
        }

        return $out;
    }

    /**
     * Stacked groom / & / bride block.
     *
     * @param  array<string, mixed>  $o  s size · f font · c colour · amp colour · gap · tok (name token) · x · w · al · up · sh · ls
     * @return array<int, array<string, mixed>>
     */
    protected function names(int $y, array $o = []): array
    {
        $s = $o['s'] ?? 170;
        $x = $o['x'] ?? 60;
        $w = $o['w'] ?? 960;
        $tok = $o['tok'] ?? 'short';
        $common = ['f' => $o['f'] ?? 'd', 's' => $s, 'c' => $o['c'] ?? 'head', 'al' => $o['al'] ?? 'center', 'lh' => 1, 'up' => $o['up'] ?? false, 'sh' => $o['sh'] ?? null, 'ls' => $o['ls'] ?? 0, 'wt' => $o['wt'] ?? 400];
        $gh = (int) ($s * 1.25);
        $ah = (int) ($s * ($o['ampScale'] ?? .5));

        return [
            $this->t($tok === 'short' ? '{{groom_short}}' : '{{groom_name}}', $x, $y, $w, $gh, $common + ['name' => 'Groom name']),
            $this->t('&', $x, $y + $gh, $w, $ah, ['f' => $o['ampF'] ?? 'r', 's' => (int) ($s * .42), 'c' => $o['amp'] ?? 'acc', 'it' => true, 'al' => $o['al'] ?? 'center', 'name' => 'Ampersand']),
            $this->t($tok === 'short' ? '{{bride_short}}' : '{{bride_name}}', $x, $y + $gh + $ah, $w, $gh, $common + ['name' => 'Bride name']),
        ];
    }

    /**
     * Date + venue lines.
     *
     * @param  array<string, mixed>  $o  c · s · f · al · x · w
     * @return array<int, array<string, mixed>>
     */
    protected function when(int $y, array $o = []): array
    {
        $x = $o['x'] ?? 90;
        $w = $o['w'] ?? 900;

        return [
            $this->t('{{wedding_date}}', $x, $y, $w, 70, ['f' => $o['f'] ?? 'r', 's' => $o['s'] ?? 46, 'c' => $o['c'] ?? 'onbg', 'ls' => .28, 'up' => true, 'al' => $o['al'] ?? 'center', 'name' => 'Wedding date']),
            $this->t('{{venue_name}}', $x, $y + 76, $w, 56, ['f' => $o['f2'] ?? 'r', 's' => (int) (($o['s'] ?? 46) * .8), 'c' => $o['c'] ?? 'onbg', 'it' => true, 'al' => $o['al'] ?? 'center', 'op' => 88, 'name' => 'Venue']),
        ];
    }

    protected function eyebrow(int $y, ?string $c = null, string $text = 'WALIMATUL URUS', int $x = 90, int $w = 900, string $al = 'center'): array
    {
        return $this->t($text, $x, $y, $w, 56, ['f' => 'n', 's' => 28, 'wt' => 500, 'ls' => .42, 'c' => $c ?? 'acc', 'al' => $al, 'name' => 'Heading line']);
    }

    /**
     * Thin horizontal/vertical rule.
     *
     * @param  array<string, mixed>  $o
     */
    protected function rule(int $x, int $y, int $len, array $o = []): array
    {
        $th = $o['th'] ?? 2;
        $v = $o['v'] ?? false;

        return $this->s($x, $y, $v ? $th : $len, $v ? $len : $th, ['name' => 'Rule', 'fill' => $o['c'] ?? 'acc', 'op' => $o['op'] ?? 80]);
    }

    /**
     * Photo placeholder + frame decorations for common shapes.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function divider(string $asset, int $y, int $w = 560, ?string $c = null): array
    {
        $h = ['divider-diamond' => (int) ($w / 10), 'divider-floral' => (int) ($w * .133), 'divider-wave' => (int) ($w / 15), 'divider-dots' => (int) ($w / 20), 'divider-star' => (int) ($w / 10), 'divider-double' => (int) ($w / 25)][$asset] ?? 50;

        return [$this->o($asset, (int) ((1080 - $w) / 2), $y, $w, $h, ['name' => 'Divider', 'c' => $c ?? 'acc', 'c2' => $c === null ? 'acc2' : null])];
    }
}
