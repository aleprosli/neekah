<?php

namespace App\Support\Card;

/**
 * Cover compositions 01–25: traditional, modern luxury and floral collections.
 * Each method returns the complete bottom-to-top layer stack of the cover scene.
 */
trait Covers
{
    protected function cover_centered(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-ornate', 40, 60, 1000, 1800, ['name' => 'Gold frame', 'c' => 'acc', 'c2' => 'acc2', 'sh' => true]);
        array_push($L, ...$this->corners($this->d['orn'], 'diag', 520, 30, ['op' => 92, 'c2' => 'acc2']));
        $L[] = $this->o('sparkle', 490, 330, 100, 100, ['c' => 'acc2', 'sh' => 'glow']);
        $L[] = $this->i('couple_image', 340, 460, 400, 400, ['shape' => 'circle', 'bd' => 6, 'bc' => 'acc', 'sh' => 'deep', 'name' => 'Couple photo']);
        $L[] = $this->o('halo', 300, 420, 480, 480, ['c' => 'acc', 'op' => 70, 'name' => 'Photo halo']);
        $L[] = $this->eyebrow(900);
        array_push($L, ...$this->names(950, ['s' => 130, 'f' => 's', 'c' => 'head', 'sh' => true]));
        array_push($L, ...$this->divider('divider-diamond', 1380));
        array_push($L, ...$this->when(1450, ['s' => 44]));

        return $L;
    }

    protected function cover_palace(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-thin', 30, 50, 1020, 1820, ['c' => 'acc', 'op' => 70, 'name' => 'Outer frame']);
        $L[] = $this->o('pillars', 60, 300, 960, 1440, ['c' => 'acc', 'c2' => 'acc2', 'op' => 38, 'name' => 'Palace pillars']);
        $L[] = $this->o('arch-ogee', 130, 190, 820, 1170, ['c' => 'acc', 'c2' => 'acc2', 'sh' => 'glow', 'name' => 'Palace arch']);
        $L[] = $this->o('crescent-star', 480, 60, 120, 120, ['c' => 'acc2', 'sh' => 'glow']);
        $L[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 270, 400, 540, 110, ['f' => 'r', 's' => 56, 'c' => 'acc2', 'name' => 'Bismillah']);
        $L[] = $this->eyebrow(560, 'acc', 'WALIMATUL URUS', 240, 600);
        array_push($L, ...$this->names(640, ['s' => 116, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .06, 'w' => 700, 'x' => 190]));
        array_push($L, ...$this->divider('divider-star', 1200, 400));
        array_push($L, ...$this->when(1420, ['s' => 44, 'c' => 'onbg']));
        $L[] = $this->o('particles', 0, 0, 1080, 1920, ['c' => 'acc2', 'op' => 22, 'name' => 'Gold dust']);

        return $L;
    }

    protected function cover_border(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-scallop', 40, 60, 1000, 1800, ['c' => 'acc', 'op' => 90, 'name' => 'Scalloped border']);
        array_push($L, ...$this->corners('corner-filigree', 'all', 250, 70, ['c' => 'acc', 'c2' => 'acc2']));
        $L[] = $this->o('pattern-rebung', 330, 150, 420, 84, ['tile' => 84, 'c' => 'acc', 'op' => 80, 'name' => 'Pucuk rebung band']);
        $L[] = $this->o('pattern-rebung', 330, 1686, 420, 84, ['tile' => 84, 'c' => 'acc', 'op' => 80, 'rot' => 180, 'name' => 'Pucuk rebung band low']);
        $L[] = $this->eyebrow(300);
        array_push($L, ...$this->names(360, ['s' => 120, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .05, 'tok' => 'short']));
        array_push($L, ...$this->divider('divider-floral', 740, 520));
        $L[] = $this->i('couple_image', 350, 830, 380, 380, ['shape' => 'circle', 'bd' => 8, 'bc' => 'acc', 'sh' => 'deep']);
        array_push($L, ...$this->when(1290, ['s' => 42]));

        return $L;
    }

    protected function cover_quiet(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-thin', 60, 80, 960, 1760, ['c' => 'acc', 'op' => 85, 'name' => 'Gold frame']);
        $L[] = $this->o('corner-blossom', 60, 80, 330, 330, ['c' => 'acc', 'op' => 70, 'name' => 'Blossom corner']);
        $L[] = $this->o('sparkle', 510, 380, 60, 60, ['c' => 'acc']);
        $L[] = $this->eyebrow(480, 'mut', 'WALIMATUL URUS');
        array_push($L, ...$this->names(560, ['s' => 128, 'f' => 'd', 'c' => 'head', 'wt' => 300, 'up' => true, 'ls' => .12, 'amp' => 'acc', 'ampF' => 'd']));
        $L[] = $this->rule(440, 1260, 200, ['c' => 'acc']);
        array_push($L, ...$this->when(1320, ['s' => 40, 'c' => 'ink']));
        $L[] = $this->o('pattern-lattice', 60, 1560, 960, 220, ['tile' => 110, 'c' => 'acc', 'op' => 28, 'name' => 'Songket band']);

        return $L;
    }

    protected function cover_cempaka(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-single', 50, 70, 980, 1780, ['c' => 'acc', 'op' => 80, 'name' => 'Frame']);
        $L[] = $this->o('corner-cempaka', -40, -40, 760, 760, ['c' => 'acc', 'c2' => 'acc2', 'op' => 95, 'sh' => true, 'name' => 'Cempaka top']);
        $L[] = $this->o('corner-cempaka', 360, 1200, 760, 760, ['c' => 'acc', 'c2' => 'acc2', 'op' => 95, 'rot' => 180, 'sh' => true, 'name' => 'Cempaka bottom']);
        $L[] = $this->o('cluster-cempaka', 640, 1300, 300, 300, ['c' => 'acc2', 'op' => 60, 'rot' => 20]);
        $L[] = $this->o('sprig-olive', 60, 1080, 170, 290, ['c' => 'acc', 'op' => 70, 'rot' => -20]);
        $L[] = $this->eyebrow(640, 'acc');
        array_push($L, ...$this->names(700, ['s' => 150, 'f' => 's', 'c' => 'head']));
        array_push($L, ...$this->divider('divider-floral', 1200, 500));
        array_push($L, ...$this->when(1280, ['s' => 42, 'c' => 'ink']));

        return $L;
    }

    protected function cover_sidebands(): array
    {
        $L = $this->bg();
        foreach ([0, 970] as $x) {
            $L[] = $this->o('pattern-pucuk', $x, 0, 110, 1920, ['tile' => 110, 'c' => 'acc', 'op' => 65, 'name' => 'Pucuk band']);
            $L[] = $this->rule($x === 0 ? 118 : 958, 0, 1920, ['v' => true, 'c' => 'acc', 'op' => 90]);
        }
        $L[] = $this->o('corner-tropical', 110, 0, 620, 620, ['c' => 'acc2', 'op' => 70, 'name' => 'Tropical leaves top']);
        $L[] = $this->o('corner-tropical', 350, 1300, 620, 620, ['c' => 'acc2', 'op' => 70, 'rot' => 180, 'name' => 'Tropical leaves bottom']);
        $L[] = $this->o('medallion', 380, 340, 320, 320, ['c' => 'acc', 'op' => 85, 'name' => 'Medallion']);
        $L[] = $this->eyebrow(700, 'acc', 'MAJLIS PERKAHWINAN', 160, 760);
        array_push($L, ...$this->names(770, ['s' => 130, 'f' => 'd', 'c' => 'head', 'x' => 160, 'w' => 760, 'up' => true, 'ls' => .04]));
        array_push($L, ...$this->divider('divider-diamond', 1240, 440));
        array_push($L, ...$this->when(1330, ['x' => 160, 'w' => 760, 's' => 42]));

        return $L;
    }

    protected function cover_hall(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-ornate', 40, 60, 1000, 1800, ['c' => 'acc', 'c2' => 'acc2', 'op' => 80, 'name' => 'Grand frame']);
        $L[] = $this->s(190, 260, 700, 933, ['fill' => 'bg2', 'r' => 0, 'op' => 55, 'name' => 'Arch backdrop', 'sh' => true]);
        $L[] = $this->i('couple_image', 272, 342, 536, 793, ['shape' => 'arch', 'sh' => 'deep', 'name' => 'Arch photo']);
        $L[] = $this->o('arch-frame', 190, 260, 700, 933, ['c' => 'acc', 'c2' => 'acc2', 'sh' => 'glow', 'name' => 'Arch outline']);
        array_push($L, ...$this->corners('corner-rose', 'top', 470, 30, ['c' => 'acc', 'op' => 88]));
        array_push($L, ...$this->corners('corner-rose', 'bottom', 330, 30, ['c' => 'acc', 'op' => 88]));
        $L[] = $this->eyebrow(1230, 'acc');
        array_push($L, ...$this->names(1290, ['s' => 100, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .06, 'tok' => 'short']));
        array_push($L, ...$this->when(1650, ['s' => 40]));

        return $L;
    }

    protected function cover_tenun(): array
    {
        $L = $this->bg();
        $L[] = $this->o('pattern-tenun', 0, 0, 1080, 250, ['tile' => 100, 'c' => 'acc', 'op' => 92, 'name' => 'Woven band top']);
        $L[] = $this->o('pattern-tenun', 0, 1670, 1080, 250, ['tile' => 100, 'c' => 'acc', 'op' => 92, 'name' => 'Woven band bottom']);
        $L[] = $this->rule(0, 250, 1080, ['th' => 6, 'c' => 'acc2', 'op' => 100]);
        $L[] = $this->rule(0, 1664, 1080, ['th' => 6, 'c' => 'acc2', 'op' => 100]);
        $L[] = $this->s(110, 340, 860, 1240, ['fill' => 'card', 'r' => 6, 'sh' => 'deep', 'name' => 'Paper card']);
        $L[] = $this->o('frame-thin', 140, 370, 800, 1180, ['c' => 'acc', 'op' => 80, 'name' => 'Card frame']);
        $L[] = $this->o('sprig-olive', 120, 1180, 220, 380, ['c' => 'head', 'op' => 65, 'rot' => -8]);
        $L[] = $this->o('sprig-olive', 740, 1180, 220, 380, ['c' => 'head', 'op' => 65, 'rot' => 8]);
        $L[] = $this->o('medallion', 440, 440, 200, 200, ['c' => 'head', 'op' => 80]);
        $L[] = $this->eyebrow(700, 'head', 'TENUN · HERITAGE');
        array_push($L, ...$this->names(770, ['s' => 130, 'f' => 'd', 'c' => 'ink', 'up' => false, 'amp' => 'head']));
        array_push($L, ...$this->when(1330, ['s' => 40, 'c' => 'ink']));

        return $L;
    }

    protected function cover_wreath(): array
    {
        $L = $this->bg();
        $L[] = $this->o('wreath-rose', 110, 280, 860, 860, ['c' => 'head', 'c2' => 'acc', 'op' => 95, 'sh' => true, 'name' => 'Rose wreath']);
        $L[] = $this->i('couple_image', 290, 460, 500, 500, ['shape' => 'circle', 'bd' => 8, 'bc' => 'card', 'sh' => 'deep']);
        $L[] = $this->o('halo', 262, 432, 556, 556, ['c' => 'acc', 'op' => 80]);
        $L[] = $this->o('sparkle', 900, 200, 70, 70, ['c' => 'acc2', 'sh' => 'glow']);
        $L[] = $this->o('sparkle', 120, 1180, 50, 50, ['c' => 'acc2', 'sh' => 'glow']);
        $L[] = $this->eyebrow(1190, 'acc');
        array_push($L, ...$this->names(1240, ['s' => 118, 'f' => 's', 'c' => 'head']));
        array_push($L, ...$this->divider('divider-floral', 1610, 460));
        array_push($L, ...$this->when(1680, ['s' => 34, 'c' => 'ink']));

        return $L;
    }

    protected function cover_ornament(): array
    {
        $L = $this->bg();
        $L[] = $this->o('star8', -120, 380, 1320, 1320, ['c' => 'acc', 'op' => 14, 'name' => 'Star medallion']);
        $L[] = $this->o('frame-ornate', 40, 60, 1000, 1800, ['c' => 'acc', 'c2' => 'acc2', 'op' => 92, 'sh' => 'glow']);
        array_push($L, ...$this->corners('corner-filigree', 'all', 300, 60, ['c' => 'acc', 'c2' => 'acc2']));
        $L[] = $this->o('medallion', 340, 250, 400, 400, ['c' => 'acc', 'c2' => 'acc2', 'op' => 95, 'sh' => 'glow']);
        $L[] = $this->eyebrow(690, 'acc', 'ISTIADAT PERKAHWINAN');
        array_push($L, ...$this->names(750, ['s' => 190, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .02, 'sh' => 'deep']));
        array_push($L, ...$this->divider('divider-diamond', 1380));
        array_push($L, ...$this->when(1450, ['s' => 46, 'c' => 'acc2']));

        return $L;
    }

    protected function cover_typo(): array
    {
        $L = $this->bg();
        $L[] = $this->rule(80, 180, 920, ['c' => 'acc', 'op' => 100]);
        $L[] = $this->t('WALIMATUL URUS', 80, 210, 600, 50, ['f' => 'n', 's' => 26, 'ls' => .5, 'c' => 'acc', 'al' => 'left', 'wt' => 500]);
        $L[] = $this->t('N° 01', 780, 210, 220, 50, ['f' => 'n', 's' => 26, 'ls' => .3, 'c' => 'mut', 'al' => 'right']);
        $L[] = $this->t('{{groom_short}}', 70, 340, 940, 330, ['f' => 'd', 's' => 250, 'c' => 'head', 'up' => true, 'al' => 'left', 'lh' => 1, 'name' => 'Groom name']);
        $L[] = $this->t('&', 690, 660, 300, 220, ['f' => 'r', 's' => 190, 'c' => 'acc', 'it' => true, 'al' => 'right', 'name' => 'Ampersand']);
        $L[] = $this->t('{{bride_short}}', 70, 800, 940, 330, ['f' => 'd', 's' => 250, 'c' => 'head', 'up' => true, 'al' => 'left', 'lh' => 1, 'name' => 'Bride name']);
        $L[] = $this->rule(80, 1300, 920, ['c' => 'acc', 'op' => 100]);
        $L[] = $this->t('{{wedding_date}}', 80, 1340, 700, 70, ['f' => 'n', 's' => 40, 'ls' => .25, 'up' => true, 'c' => 'onbg', 'al' => 'left', 'name' => 'Wedding date']);
        $L[] = $this->t('{{venue_name}} · {{venue_address}}', 80, 1420, 800, 120, ['f' => 'n', 's' => 28, 'c' => 'mut', 'al' => 'left', 'va' => 'top', 'lh' => 1.5, 'name' => 'Venue']);
        $L[] = $this->o('sparkle', 900, 1350, 70, 70, ['c' => 'acc']);
        $L[] = $this->o('sparkle', 840, 1430, 34, 34, ['c' => 'acc', 'op' => 70]);

        return $L;
    }

    protected function cover_minimal(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-thin', 60, 80, 960, 1760, ['c' => 'acc', 'op' => 90]);
        $L[] = $this->o('garland', 190, 170, 700, 140, ['c' => 'acc', 'op' => 55, 'name' => 'Floral garland']);
        $L[] = $this->eyebrow(560, 'mut');
        array_push($L, ...$this->names(640, ['s' => 140, 'f' => 'd', 'c' => 'head', 'wt' => 400, 'amp' => 'acc']));
        array_push($L, ...$this->divider('divider-dots', 1220, 360));
        array_push($L, ...$this->when(1290, ['s' => 40, 'c' => 'ink']));
        $L[] = $this->o('leaf-sprig', 470, 1560, 140, 200, ['c' => 'acc', 'op' => 60, 'name' => 'Sprig']);

        return $L;
    }

    protected function cover_split(): array
    {
        $L = $this->bg();
        $L[] = $this->i('cover_image', 0, 0, 1080, 1080, ['shape' => 'rect', 'r' => 0, 'name' => 'Cover photo']);
        $L[] = $this->s(0, 640, 1080, 460, ['kind' => 'fade', 'fill' => 'bg', 'ang' => 0, 'name' => 'Photo fade']);
        $L[] = $this->s(0, 0, 1080, 300, ['kind' => 'fade', 'fill' => '#ffffff', 'ang' => 180, 'op' => 28, 'name' => 'Top light']);
        $L[] = $this->s(140, 1010, 800, 800, ['kind' => 'glow', 'fill' => 'acc2', 'op' => 35, 'name' => 'Soft lighting']);
        $L[] = $this->eyebrow(1030, 'acc');
        array_push($L, ...$this->names(1090, ['s' => 130, 'f' => 'd', 'c' => 'head']));
        array_push($L, ...$this->divider('divider-diamond', 1520, 440));
        array_push($L, ...$this->when(1590, ['s' => 42, 'c' => 'ink']));

        return $L;
    }

    protected function cover_editorial(): array
    {
        $L = $this->bg();
        $L[] = $this->t('JIL. 01 — EDISI PERKAHWINAN', 70, 90, 700, 40, ['f' => 'n', 's' => 22, 'ls' => .35, 'c' => 'ink', 'al' => 'left']);
        $L[] = $this->t('{{wedding_date}}', 600, 90, 410, 40, ['f' => 'n', 's' => 22, 'ls' => .25, 'c' => 'ink', 'al' => 'right', 'up' => true]);
        $L[] = $this->rule(70, 150, 940, ['c' => 'ink', 'op' => 90, 'th' => 3]);
        $L[] = $this->t('{{groom_short}}', 60, 190, 960, 280, ['f' => 'd', 's' => 240, 'c' => 'head', 'al' => 'left', 'lh' => 1, 'name' => 'Groom name']);
        $L[] = $this->t('{{bride_short}}', 60, 450, 960, 280, ['f' => 'd', 's' => 240, 'c' => 'head', 'al' => 'left', 'it' => true, 'lh' => 1, 'name' => 'Bride name']);
        $L[] = $this->s(470, 800, 540, 780, ['fill' => 'acc', 'op' => 100, 'name' => 'Photo offset block']);
        $L[] = $this->i('couple_image', 440, 770, 540, 780, ['shape' => 'rect', 'r' => 0, 'sh' => 'deep', 'name' => 'Feature photo']);
        $L[] = $this->t('“Kami menemui selamanya\ndalam satu sama lain.”', 70, 860, 330, 260, ['f' => 'r', 's' => 42, 'it' => true, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'lh' => 1.3, 'name' => 'Pull quote']);
        $L[] = $this->rule(70, 1200, 260, ['c' => 'acc']);
        $L[] = $this->t('{{venue_name}}\n{{venue_address}}', 70, 1230, 340, 200, ['f' => 'n', 's' => 26, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'lh' => 1.6, 'ls' => .05, 'name' => 'Venue']);
        $L[] = $this->t('{{time_12}}', 70, 1470, 320, 80, ['f' => 'r', 's' => 64, 'c' => 'acc', 'al' => 'left', 'wt' => 500, 'name' => 'Time']);
        $L[] = $this->rule(70, 1690, 940, ['c' => 'ink', 'op' => 90, 'th' => 3]);
        $L[] = $this->t('EDISI PERKAHWINAN NEEKAH', 70, 1710, 700, 40, ['f' => 'n', 's' => 20, 'ls' => .4, 'c' => 'ink', 'al' => 'left']);

        return $L;
    }

    protected function cover_geo(): array
    {
        $L = $this->bg();
        $L[] = $this->o('geo-lines', 40, 160, 1000, 1600, ['c' => 'acc', 'op' => 70, 'name' => 'Geometric lines']);
        $L[] = $this->s(240, 520, 600, 600, ['kind' => 'ring', 'bd' => 2, 'bc' => 'acc', 'name' => 'Gold ring']);
        $L[] = $this->s(270, 550, 540, 540, ['kind' => 'ring', 'bd' => 1, 'bc' => 'acc', 'op' => 60, 'name' => 'Inner ring']);
        $L[] = $this->s(510, 490, 60, 60, ['fill' => 'bg', 'kind' => 'rect', 'name' => 'Ring gap']);
        $L[] = $this->o('sparkle', 520, 496, 40, 40, ['c' => 'acc']);
        array_push($L, ...$this->names(640, ['s' => 74, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .32, 'wt' => 300, 'x' => 260, 'w' => 560, 'ampScale' => .8, 'amp' => 'acc']));
        $L[] = $this->t('{{wedding_date}}', 90, 1200, 900, 60, ['f' => 'n', 's' => 30, 'ls' => .4, 'up' => true, 'c' => 'ink', 'name' => 'Wedding date']);
        $L[] = $this->rule(490, 1290, 100, ['c' => 'acc']);
        $L[] = $this->t('{{venue_name}}', 90, 1320, 900, 50, ['f' => 'n', 's' => 26, 'ls' => .3, 'c' => 'mut', 'name' => 'Venue']);

        return $L;
    }

    protected function cover_halo(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-thin', 40, 60, 1000, 1800, ['c' => 'acc', 'op' => 75]);
        $L[] = $this->o('halo', 120, 420, 840, 840, ['c' => 'acc', 'c2' => 'acc2', 'op' => 95, 'sh' => 'glow', 'name' => 'Golden halo']);
        $L[] = $this->s(190, 490, 700, 700, ['kind' => 'ring', 'bd' => 2, 'bc' => 'acc', 'op' => 60, 'name' => 'Inner ring']);
        $L[] = $this->o('crescent-star', 470, 250, 140, 140, ['c' => 'acc2', 'sh' => 'glow']);
        $L[] = $this->eyebrow(560, 'acc');
        array_push($L, ...$this->names(620, ['s' => 130, 'f' => 's', 'c' => 'head', 'x' => 200, 'w' => 680, 'sh' => 'glow']));
        array_push($L, ...$this->divider('divider-star', 1370, 400));
        array_push($L, ...$this->when(1450, ['s' => 44, 'c' => 'onbg']));

        return $L;
    }

    protected function cover_deco(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-deco', 40, 60, 1000, 1800, ['c' => 'acc', 'c2' => 'acc2', 'sh' => 'glow', 'name' => 'Deco frame']);
        $L[] = $this->o('divider-double', 200, 360, 680, 27, ['c' => 'acc', 'name' => 'Deco rule']);
        $L[] = $this->o('sparkle', 500, 420, 80, 80, ['c' => 'acc2', 'sh' => 'glow']);
        $L[] = $this->eyebrow(560, 'acc', 'MEMOHON KEHADIRAN TUAN/PUAN', 130, 820);
        array_push($L, ...$this->names(650, ['s' => 160, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .04, 'sh' => true]));
        $L[] = $this->o('divider-double', 200, 1360, 680, 27, ['c' => 'acc']);
        array_push($L, ...$this->when(1420, ['s' => 46, 'c' => 'acc2']));
        $L[] = $this->o('sparkle', 500, 1640, 80, 80, ['c' => 'acc2', 'sh' => 'glow']);

        return $L;
    }

    protected function cover_botanical(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-thin', 50, 70, 980, 1780, ['c' => 'acc', 'op' => 80]);
        $L[] = $this->o('sprig-olive', -20, 320, 420, 720, ['c' => 'acc', 'op' => 88, 'rot' => 6, 'sh' => true, 'name' => 'Olive branch left']);
        $L[] = $this->o('sprig-eucalyptus', 680, 320, 420, 720, ['c' => 'acc2', 'op' => 80, 'rot' => -6, 'sh' => true, 'name' => 'Eucalyptus right']);
        $L[] = $this->o('sprig-olive', 620, 1180, 380, 650, ['c' => 'acc', 'op' => 88, 'rot' => 190, 'name' => 'Olive branch low']);
        $L[] = $this->i('couple_image', 290, 250, 500, 690, ['shape' => 'oval', 'bd' => 5, 'bc' => 'acc', 'sh' => 'deep', 'name' => 'Oval portrait']);
        $L[] = $this->eyebrow(1010, 'acc');
        array_push($L, ...$this->names(1070, ['s' => 100, 'f' => 'd', 'c' => 'head', 'x' => 200, 'w' => 680, 'up' => true, 'ls' => .08]));
        array_push($L, ...$this->when(1450, ['s' => 40, 'c' => 'onbg']));

        return $L;
    }

    protected function cover_crest(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-single', 50, 70, 980, 1780, ['c' => 'acc', 'op' => 95]);
        $L[] = $this->o('frame-thin', 80, 100, 920, 1720, ['c' => 'acc', 'op' => 60]);
        $L[] = $this->o('sprig-olive', 250, 420, 240, 420, ['c' => 'acc', 'op' => 95, 'rot' => -18, 'name' => 'Laurel left']);
        $L[] = $this->o('sprig-olive', 590, 420, 240, 420, ['c' => 'acc', 'op' => 95, 'rot' => 18, 'name' => 'Laurel right']);
        $L[] = $this->s(380, 400, 320, 320, ['kind' => 'ring', 'bd' => 5, 'bc' => 'acc', 'name' => 'Crest ring']);
        $L[] = $this->s(400, 420, 280, 280, ['kind' => 'ring', 'bd' => 1, 'bc' => 'acc', 'name' => 'Crest inner ring']);
        $L[] = $this->t('{{initials}}', 380, 480, 320, 160, ['f' => 'd', 's' => 110, 'c' => 'head', 'it' => true, 'name' => 'Monogram']);
        $L[] = $this->eyebrow(860, 'acc', 'BERSAMA KELUARGA KEDUA BELAH PIHAK');
        array_push($L, ...$this->names(930, ['s' => 120, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .06, 'tok' => 'short']));
        array_push($L, ...$this->divider('divider-diamond', 1390));
        array_push($L, ...$this->when(1460, ['s' => 44]));

        return $L;
    }

    protected function cover_slab(): array
    {
        $L = $this->bg();
        $L[] = $this->s(0, 0, 250, 1920, ['fill' => 'acc', 'fill2' => 'acc2', 'ang' => 180, 'name' => 'Bronze bar']);
        $L[] = $this->o('pattern-diagonal', 0, 0, 250, 1920, ['tile' => 44, 'c' => '#000000', 'op' => 18, 'name' => 'Bar texture']);
        $L[] = $this->t('{{wedding_date}}', -690, 940, 1630, 60, ['f' => 'n', 's' => 34, 'ls' => .5, 'up' => true, 'c' => 'bg', 'rot' => -90, 'wt' => 600, 'name' => 'Vertical date']);
        $L[] = $this->eyebrow(300, 'acc', 'WALIMATUL URUS', 330, 700, 'left');
        $L[] = $this->t('{{groom_short}}', 320, 380, 730, 240, ['f' => 'd', 's' => 170, 'c' => 'head', 'up' => true, 'al' => 'left', 'ls' => .03, 'lh' => 1, 'name' => 'Groom name']);
        $L[] = $this->rule(330, 650, 680, ['c' => 'acc', 'th' => 3]);
        $L[] = $this->t('{{bride_short}}', 320, 690, 730, 240, ['f' => 'd', 's' => 170, 'c' => 'head', 'up' => true, 'al' => 'left', 'ls' => .03, 'lh' => 1, 'name' => 'Bride name']);
        $L[] = $this->i('couple_image', 330, 1040, 400, 520, ['shape' => 'rect', 'r' => 0, 'bd' => 4, 'bc' => 'acc', 'sh' => 'deep']);
        $L[] = $this->t('{{time_12}}', 770, 1040, 240, 70, ['f' => 'd', 's' => 50, 'c' => 'acc', 'al' => 'left', 'name' => 'Time']);
        $L[] = $this->t('{{venue_name}}', 770, 1120, 240, 240, ['f' => 'n', 's' => 26, 'c' => 'onbg', 'al' => 'left', 'va' => 'top', 'lh' => 1.5, 'name' => 'Venue']);

        return $L;
    }

    protected function cover_garden(): array
    {
        $L = $this->bg();
        $L[] = $this->o('scatter-wildflower', 0, 0, 1080, 1080, ['c' => 'acc2', 'op' => 60, 'name' => 'Scattered blooms']);
        $L[] = $this->o('corner-blossom', -30, -30, 720, 720, ['c' => 'head', 'c2' => 'acc', 'op' => 88, 'sh' => true, 'name' => 'Blossom corner']);
        $L[] = $this->o('corner-wildflower', 380, 1240, 720, 720, ['c' => 'acc', 'op' => 88, 'rot' => 180, 'name' => 'Wildflower corner']);
        $L[] = $this->o('corner-blossom', 650, 1290, 460, 460, ['c' => 'head', 'op' => 60, 'rot' => 200]);
        $L[] = $this->eyebrow(700, 'acc');
        array_push($L, ...$this->names(760, ['s' => 160, 'f' => 's', 'c' => 'head']));
        array_push($L, ...$this->divider('divider-floral', 1210, 480));
        array_push($L, ...$this->when(1290, ['s' => 42, 'c' => 'ink']));

        return $L;
    }

    protected function cover_spray(): array
    {
        $L = $this->bg();
        $L[] = $this->o('spray-rose', 0, 30, 1080, 260, ['c' => 'head', 'c2' => 'acc', 'op' => 92, 'sh' => true, 'name' => 'Rose spray top']);
        $L[] = $this->o('spray-rose', 0, 1630, 1080, 260, ['c' => 'head', 'c2' => 'acc', 'op' => 92, 'rot' => 180, 'name' => 'Rose spray bottom']);
        $L[] = $this->o('cluster-rose', -140, 1300, 560, 560, ['c' => 'head', 'op' => 88, 'rot' => 20, 'sh' => true, 'name' => 'Rose cluster']);
        $L[] = $this->o('cluster-rose', 660, 240, 460, 460, ['c' => 'acc', 'op' => 70, 'rot' => 160, 'name' => 'Rose cluster top']);
        $L[] = $this->o('frame-thin', 60, 80, 960, 1760, ['c' => 'acc', 'op' => 45]);
        $L[] = $this->eyebrow(690, 'acc');
        array_push($L, ...$this->names(750, ['s' => 150, 'f' => 's', 'c' => 'head']));
        array_push($L, ...$this->divider('divider-floral', 1200, 460));
        array_push($L, ...$this->when(1280, ['s' => 42, 'c' => 'ink']));

        return $L;
    }

    protected function cover_bloom(): array
    {
        $L = $this->bg();
        $L[] = $this->o('cluster-peony', 60, -200, 960, 960, ['c' => 'head', 'c2' => 'acc', 'op' => 92, 'sh' => 'deep', 'name' => 'Peony cluster top']);
        $L[] = $this->o('cluster-peony', 260, 1160, 900, 900, ['c' => 'acc', 'c2' => 'head', 'op' => 88, 'rot' => 165, 'sh' => 'deep', 'name' => 'Peony cluster bottom']);
        $L[] = $this->o('scatter-wildflower', 0, 500, 1080, 1080, ['c' => 'acc', 'op' => 30, 'name' => 'Petals']);
        $L[] = $this->o('leaf-sprig', 60, 1200, 200, 300, ['c' => 'head', 'op' => 60, 'rot' => -15]);
        $L[] = $this->eyebrow(760, 'acc');
        array_push($L, ...$this->names(820, ['s' => 130, 'f' => 's', 'c' => 'ink', 'sh' => true, 'amp' => 'head']));
        array_push($L, ...$this->when(1250, ['s' => 42, 'c' => 'ink']));
        $L[] = $this->o('heart', 510, 1170, 60, 54, ['c' => 'head', 'op' => 80, 'name' => 'Heart']);

        return $L;
    }

    protected function cover_branch(): array
    {
        $L = $this->bg();
        $L[] = $this->o('corner-blossom', 260, -120, 900, 900, ['c' => '#ffffff', 'op' => 95, 'rot' => 90, 'sh' => 'deep', 'name' => 'White blossoms']);
        $L[] = $this->o('corner-blossom', -240, 1140, 900, 900, ['c' => '#ffffff', 'op' => 90, 'rot' => 270, 'sh' => 'deep', 'name' => 'White blossoms low']);
        $L[] = $this->o('leaf-sprig', 860, 1500, 160, 240, ['c' => 'acc', 'op' => 70, 'rot' => 30]);
        $L[] = $this->t('Walimatul urus', 110, 720, 860, 60, ['f' => 's', 's' => 50, 'c' => 'acc', 'al' => 'left', 'name' => 'Heading line']);
        $L[] = $this->t('{{groom_short}}', 100, 790, 880, 240, ['f' => 'd', 's' => 200, 'c' => 'head', 'al' => 'left', 'lh' => 1, 'wt' => 300, 'name' => 'Groom name']);
        $L[] = $this->t('& {{bride_short}}', 100, 1010, 880, 240, ['f' => 'd', 's' => 200, 'c' => 'head', 'al' => 'left', 'lh' => 1, 'wt' => 300, 'name' => 'Bride name']);
        $L[] = $this->rule(110, 1300, 180, ['c' => 'acc']);
        $L[] = $this->t('{{wedding_date}}', 110, 1330, 860, 60, ['f' => 'n', 's' => 30, 'ls' => .3, 'up' => true, 'c' => 'ink', 'al' => 'left', 'name' => 'Wedding date']);
        $L[] = $this->t('{{venue_name}}', 110, 1390, 860, 50, ['f' => 'n', 's' => 26, 'c' => 'mut', 'al' => 'left', 'name' => 'Venue']);

        return $L;
    }

    protected function cover_sideleaf(): array
    {
        $L = $this->bg();
        $L[] = $this->o('sprig-eucalyptus', -90, 200, 520, 890, ['c' => 'acc', 'op' => 92, 'rot' => -4, 'sh' => true, 'name' => 'Eucalyptus']);
        $L[] = $this->o('sprig-olive', -40, 880, 500, 850, ['c' => 'acc2', 'op' => 90, 'rot' => 8, 'sh' => true, 'name' => 'Olive branch']);
        $L[] = $this->o('sprig-eucalyptus', 760, 1120, 400, 690, ['c' => 'acc', 'op' => 70, 'rot' => 190, 'name' => 'Eucalyptus low']);
        $L[] = $this->o('leaf-sprig', 800, 260, 190, 280, ['c' => 'acc2', 'op' => 75, 'rot' => 20]);
        $L[] = $this->t('WALIMATUL URUS', 420, 620, 600, 50, ['f' => 'n', 's' => 26, 'ls' => .45, 'c' => 'acc', 'al' => 'left', 'wt' => 500, 'name' => 'Heading line']);
        $L[] = $this->t('{{groom_short}}\n& {{bride_short}}', 420, 690, 620, 420, ['f' => 'd', 's' => 150, 'c' => 'head', 'al' => 'left', 'lh' => 1.05, 'name' => 'Names']);
        $L[] = $this->rule(430, 1160, 200, ['c' => 'acc']);
        $L[] = $this->t('{{wedding_date}}', 420, 1190, 620, 60, ['f' => 'n', 's' => 30, 'ls' => .28, 'up' => true, 'c' => 'ink', 'al' => 'left', 'name' => 'Wedding date']);
        $L[] = $this->t('{{venue_name}}', 420, 1250, 620, 50, ['f' => 'r', 's' => 34, 'it' => true, 'c' => 'mut', 'al' => 'left', 'name' => 'Venue']);

        return $L;
    }
}
