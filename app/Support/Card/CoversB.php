<?php

namespace App\Support\Card;

/**
 * Cover compositions 26–50: floral arrangements, Islamic, minimalist and creative editorial layouts.
 */
trait CoversB
{
    protected function cover_wild(): array
    {
        $L = $this->bg();
        $L[] = $this->o('scatter-wildflower', -40, -20, 1160, 1160, ['c' => 'head', 'op' => 70, 'name' => 'Wildflowers pink']);
        $L[] = $this->o('scatter-wildflower', -80, 900, 1240, 1240, ['c' => 'acc', 'op' => 70, 'rot' => 180, 'name' => 'Wildflowers gold']);
        $L[] = $this->o('scatter-wildflower', 300, 400, 800, 800, ['c' => 'acc2', 'op' => 45, 'rot' => 90, 'name' => 'Wildflowers soft']);
        $L[] = $this->s(120, 640, 840, 640, ['fill' => 'card', 'r' => 24, 'op' => 88, 'sh' => true, 'name' => 'Paper note']);
        $L[] = $this->t('kami akan berkahwin', 160, 690, 760, 80, ['f' => 's', 's' => 58, 'c' => 'acc', 'rot' => -3, 'name' => 'Handwritten heading']);
        $L[] = $this->t('{{groom_short}}', 160, 770, 760, 190, ['f' => 'd', 's' => 150, 'c' => 'head', 'lh' => 1, 'rot' => -3, 'name' => 'Groom name']);
        $L[] = $this->t('& {{bride_short}}', 160, 940, 760, 190, ['f' => 'd', 's' => 150, 'c' => 'head', 'lh' => 1, 'rot' => -3, 'name' => 'Bride name']);
        $L[] = $this->t('{{wedding_date}}', 160, 1130, 760, 60, ['f' => 'r', 's' => 42, 'c' => 'ink', 'up' => true, 'ls' => .18, 'name' => 'Wedding date']);
        $L[] = $this->t('{{venue_name}}', 160, 1190, 760, 50, ['f' => 's', 's' => 44, 'c' => 'acc', 'name' => 'Venue']);
        $L[] = $this->o('heart', 780, 610, 70, 63, ['c' => 'head', 'rot' => 14]);
        $L[] = $this->o('heart', 220, 1230, 46, 42, ['c' => 'acc', 'rot' => -12]);

        return $L;
    }

    protected function cover_floralarch(): array
    {
        $L = $this->bg();
        $L[] = $this->s(160, 560, 760, 1000, ['kind' => 'glow', 'fill' => 'acc2', 'op' => 35, 'name' => 'Soft light']);
        $L[] = $this->i('couple_image', 290, 400, 500, 800, ['shape' => 'arch', 'sh' => 'deep', 'name' => 'Arch photo']);
        $L[] = $this->o('floral-arch-peony', 140, 200, 800, 1040, ['c' => 'head', 'c2' => 'acc', 'op' => 96, 'sh' => true, 'name' => 'Floral arch']);
        $L[] = $this->o('cluster-peony', -110, 1290, 470, 470, ['c' => 'head', 'op' => 80, 'rot' => 30, 'name' => 'Peony cluster']);
        $L[] = $this->o('cluster-peony', 720, 1290, 470, 470, ['c' => 'acc', 'op' => 70, 'rot' => -30, 'name' => 'Peony cluster right']);
        $L[] = $this->eyebrow(1250, 'acc');
        array_push($L, ...$this->names(1300, ['s' => 108, 'f' => 's', 'c' => 'head', 'x' => 200, 'w' => 680]));
        array_push($L, ...$this->when(1700, ['s' => 36, 'c' => 'ink']));

        return $L;
    }

    protected function cover_gate(): array
    {
        $L = $this->bg();
        $L[] = $this->s(0, 0, 1080, 400, ['kind' => 'fade', 'fill' => '#ffffff', 'ang' => 180, 'op' => 50, 'name' => 'Morning light']);
        $L[] = $this->i('couple_image', 260, 360, 560, 780, ['shape' => 'rect', 'r' => 8, 'sh' => 'deep', 'name' => 'Gate photo']);
        $L[] = $this->o('floral-gate', 140, 180, 800, 1040, ['c' => 'acc', 'c2' => 'head', 'op' => 95, 'sh' => true, 'name' => 'Garden gate']);
        $L[] = $this->o('scatter-wildflower', 0, 1100, 1080, 800, ['c' => 'head', 'op' => 22, 'name' => 'Grass flowers']);
        $L[] = $this->eyebrow(1270, 'acc', 'ANDA DIJEMPUT KE MAJLIS PERKAHWINAN');
        array_push($L, ...$this->names(1330, ['s' => 100, 'f' => 'd', 'c' => 'head', 'x' => 200, 'w' => 680, 'tok' => 'short']));
        array_push($L, ...$this->when(1690, ['s' => 36, 'c' => 'ink']));

        return $L;
    }

    protected function cover_bouquet(): array
    {
        $L = $this->bg();
        $L[] = $this->o('spray-rose', 0, 30, 1080, 260, ['c' => 'pri', 'op' => 70, 'name' => 'Rose spray']);
        $L[] = $this->o('cluster-rose', 140, 1080, 800, 800, ['c' => 'pri', 'c2' => 'head', 'op' => 96, 'sh' => 'deep', 'name' => 'Burgundy bouquet']);
        $L[] = $this->o('cluster-rose', -180, 1300, 520, 520, ['c' => 'pri', 'op' => 85, 'rot' => -25, 'name' => 'Rose left']);
        $L[] = $this->o('cluster-rose', 740, 1300, 520, 520, ['c' => 'pri', 'op' => 85, 'rot' => 25, 'name' => 'Rose right']);
        $L[] = $this->o('sparkle', 500, 330, 80, 80, ['c' => 'acc', 'sh' => 'glow']);
        $L[] = $this->eyebrow(430, 'acc');
        array_push($L, ...$this->names(500, ['s' => 150, 'f' => 's', 'c' => 'acc', 'sh' => true, 'amp' => 'head']));
        array_push($L, ...$this->divider('divider-floral', 940, 480));
        array_push($L, ...$this->when(1020, ['s' => 42, 'c' => 'ink']));

        return $L;
    }

    protected function cover_lavender(): array
    {
        $L = $this->bg();
        $L[] = $this->s(0, 900, 1080, 1020, ['kind' => 'fade', 'fill' => 'bg2', 'ang' => 0, 'op' => 70, 'name' => 'Twilight haze']);
        $L[] = $this->o('lavender-bunch', -70, 1010, 520, 860, ['c' => 'acc', 'op' => 95, 'rot' => -6, 'sh' => true, 'name' => 'Lavender left']);
        $L[] = $this->o('lavender-bunch', 620, 1010, 520, 860, ['c' => 'acc', 'op' => 95, 'rot' => 6, 'sh' => true, 'name' => 'Lavender right']);
        $L[] = $this->o('lavender-bunch', 300, 1180, 460, 760, ['c' => 'head', 'op' => 80, 'name' => 'Lavender centre']);
        $L[] = $this->o('particles', 0, 0, 1080, 1100, ['c' => '#ffffff', 'op' => 40, 'name' => 'Pollen']);
        $L[] = $this->s(140, 300, 800, 800, ['kind' => 'ring', 'bd' => 2, 'bc' => 'acc', 'op' => 70, 'name' => 'Dream ring']);
        $L[] = $this->eyebrow(440, 'acc');
        array_push($L, ...$this->names(500, ['s' => 130, 'f' => 's', 'c' => 'head', 'x' => 200, 'w' => 680]));
        array_push($L, ...$this->when(920, ['s' => 40, 'c' => 'ink']));

        return $L;
    }

    protected function cover_cartouche(): array
    {
        $L = $this->bg();
        $L[] = $this->s(110, 300, 860, 1320, ['fill' => 'card', 'r' => 14, 'sh' => 'deep', 'name' => 'Cartouche card']);
        $L[] = $this->o('frame-thin', 140, 330, 800, 1260, ['c' => 'acc', 'op' => 90, 'name' => 'Card frame']);
        $L[] = $this->o('medallion', 340, 360, 400, 400, ['c' => 'acc', 'c2' => 'acc2', 'op' => 95, 'sh' => 'glow']);
        $L[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 200, 800, 680, 100, ['f' => 'r', 's' => 56, 'c' => 'acc', 'name' => 'Bismillah']);
        array_push($L, ...$this->names(920, ['s' => 112, 'f' => 'd', 'c' => 'head', 'x' => 160, 'w' => 760, 'up' => true, 'ls' => .05]));
        array_push($L, ...$this->divider('divider-star', 1360, 420));
        array_push($L, ...$this->when(1430, ['s' => 40, 'c' => 'ink', 'x' => 160, 'w' => 760]));
        $L[] = $this->o('pattern-islamic', 110, 1640, 860, 130, ['tile' => 130, 'c' => 'acc', 'op' => 60, 'name' => 'Islamic band']);

        return $L;
    }

    protected function cover_mihrab(): array
    {
        $L = $this->bg();
        $L[] = $this->o('arch-pointed', 100, 200, 880, 1280, ['c' => 'acc', 'c2' => 'acc2', 'sh' => true, 'name' => 'Mihrab arch']);
        $L[] = $this->s(240, 420, 600, 900, ['kind' => 'glow', 'fill' => 'card', 'op' => 70, 'name' => 'Niche glow']);
        $L[] = $this->o('star8', 400, 300, 280, 280, ['c' => 'acc', 'op' => 95, 'sh' => 'glow', 'name' => 'Niche star']);
        $L[] = $this->o('pattern-islamic', 0, 1520, 1080, 400, ['tile' => 130, 'c' => 'acc', 'op' => 30, 'name' => 'Pattern floor']);
        $L[] = $this->eyebrow(640, 'acc', 'WALIMATUL URUS', 240, 600);
        array_push($L, ...$this->names(710, ['s' => 120, 'f' => 'd', 'c' => 'head', 'x' => 200, 'w' => 680, 'tok' => 'short']));
        array_push($L, ...$this->when(1140, ['s' => 38, 'c' => 'ink', 'x' => 200, 'w' => 680]));
        $L[] = $this->o('crescent-star', 480, 1580, 120, 120, ['c' => 'acc', 'sh' => 'glow']);

        return $L;
    }

    protected function cover_noor(): array
    {
        $L = $this->bg();
        $L[] = $this->o('star8', 360, -250, 1000, 1000, ['c' => 'acc', 'op' => 95, 'rot' => 0, 'sh' => true, 'name' => 'Noor star']);
        $L[] = $this->o('medallion', 470, -140, 780, 780, ['c' => 'acc', 'op' => 40, 'rot' => 22, 'name' => 'Noor medallion']);
        $L[] = $this->o('pattern-islamic', 0, 1330, 1080, 590, ['tile' => 130, 'c' => 'acc', 'op' => 26, 'name' => 'Pattern field']);
        $L[] = $this->o('frame-single', 50, 70, 980, 1780, ['c' => 'acc', 'op' => 40, 'name' => 'Fine frame']);
        $L[] = $this->s(560, 40, 520, 520, ['kind' => 'glow', 'fill' => 'acc2', 'op' => 40, 'name' => 'Star glow']);
        $L[] = $this->o('sparkle', 150, 760, 46, 46, ['c' => 'acc', 'op' => 80, 'name' => 'Sparkle']);
        $L[] = $this->o('divider-double', 90, 1680, 900, 36, ['c' => 'acc', 'op' => 60, 'name' => 'Double rule']);
        $L[] = $this->s(90, 1090, 90, 4, ['fill' => 'acc', 'name' => 'Accent bar']);
        $L[] = $this->t('WALIMATUL URUS', 90, 1120, 700, 50, ['f' => 'n', 's' => 26, 'ls' => .45, 'c' => 'acc', 'al' => 'left', 'wt' => 500, 'name' => 'Heading line']);
        $L[] = $this->t('{{groom_short}}\n& {{bride_short}}', 90, 1190, 900, 380, ['f' => 'd', 's' => 150, 'c' => 'head', 'al' => 'left', 'lh' => 1.05, 'wt' => 300, 'name' => 'Names']);
        $L[] = $this->t('{{wedding_date}}  ·  {{venue_name}}', 90, 1600, 900, 60, ['f' => 'n', 's' => 28, 'ls' => .18, 'up' => true, 'c' => 'ink', 'al' => 'left', 'name' => 'Date and venue']);

        return $L;
    }

    protected function cover_medal(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-ornate', 40, 60, 1000, 1800, ['c' => 'acc', 'c2' => 'acc2', 'op' => 85, 'name' => 'Gold frame']);
        $L[] = $this->o('medallion', 180, 170, 720, 720, ['c' => 'acc', 'c2' => 'acc2', 'op' => 95, 'sh' => 'glow', 'name' => 'Medallion']);
        $L[] = $this->i('couple_image', 380, 370, 320, 320, ['shape' => 'circle', 'bd' => 6, 'bc' => 'acc', 'sh' => 'deep', 'name' => 'Medallion photo']);
        $L[] = $this->eyebrow(960, 'acc', 'WALIMATUL URUS');
        array_push($L, ...$this->names(1020, ['s' => 130, 'f' => 'd', 'c' => 'head', 'up' => true, 'ls' => .05, 'tok' => 'short']));
        array_push($L, ...$this->divider('divider-star', 1440, 420));
        array_push($L, ...$this->when(1510, ['s' => 42, 'c' => 'onbg']));

        return $L;
    }

    protected function cover_crescent(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-thin', 50, 70, 980, 1780, ['c' => 'acc', 'op' => 70]);
        $L[] = $this->s(240, 160, 600, 600, ['kind' => 'glow', 'fill' => 'acc', 'op' => 40, 'name' => 'Moon glow']);
        $L[] = $this->o('crescent-star', 290, 210, 500, 500, ['c' => 'acc', 'c2' => 'acc2', 'sh' => 'glow', 'name' => 'Crescent moon']);
        $L[] = $this->o('arch-pointed', 300, 780, 480, 690, ['c' => 'acc', 'op' => 80, 'name' => 'Small mihrab']);
        $L[] = $this->eyebrow(870, 'acc', 'WALIMATUL URUS', 300, 480);
        array_push($L, ...$this->names(920, ['s' => 78, 'f' => 'd', 'c' => 'head', 'x' => 320, 'w' => 440, 'up' => true, 'ls' => .08, 'tok' => 'short']));
        $L[] = $this->o('particles', 0, 0, 1080, 1920, ['c' => 'acc2', 'op' => 45, 'name' => 'Stars']);
        array_push($L, ...$this->when(1560, ['s' => 40, 'c' => 'onbg']));

        return $L;
    }

    protected function cover_whitespace(): array
    {
        $L = $this->bg();
        $L[] = $this->g('paper', 0, 0, 1080, 1920, ['tile' => 400, 'op' => 35, 'name' => 'Paper grain']);
        $L[] = $this->o('frame-single', 60, 80, 960, 1760, ['c' => 'acc', 'op' => 28, 'name' => 'Faint frame']);
        $L[] = $this->rule(110, 200, 60, ['c' => 'acc']);
        $L[] = $this->t('N° 36', 110, 130, 300, 40, ['f' => 'n', 's' => 20, 'ls' => .4, 'c' => 'mut', 'al' => 'left', 'name' => 'Issue mark']);
        $L[] = $this->o('sprig-olive', 760, 110, 210, 350, ['c' => 'acc', 'op' => 85, 'rot' => 12, 'name' => 'Olive sprig']);
        $L[] = $this->o('leaf-sprig', 690, 210, 100, 150, ['c' => 'acc', 'op' => 50, 'rot' => -20, 'name' => 'Small sprig']);
        $L[] = $this->t('Walimatul urus', 110, 1280, 600, 50, ['f' => 'r', 's' => 32, 'it' => true, 'c' => 'mut', 'al' => 'left', 'name' => 'Heading line']);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 110, 1330, 860, 130, ['f' => 'd', 's' => 100, 'c' => 'head', 'al' => 'left', 'wt' => 300, 'name' => 'Names']);
        $L[] = $this->rule(114, 1490, 60, ['c' => 'acc']);
        $L[] = $this->t('{{wedding_date}}', 110, 1520, 800, 50, ['f' => 'n', 's' => 26, 'ls' => .3, 'up' => true, 'c' => 'ink', 'al' => 'left', 'name' => 'Wedding date']);
        $L[] = $this->t('{{venue_name}}', 110, 1575, 800, 50, ['f' => 'n', 's' => 24, 'c' => 'mut', 'al' => 'left', 'name' => 'Venue']);

        return $L;
    }

    protected function cover_oval(): array
    {
        $L = $this->bg();
        $L[] = $this->g('paper', 0, 0, 1080, 1920, ['tile' => 400, 'op' => 40, 'name' => 'Paper grain']);
        $L[] = $this->s(160, 230, 760, 980, ['kind' => 'rect', 'fill' => 'acc2', 'r' => 380, 'op' => 55, 'name' => 'Oval backdrop']);
        $L[] = $this->s(120, 190, 840, 1060, ['kind' => 'ring', 'bd' => 2, 'bc' => 'acc', 'r' => 420, 'op' => 60, 'name' => 'Oval outline']);
        $L[] = $this->i('couple_image', 240, 300, 600, 820, ['shape' => 'oval', 'sh' => 'deep', 'name' => 'Oval portrait']);
        $L[] = $this->o('sprig-eucalyptus', 60, 820, 300, 510, ['c' => 'acc', 'op' => 95, 'rot' => -12, 'sh' => true, 'name' => 'Botanical sprig']);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 1270, 900, 120, ['f' => 'd', 's' => 96, 'c' => 'head', 'name' => 'Names']);
        $L[] = $this->t('AKAN BERKAHWIN', 90, 1400, 900, 50, ['f' => 'n', 's' => 26, 'ls' => .4, 'c' => 'acc', 'name' => 'Subtitle']);
        $L[] = $this->rule(490, 1490, 100, ['c' => 'acc']);
        array_push($L, ...$this->when(1530, ['s' => 38, 'c' => 'ink']));

        return $L;
    }

    protected function cover_blackmin(): array
    {
        $L = $this->bg();
        $L[] = $this->rule(90, 100, 900, ['c' => '#ffffff', 'op' => 25]);
        $L[] = $this->t('N° 12 · 12 · 2026', 90, 120, 900, 40, ['f' => 'n', 's' => 20, 'ls' => .5, 'c' => 'mut', 'al' => 'left']);
        $L[] = $this->s(520, 700, 40, 40, ['kind' => 'circle', 'fill' => 'acc', 'name' => 'Gold accent']);
        $L[] = $this->t('{{groom_short}}', 90, 820, 900, 130, ['f' => 'd', 's' => 96, 'c' => 'head', 'up' => true, 'ls' => .5, 'wt' => 300, 'name' => 'Groom name']);
        $L[] = $this->t('dan', 90, 950, 900, 60, ['f' => 'r', 's' => 40, 'it' => true, 'c' => 'acc', 'name' => 'Conjunction']);
        $L[] = $this->t('{{bride_short}}', 90, 1010, 900, 130, ['f' => 'd', 's' => 96, 'c' => 'head', 'up' => true, 'ls' => .5, 'wt' => 300, 'name' => 'Bride name']);
        $L[] = $this->rule(90, 1620, 900, ['c' => '#ffffff', 'op' => 25]);
        $L[] = $this->t('{{wedding_date}}', 90, 1650, 600, 40, ['f' => 'n', 's' => 22, 'ls' => .4, 'up' => true, 'c' => 'onbg', 'al' => 'left', 'name' => 'Wedding date']);
        $L[] = $this->t('{{venue_name}}', 400, 1650, 590, 40, ['f' => 'n', 's' => 22, 'ls' => .4, 'up' => true, 'c' => 'mut', 'al' => 'right', 'name' => 'Venue']);

        return $L;
    }

    protected function cover_card(): array
    {
        $L = $this->bg();
        $L[] = $this->o('pattern-lines', 0, 0, 1080, 1920, ['tile' => 40, 'c' => '#ffffff', 'op' => 40, 'name' => 'Fine lines']);
        $L[] = $this->s(120, 260, 840, 1400, ['fill' => 'card', 'r' => 4, 'sh' => 'deep', 'name' => 'Floating card']);
        $L[] = $this->o('frame-thin', 150, 290, 780, 1340, ['c' => 'acc', 'op' => 80, 'name' => 'Card line frame']);
        $L[] = $this->t('SIMPAN TARIKH', 120, 420, 840, 50, ['f' => 'n', 's' => 26, 'ls' => .55, 'c' => 'acc', 'wt' => 500, 'name' => 'Heading line']);
        array_push($L, ...$this->names(520, ['s' => 130, 'f' => 'd', 'c' => 'head', 'x' => 150, 'w' => 780, 'wt' => 400, 'amp' => 'acc']));
        $L[] = $this->rule(440, 1080, 200, ['c' => 'acc']);
        $L[] = $this->t('{{wedding_date}}', 150, 1120, 780, 70, ['f' => 'r', 's' => 48, 'c' => 'ink', 'ls' => .12, 'up' => true, 'name' => 'Wedding date']);
        $L[] = $this->t('{{time_12}}', 150, 1200, 780, 50, ['f' => 'n', 's' => 28, 'c' => 'mut', 'ls' => .3, 'name' => 'Time']);
        $L[] = $this->t('{{venue_name}}\n{{venue_address}}', 180, 1290, 720, 190, ['f' => 'n', 's' => 26, 'c' => 'mut', 'lh' => 1.6, 'va' => 'top', 'name' => 'Venue']);

        return $L;
    }

    protected function cover_monogram(): array
    {
        $L = $this->bg();
        $L[] = $this->t('{{groom_initial}}', -60, 160, 760, 900, ['f' => 'd', 's' => 760, 'c' => 'acc', 'op' => 16, 'lh' => 1, 'name' => 'Monogram letter A']);
        $L[] = $this->t('{{bride_initial}}', 380, 800, 760, 900, ['f' => 'd', 's' => 760, 'c' => 'acc', 'op' => 16, 'lh' => 1, 'it' => true, 'name' => 'Monogram letter B']);
        $L[] = $this->o('frame-single', 60, 80, 960, 1760, ['c' => 'acc', 'op' => 60]);
        $L[] = $this->eyebrow(700, 'acc');
        array_push($L, ...$this->names(760, ['s' => 130, 'f' => 'd', 'c' => 'head', 'wt' => 400]));
        $L[] = $this->rule(440, 1220, 200, ['c' => 'acc']);
        array_push($L, ...$this->when(1270, ['s' => 40, 'c' => 'ink']));
        $L[] = $this->o('sparkle', 510, 1580, 60, 60, ['c' => 'acc']);

        return $L;
    }

    protected function cover_magazine(): array
    {
        $L = [$this->i('cover_image', 0, 0, 1080, 1920, ['shape' => 'rect', 'r' => 0, 'name' => 'Cover photo'])];
        $L[] = $this->s(0, 0, 1080, 520, ['kind' => 'fade', 'fill' => '#000000', 'ang' => 180, 'op' => 55, 'name' => 'Masthead shade']);
        $L[] = $this->s(0, 1000, 1080, 920, ['kind' => 'fade', 'fill' => '#000000', 'ang' => 0, 'op' => 78, 'name' => 'Headline shade']);
        $L[] = $this->t('NEEKAH', 40, 80, 1000, 220, ['f' => 'd', 's' => 190, 'c' => '#ffffff', 'up' => true, 'ls' => .02, 'wt' => 700, 'lh' => 1, 'name' => 'Masthead']);
        $L[] = $this->t('EDISI PERKAHWINAN · {{wedding_date}}', 70, 300, 940, 40, ['f' => 'n', 's' => 22, 'ls' => .35, 'c' => '#ffffff', 'op' => 90, 'name' => 'Issue line']);
        $L[] = $this->t('{{groom_short}}\n& {{bride_short}}', 60, 1120, 960, 400, ['f' => 'd', 's' => 150, 'c' => '#ffffff', 'al' => 'left', 'lh' => 1, 'sh' => 'deep', 'name' => 'Headline names']);
        $L[] = $this->s(70, 1540, 8, 150, ['fill' => 'acc', 'name' => 'Accent rule']);
        $L[] = $this->t('Kisah cinta yang\nmenghentikan dunia.', 100, 1540, 600, 160, ['f' => 'r', 's' => 40, 'it' => true, 'c' => '#ffffff', 'al' => 'left', 'va' => 'top', 'name' => 'Cover line']);
        $L[] = $this->t('{{venue_name}}', 640, 1560, 370, 60, ['f' => 'n', 's' => 24, 'ls' => .3, 'up' => true, 'c' => '#ffffff', 'al' => 'right', 'name' => 'Venue']);
        foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8] as $b) {
            $L[] = $this->s(884 + $b * 14, 1640, 3 + ($b % 3), 70, ['fill' => '#ffffff', 'name' => 'Barcode '.($b + 1), 'op' => 90]);
        }

        return $L;
    }

    protected function cover_cinema(): array
    {
        $L = [$this->i('cover_image', 0, 0, 1080, 1920, ['shape' => 'rect', 'r' => 0, 'name' => 'Film still'])];
        $L[] = $this->s(0, 0, 1080, 1920, ['fill' => '#0a0908', 'op' => 52, 'name' => 'Dark overlay']);
        $L[] = $this->s(0, 500, 1080, 900, ['kind' => 'glow', 'fill' => 'acc', 'op' => 22, 'name' => 'Warm light leak', 'bl' => 'screen']);
        $L[] = $this->g('grain', 0, 0, 1080, 1920, ['tile' => 300, 'op' => 42, 'bl' => 'screen', 'name' => 'Film grain']);
        $L[] = $this->s(0, 0, 1080, 200, ['fill' => '#000000', 'name' => 'Letterbox top']);
        $L[] = $this->s(0, 1720, 1080, 200, ['fill' => '#000000', 'name' => 'Letterbox bottom']);
        $L[] = $this->t('● REC', 70, 90, 300, 40, ['f' => 'n', 's' => 24, 'ls' => .3, 'c' => '#e5484d', 'al' => 'left', 'name' => 'Rec marker']);
        $L[] = $this->t('{{wedding_date}}', 610, 90, 400, 40, ['f' => 'n', 's' => 24, 'ls' => .3, 'c' => '#ffffff', 'al' => 'right', 'up' => true, 'name' => 'Timecode']);
        $L[] = $this->eyebrow(760, 'acc2', 'SEBUAH FILEM PERKAHWINAN');
        array_push($L, ...$this->names(830, ['s' => 130, 'f' => 'd', 'c' => '#ffffff', 'up' => true, 'ls' => .08, 'wt' => 400, 'sh' => 'deep', 'amp' => 'acc2', 'tok' => 'short']));
        $L[] = $this->rule(390, 1290, 300, ['c' => '#ffffff', 'op' => 60]);
        array_push($L, ...$this->when(1330, ['s' => 38, 'c' => '#ffffff']));
        $L[] = $this->t('{{venue_address}}', 70, 1740, 940, 40, ['f' => 'n', 's' => 20, 'ls' => .3, 'c' => '#ffffff', 'op' => 70, 'name' => 'Location line']);

        return $L;
    }

    protected function cover_polaroid(): array
    {
        $L = $this->bg();
        $L[] = $this->s(0, 0, 1080, 1920, ['kind' => 'glow', 'fill' => '#ffffff', 'op' => 40, 'name' => 'Light']);
        foreach ([['couple_image', 90, 150, -6, 'photo-couple'], ['groom_image', 520, 330, 5, 'photo-portrait'], ['bride_image', 200, 820, -3, 'photo-portrait']] as $k => [$bind, $x, $y, $rot, $ph]) {
            $L[] = $this->s($x, $y, 470, 570, ['fill' => '#fdfcf8', 'r' => 4, 'rot' => $rot, 'sh' => 'deep', 'name' => 'Polaroid '.($k + 1)]);
            $L[] = $this->i($bind, $x + 28, $y + 28, 414, 414, ['shape' => 'rect', 'r' => 0, 'rot' => $rot, 'ph' => $ph, 'name' => 'Polaroid photo '.($k + 1)]);
            $L[] = $this->o('tape', $x + 130, $y - 30, 210, 66, ['c' => 'acc2', 'op' => 85, 'rot' => $rot + ($k % 2 ? 6 : -8), 'name' => 'Tape '.($k + 1)]);
        }
        $L[] = $this->t('selamanya bersama', 230, 1290, 420, 60, ['f' => 's', 's' => 52, 'c' => 'ink', 'rot' => -3, 'name' => 'Polaroid caption']);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 1510, 900, 130, ['f' => 's', 's' => 108, 'c' => 'head', 'rot' => -2, 'name' => 'Names']);
        $L[] = $this->t('{{wedding_date}} · {{venue_name}}', 90, 1640, 900, 60, ['f' => 'n', 's' => 28, 'ls' => .2, 'c' => 'ink', 'up' => true, 'name' => 'Date and venue']);
        $L[] = $this->o('heart', 850, 1180, 90, 81, ['c' => 'head', 'rot' => 12, 'name' => 'Heart']);
        $L[] = $this->o('heart', 780, 1290, 44, 40, ['c' => 'acc', 'rot' => -10, 'name' => 'Small heart']);

        return $L;
    }

    protected function cover_scrapbook(): array
    {
        $L = $this->bg();
        $L[] = $this->s(70, 110, 940, 1500, ['fill' => 'card', 'r' => 2, 'rot' => -1.5, 'sh' => 'deep', 'name' => 'Paper sheet']);
        $L[] = $this->g('torn-edge', 70, 1560, 940, 60, ['name' => 'Torn edge', 'op' => 60]);
        $L[] = $this->s(130, 300, 500, 640, ['fill' => '#ffffff', 'rot' => 4, 'sh' => 'deep', 'name' => 'Photo mount']);
        $L[] = $this->i('couple_image', 155, 325, 450, 520, ['shape' => 'rect', 'r' => 0, 'rot' => 4, 'name' => 'Main photo']);
        $L[] = $this->i('groom_image', 610, 260, 320, 380, ['shape' => 'rect', 'r' => 0, 'bd' => 12, 'bc' => '#ffffff', 'rot' => -7, 'sh' => 'deep', 'ph' => 'photo-portrait', 'name' => 'Small photo']);
        $L[] = $this->o('tape', 170, 270, 220, 66, ['c' => 'acc2', 'op' => 88, 'rot' => -18, 'name' => 'Washi tape']);
        $L[] = $this->o('tape', 660, 240, 200, 60, ['c' => 'head', 'op' => 80, 'rot' => 12, 'name' => 'Washi tape red']);
        $L[] = $this->o('corner-blossom', 620, 500, 480, 480, ['c' => 'head', 'op' => 88, 'rot' => 90, 'sh' => true, 'name' => 'Pressed flowers']);
        $L[] = $this->o('corner-wildflower', -100, 1000, 520, 520, ['c' => 'acc', 'op' => 88, 'rot' => 270, 'sh' => true, 'name' => 'Pressed wildflowers']);
        $L[] = $this->t('kisah kami', 130, 1010, 820, 80, ['f' => 's', 's' => 66, 'c' => 'head', 'rot' => -2, 'name' => 'Handwritten heading']);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 130, 1110, 820, 190, ['f' => 's', 's' => 150, 'c' => 'ink', 'rot' => -2, 'name' => 'Names']);
        $L[] = $this->t('{{wedding_date}} · {{venue_name}}', 130, 1330, 820, 60, ['f' => 'n', 's' => 28, 'ls' => .2, 'c' => 'ink', 'up' => true, 'name' => 'Date and venue']);
        $L[] = $this->o('heart', 840, 1150, 70, 63, ['c' => 'head', 'rot' => 14, 'name' => 'Heart']);

        return $L;
    }

    protected function cover_letter(): array
    {
        $L = $this->bg();
        $L[] = $this->s(0, 0, 1080, 1920, ['kind' => 'glow', 'fill' => '#ffffff', 'op' => 40, 'name' => 'Aged light']);
        $L[] = $this->o('frame-single', 70, 90, 940, 1740, ['c' => 'acc', 'op' => 80, 'name' => 'Letter border']);
        $L[] = $this->o('corner-rose', -40, -20, 460, 460, ['c' => 'head', 'op' => 60, 'sh' => true, 'name' => 'Faded roses']);
        $L[] = $this->o('corner-rose', 660, 1480, 460, 460, ['c' => 'head', 'op' => 60, 'rot' => 180, 'name' => 'Faded roses low']);
        $L[] = $this->t('Sahabat yang dikasihi,', 150, 420, 780, 70, ['f' => 'n', 's' => 40, 'c' => 'ink', 'al' => 'left', 'name' => 'Salutation']);
        $L[] = $this->t('dengan hormatnya menjemput tuan/puan ke majlis perkahwinan', 150, 500, 780, 130, ['f' => 'n', 's' => 34, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'lh' => 1.5, 'op' => 88, 'name' => 'Invitation line']);
        array_push($L, ...$this->names(660, ['s' => 130, 'f' => 's', 'c' => 'head', 'x' => 120, 'w' => 840]));
        array_push($L, ...$this->when(1120, ['s' => 38, 'c' => 'ink', 'f' => 'n']));
        $L[] = $this->o('wax-seal', 420, 1400, 240, 240, ['c' => 'head', 'c2' => 'acc', 'ang' => 150, 'sh' => 'deep', 'name' => 'Wax seal']);
        $L[] = $this->o('seal-emboss', 420, 1400, 240, 240, ['c' => 'acc', 'op' => 80, 'name' => 'Seal emboss']);
        $L[] = $this->s(830, 210, 160, 160, ['kind' => 'ring', 'bd' => 3, 'bc' => 'head', 'op' => 55, 'rot' => -12, 'name' => 'Postmark ring']);
        $L[] = $this->t('POS\n{{wedding_date}}', 830, 250, 160, 90, ['f' => 'n', 's' => 15, 'c' => 'head', 'op' => 60, 'rot' => -12, 'ls' => .15, 'name' => 'Postmark']);

        return $L;
    }

    protected function cover_newspaper(): array
    {
        $L = $this->bg();
        $L[] = $this->rule(60, 90, 960, ['c' => 'ink', 'th' => 6, 'op' => 100]);
        $L[] = $this->rule(60, 106, 960, ['c' => 'ink', 'th' => 2, 'op' => 100]);
        $L[] = $this->t('UTUSAN NEEKAH', 40, 130, 1000, 170, ['f' => 'd', 's' => 88, 'c' => 'ink', 'ls' => .01, 'name' => 'Masthead']);
        $L[] = $this->rule(60, 320, 960, ['c' => 'ink', 'th' => 2, 'op' => 100]);
        $L[] = $this->t('JIL. I · NO. 1', 60, 336, 300, 36, ['f' => 'n', 's' => 20, 'ls' => .2, 'c' => 'ink', 'al' => 'left']);
        $L[] = $this->t('{{wedding_date}}', 380, 336, 320, 36, ['f' => 'n', 's' => 20, 'ls' => .2, 'c' => 'ink', 'up' => true]);
        $L[] = $this->t('PERCUMA · TIDAK TERHARGA', 720, 336, 300, 36, ['f' => 'n', 's' => 20, 'ls' => .2, 'c' => 'ink', 'al' => 'right']);
        $L[] = $this->rule(60, 382, 960, ['c' => 'ink', 'th' => 6, 'op' => 100]);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}\nBERSATU', 40, 420, 1000, 400, ['f' => 'd', 's' => 104, 'c' => 'ink', 'lh' => 1.05, 'name' => 'Headline']);
        $L[] = $this->rule(60, 850, 960, ['c' => 'ink', 'th' => 2, 'op' => 100]);
        $L[] = $this->i('couple_image', 60, 890, 560, 620, ['shape' => 'rect', 'r' => 0, 'bd' => 3, 'bc' => 'ink', 'name' => 'Press photo']);
        $L[] = $this->t('Pasangan bahagia, sebelum hari yang dinanti.', 60, 1520, 560, 70, ['f' => 'n', 's' => 20, 'it' => true, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'name' => 'Photo caption']);
        $L[] = $this->rule(650, 890, 700, ['v' => true, 'c' => 'ink', 'th' => 3]);
        $L[] = $this->t('{{wedding_message}}', 660, 890, 360, 520, ['f' => 'r', 's' => 27, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'lh' => 1.45, 'name' => 'Article text']);
        $L[] = $this->t('TARIKH  {{wedding_date}}\nMASA  {{time_12}}\nTEMPAT  {{venue_name}}', 660, 1430, 360, 170, ['f' => 'n', 's' => 22, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'lh' => 1.6, 'wt' => 600, 'name' => 'Event details']);
        $L[] = $this->rule(60, 1640, 960, ['c' => 'ink', 'th' => 6, 'op' => 100]);

        return $L;
    }

    protected function cover_postcard(): array
    {
        $L = $this->bg();
        $L[] = $this->o('frame-postcard', 40, 60, 1000, 1800, ['c' => 'head', 'op' => 80, 'name' => 'Postcard border']);
        $L[] = $this->o('stamp', 780, 130, 210, 266, ['c' => 'head', 'op' => 85, 'rot' => 4, 'name' => 'Postage stamp']);
        $L[] = $this->o('heart', 830, 200, 110, 99, ['c' => 'acc', 'rot' => 4, 'name' => 'Stamp heart']);
        $L[] = $this->s(560, 400, 190, 190, ['kind' => 'ring', 'bd' => 3, 'bc' => 'head', 'op' => 60, 'rot' => -15, 'name' => 'Postmark']);
        $L[] = $this->o('divider-wave', 560, 470, 440, 30, ['c' => 'head', 'op' => 60, 'rot' => -15, 'name' => 'Postmark waves']);
        $L[] = $this->t('SALAM\nKASIH', 90, 170, 660, 300, ['f' => 'd', 's' => 92, 'c' => 'head', 'al' => 'left', 'lh' => 1, 'wt' => 700, 'name' => 'Postcard heading']);
        $L[] = $this->i('couple_image', 90, 560, 560, 720, ['shape' => 'rect', 'r' => 0, 'bd' => 14, 'bc' => '#ffffff', 'rot' => -3, 'sh' => 'deep', 'name' => 'Postcard photo']);
        $L[] = $this->t('Sahabat yang dikasihi,\nkami akan berkahwin! Hadirlah bersama kami pada hari bahagia ini.', 700, 640, 330, 330, ['f' => 's', 's' => 46, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'lh' => 1.25, 'name' => 'Handwritten note']);
        foreach ([1350, 1440, 1530] as $y) {
            $L[] = $this->rule(90, $y, 900, ['c' => 'head', 'op' => 45, 'th' => 2]);
        }
        $L[] = $this->t('{{groom_name}} & {{bride_name}}', 90, 1300, 900, 60, ['f' => 's', 's' => 56, 'c' => 'head', 'al' => 'left', 'name' => 'Names']);
        $L[] = $this->t('{{wedding_date}} · {{time_12}}', 90, 1388, 900, 60, ['f' => 's', 's' => 50, 'c' => 'ink', 'al' => 'left', 'name' => 'Date and time']);
        $L[] = $this->t('{{venue_name}}, {{venue_address}}', 90, 1478, 900, 60, ['f' => 's', 's' => 46, 'c' => 'ink', 'al' => 'left', 'name' => 'Venue']);

        return $L;
    }

    protected function cover_filmstrip(): array
    {
        $L = $this->bg();
        foreach ([['y' => 210, 'binds' => ['cover_image', 'couple_image', 'groom_image']], ['y' => 780, 'binds' => ['bride_image', 'closing_image', 'couple_image']]] as $s => $strip) {
            $y = $strip['y'];
            $L[] = $this->s(0, $y, 1080, 520, ['fill' => '#0c0b0a', 'sh' => 'deep', 'rot' => $s ? 1.5 : -1.5, 'name' => 'Film strip '.($s + 1)]);
            $L[] = $this->o('film-holes', 0, $y + 12, 1080, 40, ['tile' => 40, 'c' => '#e9e4d8', 'op' => 85, 'rot' => $s ? 1.5 : -1.5, 'name' => 'Sprocket holes top']);
            $L[] = $this->o('film-holes', 0, $y + 468, 1080, 40, ['tile' => 40, 'c' => '#e9e4d8', 'op' => 85, 'rot' => $s ? 1.5 : -1.5, 'name' => 'Sprocket holes bottom']);
            foreach ($strip['binds'] as $k => $bind) {
                $L[] = $this->i($bind, 34 + $k * 344, $y + 70, 320, 380, ['shape' => 'rect', 'r' => 4, 'rot' => $s ? 1.5 : -1.5, 'ph' => in_array($bind, ['couple_image', 'cover_image'], true) ? 'photo-couple' : 'photo-portrait', 'name' => 'Frame '.($s * 3 + $k + 1)]);
            }
        }
        $L[] = $this->eyebrow(1400, 'acc', 'SEBUAH KISAH CINTA');
        array_push($L, ...$this->names(1450, ['s' => 100, 'f' => 'd', 'c' => 'head', 'x' => 90, 'w' => 900, 'amp' => 'acc', 'ampScale' => .35]));
        array_push($L, ...$this->when(1690, ['s' => 34, 'c' => 'onbg']));
        $L[] = $this->g('grain', 0, 0, 1080, 1920, ['tile' => 300, 'op' => 30, 'bl' => 'screen', 'name' => 'Film grain']);

        return $L;
    }

    protected function cover_abstract(): array
    {
        $L = $this->bg();
        $L[] = $this->s(-180, -140, 760, 760, ['kind' => 'circle', 'fill' => 'acc2', 'op' => 85, 'name' => 'Sand circle']);
        $L[] = $this->o('arcs', 320, 0, 760, 760, ['c' => 'acc', 'op' => 80, 'rot' => 90, 'name' => 'Golden arcs']);
        $L[] = $this->s(540, 700, 420, 620, ['fill' => 'head', 'r' => 210, 'op' => 92, 'name' => 'Terracotta pill']);
        $L[] = $this->i('couple_image', 590, 750, 320, 520, ['shape' => 'arch', 'sh' => true, 'name' => 'Arch photo']);
        $L[] = $this->s(120, 960, 240, 240, ['kind' => 'circle', 'fill' => 'acc', 'op' => 90, 'name' => 'Gold circle']);
        $L[] = $this->s(90, 1300, 900, 3, ['fill' => 'ink', 'op' => 60, 'name' => 'Baseline']);
        $L[] = $this->t('{{groom_short}}\n& {{bride_short}}', 90, 1340, 640, 300, ['f' => 'd', 's' => 120, 'c' => 'head', 'al' => 'left', 'lh' => 1, 'wt' => 400, 'name' => 'Names']);
        $L[] = $this->t('{{wedding_date}}\n{{venue_name}}', 90, 1660, 700, 110, ['f' => 'n', 's' => 26, 'ls' => .18, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'up' => true, 'lh' => 1.6, 'name' => 'Date and venue']);
        $L[] = $this->s(900, 1620, 90, 90, ['kind' => 'ring', 'bd' => 4, 'bc' => 'head', 'name' => 'Ring accent']);

        return $L;
    }

    protected function cover_signature(): array
    {
        $L = $this->bg();
        $L[] = $this->s(60, 100, 960, 1720, ['fill' => 'card', 'r' => 6, 'op' => 55, 'name' => 'Paper sheet']);
        $L[] = $this->g('paper', 0, 0, 1080, 1920, ['tile' => 400, 'op' => 55, 'bl' => 'multiply', 'name' => 'Paper texture']);
        $L[] = $this->s(90, 380, 900, 900, ['kind' => 'glow', 'fill' => 'acc2', 'op' => 45, 'name' => 'Golden light']);
        $L[] = $this->o('frame-thin', 60, 80, 960, 1760, ['c' => 'acc', 'c2' => 'acc2', 'op' => 90, 'sh' => true, 'name' => 'Gold frame']);
        $L[] = $this->o('spray-rose', 0, 20, 1080, 260, ['c' => 'head', 'c2' => 'acc', 'op' => 80, 'sh' => true, 'name' => 'Rose spray']);
        $L[] = $this->o('corner-peony', -60, -40, 700, 700, ['c' => 'head', 'c2' => 'acc', 'op' => 92, 'sh' => 'deep', 'name' => 'Peony corner']);
        $L[] = $this->o('corner-peony', 680, 1470, 420, 420, ['c' => 'head', 'c2' => 'acc', 'op' => 92, 'rot' => 180, 'sh' => 'deep', 'name' => 'Peony corner low']);
        $L[] = $this->o('arch-frame', 250, 330, 580, 773, ['c' => 'acc', 'c2' => 'acc2', 'sh' => 'glow', 'name' => 'Arch outline']);
        $L[] = $this->i('cover_image', 300, 383, 480, 668, ['shape' => 'arch', 'sh' => 'deep', 'name' => 'Arch portrait']);
        $L[] = $this->o('cluster-rose', 100, 830, 380, 380, ['c' => 'head', 'op' => 88, 'rot' => -20, 'sh' => 'deep', 'name' => 'Bouquet left']);
        $L[] = $this->o('cluster-peony', 620, 850, 360, 360, ['c' => 'acc', 'c2' => 'head', 'op' => 90, 'rot' => 20, 'sh' => 'deep', 'name' => 'Bouquet right']);
        $L[] = $this->o('particles', 0, 0, 1080, 1920, ['c' => 'acc2', 'op' => 50, 'name' => 'Gold dust']);
        $L[] = $this->o('sparkle', 890, 300, 60, 60, ['c' => 'acc2', 'sh' => 'glow']);
        $L[] = $this->eyebrow(1200, 'acc', 'WALIMATUL URUS');
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 1260, 900, 170, ['f' => 's', 's' => 118, 'c' => 'head', 'sh' => true, 'name' => 'Couple names']);
        array_push($L, ...$this->divider('divider-floral', 1470, 500));
        array_push($L, ...$this->when(1550, ['s' => 38, 'c' => 'ink']));
        $L[] = $this->t('KOLEKSI SIGNATURE NEEKAH', 90, 1806, 900, 30, ['f' => 'n', 's' => 16, 'ls' => .5, 'c' => 'acc', 'name' => 'Collection mark']);

        return $L;
    }
}
