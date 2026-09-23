<?php

namespace App\Support\Card;

trait Artwork
{
    /**
     * @return array<string, mixed>
     */
    protected function artworkImage(string $asset, string $name, bool $motion = false, bool $widgetBackground = false): array
    {
        return $this->L('image', $name, [
            'src' => '/img/layers/ekad-'.$asset.'.webp',
            'x' => 0, 'y' => 0, 'w' => self::WIDTH, 'h' => self::HEIGHT,
            'shape' => 'rect', 'fit' => 'cover', 'motion' => $motion, 'widgetBackground' => $widgetBackground,
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_artwork(): array
    {
        $layers = [$this->artworkImage($this->d['artwork'], 'Illustrated background', widgetBackground: true)];
        $variant = $this->d['artworkLayout'];

        switch ($variant) {
            case 1:
                $layers[] = $this->eyebrow(490);
                array_push($layers, ...$this->names(615, ['s' => 145, 'f' => 'd', 'c' => 'head']));
                array_push($layers, ...$this->divider('divider-diamond', 1160, 420));
                array_push($layers, ...$this->when(1280, ['s' => 44]));
                break;
            case 2:
                $layers[] = $this->t('Dengan penuh kesyukuran', 170, 520, 740, 70, ['f' => 'r', 's' => 43, 'c' => 'onbg', 'it' => true]);
                array_push($layers, ...$this->names(635, ['s' => 160, 'f' => 's', 'c' => 'head']));
                $layers[] = $this->rule(370, 1175, 340);
                array_push($layers, ...$this->when(1250, ['s' => 41]));
                break;
            case 3:
                $layers[] = $this->eyebrow(510, 'acc', 'UNDANGAN PERKAHWINAN', 150, 780, 'left');
                $layers[] = $this->rule(150, 600, 600);
                array_push($layers, ...$this->names(670, ['s' => 133, 'f' => 'd', 'c' => 'head', 'x' => 150, 'w' => 780, 'al' => 'left']));
                array_push($layers, ...$this->when(1270, ['s' => 40, 'x' => 150, 'w' => 780, 'al' => 'left']));
                break;
            case 4:
                $layers[] = $this->eyebrow(590, 'onbg', 'WALIMATUL URUS');
                $layers[] = $this->t('{{groom_short}}  &  {{bride_short}}', 120, 790, 840, 270, ['f' => 'd', 's' => 98, 'c' => 'head', 'wt' => 400, 'lh' => 1.1]);
                $layers[] = $this->rule(390, 1125, 300);
                array_push($layers, ...$this->when(1210, ['s' => 40]));
                break;
            case 5:
                $layers[] = $this->s(230, 500, 620, 620, ['kind' => 'ring', 'fill' => 'acc', 'bd' => 2, 'bc' => 'acc', 'op' => 60, 'name' => 'Gold medallion']);
                $layers[] = $this->eyebrow(575);
                array_push($layers, ...$this->names(690, ['s' => 115, 'f' => 'd', 'c' => 'head', 'x' => 225, 'w' => 630]));
                array_push($layers, ...$this->when(1280, ['s' => 43]));
                break;
            case 6:
                $layers[] = $this->t('Bismillahirrahmanirrahim', 160, 475, 760, 70, ['f' => 'r', 's' => 45, 'c' => 'acc', 'it' => true]);
                $layers[] = $this->eyebrow(615, 'onbg', 'JEMPUTAN ISTIMEWA');
                array_push($layers, ...$this->names(715, ['s' => 125, 'f' => 'd', 'c' => 'head']));
                array_push($layers, ...$this->divider('divider-floral', 1190, 460));
                array_push($layers, ...$this->when(1300, ['s' => 40]));
                break;
            case 7:
                $layers[] = $this->eyebrow(540, 'acc', 'SEBUAH KISAH CINTA');
                $layers[] = $this->t('{{groom_short}}', 120, 690, 390, 200, ['f' => 'd', 's' => 104, 'c' => 'head']);
                $layers[] = $this->t('&', 490, 700, 100, 190, ['f' => 's', 's' => 108, 'c' => 'acc']);
                $layers[] = $this->t('{{bride_short}}', 570, 690, 390, 200, ['f' => 'd', 's' => 104, 'c' => 'head']);
                $layers[] = $this->rule(390, 1080, 300);
                array_push($layers, ...$this->when(1190, ['s' => 43]));
                break;
            case 8:
                $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 170, 480, 740, 120, ['f' => 'r', 's' => 60, 'c' => 'acc']);
                $layers[] = $this->eyebrow(650);
                array_push($layers, ...$this->names(750, ['s' => 115, 'f' => 'd', 'c' => 'head']));
                array_push($layers, ...$this->when(1280, ['s' => 40]));
                break;
            case 9:
                $layers[] = $this->t('&', 200, 510, 680, 660, ['f' => 's', 's' => 400, 'c' => 'acc2', 'op' => 33]);
                $layers[] = $this->eyebrow(610);
                array_push($layers, ...$this->names(720, ['s' => 130, 'f' => 'd', 'c' => 'head']));
                array_push($layers, ...$this->when(1290, ['s' => 41]));
                break;
            default:
                $layers[] = $this->t('Dan di antara tanda kasih-Nya', 170, 500, 740, 100, ['f' => 'r', 's' => 42, 'c' => 'onbg', 'it' => true]);
                $layers[] = $this->rule(410, 640, 260);
                array_push($layers, ...$this->names(720, ['s' => 140, 'f' => 's', 'c' => 'head']));
                $layers[] = $this->eyebrow(1220, 'acc', 'WALIMATUL URUS');
                array_push($layers, ...$this->when(1300, ['s' => 38]));
        }

        $layers[] = $this->artworkImage('floral-frame', 'Flower and gold frame', true);

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_artwork(): array
    {
        $layers = [$this->artworkImage($this->d['artwork'], 'Illustrated background', widgetBackground: true)];
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 170, 280, 740, 100, ['f' => 'r', 's' => 55, 'c' => 'acc']);
        $layers[] = $this->eyebrow(490, 'pri', 'JEMPUTAN PERKAHWINAN');
        array_push($layers, ...$this->names(610, ['s' => 115, 'f' => 'd', 'c' => 'pri']));
        array_push($layers, ...$this->divider('divider-diamond', 1080, 400));
        $layers[] = $this->t('{{wedding_message}}', 140, 1210, 800, 370, ['f' => 'r', 's' => 41, 'c' => 'ink', 'lh' => 1.5, 'va' => 'top', 'name' => 'Invitation message']);

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_artwork(): array
    {
        $layers = [$this->artworkImage($this->d['artwork'], 'Illustrated background', widgetBackground: true)];
        $layers[] = $this->eyebrow(400, 'pri', 'BUTIRAN MAJLIS');
        $layers[] = $this->t('Majlis Perkahwinan', 110, 530, 860, 170, ['f' => 'd', 's' => 90, 'c' => 'pri']);
        array_push($layers, ...$this->divider('divider-diamond', 755, 400));
        $layers[] = $this->t('{{date_full}}', 130, 900, 820, 130, ['f' => 'r', 's' => 59, 'c' => 'ink']);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 150, 1060, 780, 80, ['f' => 'n', 's' => 35, 'c' => 'acc', 'ls' => .15]);
        $layers[] = $this->t('{{venue_name}}', 130, 1220, 820, 100, ['f' => 'd', 's' => 65, 'c' => 'pri']);
        $layers[] = $this->t('{{venue_address}}', 170, 1350, 740, 230, ['f' => 'r', 's' => 39, 'c' => 'ink', 'lh' => 1.45, 'va' => 'top']);

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_mihrab_bloom(): array
    {
        $layers = [$this->artworkImage('mihrab-bloom', 'Ivory mihrab and mauve flowers', true, true)];
        $layers[] = $this->eyebrow(580, 'acc', 'WALIMATUL URUS');
        array_push($layers, ...$this->names(720, ['s' => 140, 'f' => 'd', 'c' => 'head']));
        $layers[] = $this->rule(400, 1190, 280, ['op' => 55]);
        array_push($layers, ...$this->when(1280, ['s' => 40, 'c' => 'head']));

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_mihrab_bloom(): array
    {
        $layers = [$this->artworkImage('mihrab-bloom', 'Ivory mihrab and mauve flowers', true, true)];
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 170, 470, 740, 100, ['f' => 'r', 's' => 54, 'c' => 'acc']);
        $layers[] = $this->eyebrow(610, 'pri', 'JEMPUTAN PERKAHWINAN');
        array_push($layers, ...$this->names(730, ['s' => 110, 'f' => 'd', 'c' => 'head']));
        $layers[] = $this->rule(410, 1120, 260, ['op' => 55]);
        $layers[] = $this->t('{{wedding_message}}', 145, 1200, 790, 350, ['f' => 'r', 's' => 40, 'c' => 'ink', 'lh' => 1.4, 'va' => 'top', 'name' => 'Invitation message']);

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_mihrab_bloom(): array
    {
        $layers = [$this->artworkImage('mihrab-bloom', 'Ivory mihrab and mauve flowers', true, true)];
        $layers[] = $this->eyebrow(495, 'pri', 'BUTIRAN MAJLIS');
        $layers[] = $this->t('Majlis Perkahwinan', 120, 605, 840, 145, ['f' => 'd', 's' => 86, 'c' => 'head']);
        $layers[] = $this->rule(410, 805, 260, ['op' => 55]);
        $layers[] = $this->t('{{date_full}}', 130, 895, 820, 125, ['f' => 'r', 's' => 60, 'c' => 'ink']);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 150, 1045, 780, 80, ['f' => 'n', 's' => 35, 'c' => 'acc', 'ls' => .15]);
        $layers[] = $this->t('{{venue_name}}', 130, 1200, 820, 95, ['f' => 'd', 's' => 64, 'c' => 'pri']);
        $layers[] = $this->t('{{venue_address}}', 170, 1325, 740, 220, ['f' => 'r', 's' => 39, 'c' => 'ink', 'lh' => 1.45, 'va' => 'top']);

        return $layers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function suteraImage(string $asset, string $name, int $x, int $y, int $w, int $h, bool $motion = true, int $rotation = 0, bool $widgetBackground = false): array
    {
        return $this->L('image', $name, [
            'src' => '/img/layers/sutera-'.$asset.'.webp',
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'shape' => 'rect', 'fit' => 'contain', 'rotation' => $rotation,
            'motion' => $motion, 'widgetBackground' => $widgetBackground,
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_sutera_zaitun(): array
    {
        $layers = [$this->suteraImage('satin', 'Ivory silk', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->t('WALIMATUL URUS', 140, 270, 800, 90, ['f' => 'n', 's' => 31, 'c' => 'pri', 'ls' => .35]);
        $layers[] = $this->suteraImage('burgundy-envelope', 'Wax-sealed burgundy envelope', 172, 475, 736, 884);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy flowers at the envelope', -45, 785, 485, 511);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Upper olive and burgundy spray', 685, 275, 335, 353, true, 180);
        $layers[] = $this->t('Sebuah jemputan istimewa', 150, 1425, 780, 95, ['f' => 'r', 's' => 44, 'c' => 'ink', 'it' => true]);
        $layers[] = $this->t('{{groom_short}} & {{bride_short}}', 110, 1515, 860, 165, ['f' => 's', 's' => 103, 'c' => 'pri']);
        array_push($layers, ...$this->when(1690, ['s' => 39, 'c' => 'ink']));

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_sutera_zaitun(): array
    {
        // The frame's own art is 660×990 (2:3); a box off that ratio letterboxes
        // it inside empty space instead of actually growing it. 880×1320 keeps the
        // ratio exact, so the whole box is frame — the biggest it can get without
        // crowding the wedding message that sits below it.
        $layers = [$this->suteraImage('satin', 'Ivory silk', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->suteraImage('olive-frame', 'Olive lace cartouche', 100, 145, 880, 1320);
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 215, 520, 650, 115, ['f' => 'r', 's' => 50, 'c' => 'head']);
        $layers[] = $this->t('JEMPUTAN PERKAHWINAN', 215, 650, 650, 75, ['f' => 'n', 's' => 28, 'c' => 'head', 'ls' => .2]);
        $layers[] = $this->t('{{groom_short}}', 190, 765, 700, 175, ['f' => 's', 's' => 104, 'c' => 'head']);
        $layers[] = $this->t('&', 430, 955, 220, 90, ['f' => 'd', 's' => 80, 'c' => 'acc2']);
        $layers[] = $this->t('{{bride_short}}', 190, 1060, 700, 175, ['f' => 's', 's' => 104, 'c' => 'head']);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Lower floral bouquet', 0, 1250, 420, 443);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Upper floral bouquet', 715, 70, 310, 327, true, 180);
        $layers[] = $this->t('{{wedding_message}}', 210, 1550, 660, 330, ['f' => 'r', 's' => 33, 'c' => 'ink', 'lh' => 1.3, 'va' => 'top', 'name' => 'Invitation message']);

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_sutera_zaitun(): array
    {
        $layers = [$this->suteraImage('satin', 'Ivory silk', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->s(122, 240, 836, 1370, ['fill' => 'pri', 'kind' => 'rect', 'r' => 35, 'sh' => 'deep', 'name' => 'Burgundy keepsake card']);
        $layers[] = $this->s(148, 265, 784, 1320, ['fill' => 'pri', 'kind' => 'rect', 'r' => 25, 'bd' => 2, 'bc' => 'acc2', 'name' => 'Fine gold border']);
        $layers[] = $this->t('BUTIRAN MAJLIS', 205, 390, 670, 85, ['f' => 'n', 's' => 30, 'c' => 'head', 'ls' => .27]);
        $layers[] = $this->t('Majlis Perkahwinan', 180, 515, 720, 140, ['f' => 'd', 's' => 83, 'c' => 'head']);
        $layers[] = $this->rule(370, 720, 340, ['c' => 'acc2', 'op' => 75]);
        $layers[] = $this->t('{{date_full}}', 205, 790, 670, 125, ['f' => 'r', 's' => 57, 'c' => 'head']);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 220, 945, 640, 85, ['f' => 'n', 's' => 32, 'c' => 'head']);
        $layers[] = $this->t('{{venue_name}}', 205, 1090, 670, 115, ['f' => 'd', 's' => 65, 'c' => 'head']);
        $layers[] = $this->t('{{venue_address}}', 225, 1220, 630, 205, ['f' => 'r', 's' => 35, 'c' => 'head', 'lh' => 1.3, 'va' => 'top']);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy floral footer', 0, 1320, 565, 595);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy floral header', 760, 20, 285, 300, true, 180);

        return $layers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function liliImage(string $asset, string $name, int $x, int $y, int $w, int $h, bool $motion = true, int $rotation = 0, bool $widgetBackground = false, bool $widgetForeground = false): array
    {
        return $this->L('image', $name, [
            'src' => '/img/layers/lili-'.$asset.'.webp',
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'shape' => 'rect', 'fit' => 'contain', 'rotation' => $rotation,
            'motion' => $motion, 'widgetBackground' => $widgetBackground, 'widgetForeground' => $widgetForeground,
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function liliButterflies(): array
    {
        return $this->L('image', 'Flying blush butterflies', [
            'src' => '/img/layers/lili-flying-butterflies.gif',
            'x' => 0, 'y' => 0, 'w' => self::WIDTH, 'h' => self::HEIGHT,
            'shape' => 'rect', 'fit' => 'contain', 'widgetAnimation' => true,
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_lili_kasih(): array
    {
        $layers = [$this->liliImage('paper', 'Soft ivory paper', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->liliImage('bouquet', 'Blush lilies in the upper corner', -170, -140, 420, 420);
        $layers[] = $this->t('WALIMATUL URUS', 190, 355, 700, 75, ['f' => 'n', 's' => 29, 'c' => 'acc', 'ls' => .3]);
        $layers[] = $this->t('{{bride_short}} & {{groom_short}}', 100, 485, 880, 225, ['f' => 's', 's' => 130, 'c' => 'head', 'lh' => 1.1]);
        $layers[] = $this->rule(390, 760, 300, ['op' => 55]);
        $layers[] = $this->t('{{wedding_date}}', 155, 810, 770, 90, ['f' => 'r', 's' => 48, 'c' => 'ink']);
        $layers[] = $this->liliImage('couple', 'Illustrated bride and groom', 235, 910, 610, 915);
        $layers[] = $this->liliImage('spray', 'Trailing blossoms at the lower left', -90, 1370, 335, 500);
        $layers[] = $this->liliImage('bouquet', 'Blush lilies at the lower right', 785, 1350, 380, 380, true, 180);
        $layers[] = $this->liliImage('arch', 'Floral entrance arch', 0, 0, self::WIDTH, self::HEIGHT, widgetForeground: true);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_lili_kasih(): array
    {
        $layers = [$this->liliImage('paper', 'Soft ivory paper', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->liliImage('bouquet', 'Blush lilies in the upper corner', -170, -90, 450, 450);
        $layers[] = $this->liliImage('spray', 'Blossoms along the right edge', 865, 555, 270, 405);
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 180, 350, 720, 110, ['f' => 'r', 's' => 55, 'c' => 'head']);
        $layers[] = $this->eyebrow(535, 'acc', 'JEMPUTAN PERKAHWINAN');
        $layers[] = $this->t('Dengan penuh kesyukuran, kami menjemput', 160, 655, 760, 120, ['f' => 'r', 's' => 45, 'c' => 'ink', 'lh' => 1.3]);
        $layers[] = $this->t('{{bride_short}}', 150, 845, 780, 155, ['f' => 's', 's' => 112, 'c' => 'head']);
        $layers[] = $this->t('&', 440, 1030, 200, 85, ['f' => 'd', 's' => 75, 'c' => 'acc']);
        $layers[] = $this->t('{{groom_short}}', 150, 1140, 780, 155, ['f' => 's', 's' => 112, 'c' => 'head']);
        $layers[] = $this->rule(390, 1370, 300, ['op' => 55]);
        $layers[] = $this->t('{{wedding_message}}', 200, 1460, 680, 300, ['f' => 'r', 's' => 37, 'c' => 'ink', 'lh' => 1.35, 'va' => 'top', 'name' => 'Invitation message']);
        $layers[] = $this->liliImage('spray', 'Blossoms along the lower left edge', -100, 1525, 260, 390, true, 180);
        $layers[] = $this->liliImage('arch', 'Floral entrance arch', 0, 0, self::WIDTH, self::HEIGHT, widgetForeground: true);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_lili_kasih(): array
    {
        $layers = [$this->liliImage('paper', 'Soft ivory paper', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->liliImage('spray', 'Blossoms along the upper right edge', 840, 80, 290, 435);
        $layers[] = $this->liliImage('bouquet', 'Blush lilies at the lower left', -170, 1430, 480, 480);
        $layers[] = $this->eyebrow(350, 'acc', 'BUTIRAN MAJLIS');
        $layers[] = $this->t('Hari Bahagia Kami', 135, 485, 810, 150, ['f' => 'd', 's' => 91, 'c' => 'head']);
        $layers[] = $this->rule(380, 710, 320, ['op' => 55]);
        $layers[] = $this->t('{{date_full}}', 130, 830, 820, 140, ['f' => 'r', 's' => 65, 'c' => 'ink']);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 180, 1020, 720, 85, ['f' => 'n', 's' => 35, 'c' => 'acc']);
        $layers[] = $this->t('{{venue_name}}', 145, 1200, 790, 115, ['f' => 'd', 's' => 76, 'c' => 'head']);
        $layers[] = $this->t('{{venue_address}}', 175, 1360, 730, 220, ['f' => 'r', 's' => 39, 'c' => 'ink', 'lh' => 1.4, 'va' => 'top']);
        $layers[] = $this->t('Semoga kehadiran anda menyerikan majlis kami', 220, 1690, 640, 105, ['f' => 'r', 's' => 36, 'c' => 'mut', 'it' => true]);
        $layers[] = $this->liliImage('arch', 'Floral entrance arch', 0, 0, self::WIDTH, self::HEIGHT, widgetForeground: true);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function tamanImage(string $asset, string $name, bool $motion = false, bool $widgetBackground = false, bool $widgetForeground = false): array
    {
        return $this->L('image', $name, [
            'src' => '/img/layers/taman-bulan-'.$asset.'.webp',
            'x' => 0, 'y' => 0, 'w' => self::WIDTH, 'h' => self::HEIGHT,
            'shape' => 'rect', 'fit' => 'cover', 'motion' => $motion,
            'widgetBackground' => $widgetBackground, 'widgetForeground' => $widgetForeground,
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function tamanButterflies(): array
    {
        return $this->L('image', 'Flying butterflies', [
            'src' => '/img/layers/lili-flying-butterflies.gif',
            'x' => 0, 'y' => 0, 'w' => self::WIDTH, 'h' => self::HEIGHT,
            'shape' => 'rect', 'fit' => 'contain', 'widgetAnimation' => true,
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_taman_bulan(): array
    {
        $layers = [$this->tamanImage('night', 'Moonlit garden', widgetBackground: true)];
        $layers[] = $this->t('WALIMATUL URUS', 180, 425, 720, 70, ['f' => 'n', 's' => 31, 'c' => 'acc2', 'ls' => .32]);
        $layers[] = $this->t('Di bawah cahaya bulan', 145, 520, 790, 100, ['f' => 'r', 's' => 47, 'c' => 'onbg', 'it' => true]);
        $layers[] = $this->t('{{bride_short}}', 145, 690, 790, 155, ['f' => 'd', 's' => 135, 'c' => 'head']);
        $layers[] = $this->t('&', 425, 845, 230, 115, ['f' => 's', 's' => 105, 'c' => 'acc']);
        $layers[] = $this->t('{{groom_short}}', 145, 965, 790, 155, ['f' => 'd', 's' => 135, 'c' => 'head']);
        $layers[] = $this->rule(410, 1175, 260, ['c' => 'acc', 'op' => 80]);
        $layers[] = $this->t('{{wedding_date}}', 160, 1240, 760, 85, ['f' => 'r', 's' => 47, 'c' => 'onbg']);
        $layers[] = $this->tamanImage('arch', 'Jasmine and wisteria gateway', true, widgetForeground: true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_taman_bulan(): array
    {
        $layers = [$this->tamanImage('night', 'Moonlit garden', widgetBackground: true)];
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 165, 380, 750, 115, ['f' => 'r', 's' => 54, 'c' => 'acc2']);
        $layers[] = $this->eyebrow(550, 'acc', 'JEMPUTAN PERKAHWINAN');
        $layers[] = $this->t('Dengan penuh kesyukuran', 150, 650, 780, 100, ['f' => 'r', 's' => 44, 'c' => 'ink', 'it' => true]);
        $layers[] = $this->t('{{bride_short}}', 145, 780, 790, 145, ['f' => 'd', 's' => 117, 'c' => 'head']);
        $layers[] = $this->t('&', 435, 930, 210, 105, ['f' => 's', 's' => 92, 'c' => 'acc']);
        $layers[] = $this->t('{{groom_short}}', 145, 1040, 790, 145, ['f' => 'd', 's' => 117, 'c' => 'head']);
        $layers[] = $this->rule(410, 1230, 260, ['c' => 'acc', 'op' => 80]);
        $layers[] = $this->t('{{wedding_message}}', 195, 1330, 690, 280, ['f' => 'r', 's' => 37, 'c' => 'ink', 'lh' => 1.3, 'va' => 'top', 'name' => 'Invitation message']);
        $layers[] = $this->tamanImage('arch', 'Jasmine and wisteria gateway', true, widgetForeground: true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_taman_bulan(): array
    {
        $layers = [$this->tamanImage('night', 'Moonlit garden', widgetBackground: true)];
        $layers[] = $this->eyebrow(410, 'acc', 'BUTIRAN MAJLIS');
        $layers[] = $this->t('Malam Bahagia', 140, 540, 800, 145, ['f' => 'd', 's' => 94, 'c' => 'head']);
        $layers[] = $this->rule(410, 750, 260, ['c' => 'acc', 'op' => 80]);
        $layers[] = $this->t('{{date_full}}', 160, 860, 760, 145, ['f' => 'r', 's' => 65, 'c' => 'ink']);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 180, 1030, 720, 85, ['f' => 'n', 's' => 36, 'c' => 'acc2']);
        $layers[] = $this->t('{{venue_name}}', 165, 1190, 750, 120, ['f' => 'd', 's' => 76, 'c' => 'head']);
        $layers[] = $this->t('{{venue_address}}', 200, 1340, 680, 210, ['f' => 'r', 's' => 40, 'c' => 'ink', 'lh' => 1.35, 'va' => 'top']);
        $layers[] = $this->tamanImage('arch', 'Jasmine and wisteria gateway', true, widgetForeground: true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_kasih_sutera(): array
    {
        $layers = [$this->suteraImage('satin', 'Ivory silk', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->t('WALIMATUL URUS', 190, 330, 700, 70, ['f' => 'n', 's' => 29, 'c' => 'acc', 'ls' => .3]);
        $layers[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 420, 900, 200, ['f' => 's', 's' => 118, 'c' => 'head', 'lh' => 1.1]);
        $layers[] = $this->rule(400, 650, 280, ['op' => 55]);
        $layers[] = $this->t('{{wedding_date}}', 160, 690, 760, 80, ['f' => 'r', 's' => 44, 'c' => 'ink', 'ls' => .2, 'up' => true]);
        $layers[] = $this->liliImage('couple', 'Illustrated bride and groom', 220, 960, 640, 960);
        $layers[] = $this->liliImage('bouquet', 'Blush lilies at the lower right', 640, 1420, 460, 460, true, 180);
        $layers[] = $this->liliImage('spray', 'Trailing blossoms at the lower left', 0, 1330, 360, 540, true, 180);
        $layers[] = $this->liliImage('arch', 'Floral entrance arch', 0, 0, self::WIDTH, self::HEIGHT, widgetForeground: true);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_kasih_sutera(): array
    {
        $layers = [$this->suteraImage('satin', 'Ivory silk', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->liliImage('bouquet', 'Blush lilies in the upper left', -160, -100, 440, 440);
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 180, 380, 720, 110, ['f' => 'r', 's' => 54, 'c' => 'head']);
        $layers[] = $this->eyebrow(560, 'acc', 'JEMPUTAN PERKAHWINAN');
        array_push($layers, ...$this->names(680, ['s' => 112, 'f' => 's', 'c' => 'head']));
        $layers[] = $this->rule(400, 1100, 280, ['op' => 55]);
        $layers[] = $this->t('{{wedding_message}}', 190, 1180, 700, 330, ['f' => 'r', 's' => 38, 'c' => 'ink', 'lh' => 1.35, 'va' => 'top', 'name' => 'Invitation message']);
        $layers[] = $this->liliImage('bouquet', 'Blush lilies at the lower right', 790, 1420, 400, 400, true, 180);
        $layers[] = $this->liliImage('arch', 'Floral entrance arch', 0, 0, self::WIDTH, self::HEIGHT, widgetForeground: true);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_kasih_sutera(): array
    {
        $layers = [$this->suteraImage('satin', 'Ivory silk', 0, 0, self::WIDTH, self::HEIGHT, false, widgetBackground: true)];
        $layers[] = $this->liliImage('spray', 'Blossoms along the upper left edge', -60, 90, 290, 435, true, 180);
        $layers[] = $this->eyebrow(360, 'acc', 'BUTIRAN MAJLIS');
        $layers[] = $this->t('Majlis Perkahwinan', 130, 490, 820, 150, ['f' => 'd', 's' => 86, 'c' => 'head']);
        $layers[] = $this->rule(400, 700, 280, ['op' => 55]);
        $layers[] = $this->t('{{date_full}}', 140, 810, 800, 140, ['f' => 'r', 's' => 62, 'c' => 'ink']);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 180, 990, 720, 85, ['f' => 'n', 's' => 34, 'c' => 'acc']);
        $layers[] = $this->t('{{venue_name}}', 145, 1160, 790, 115, ['f' => 'd', 's' => 72, 'c' => 'head']);
        $layers[] = $this->t('{{venue_address}}', 185, 1310, 710, 220, ['f' => 'r', 's' => 38, 'c' => 'ink', 'lh' => 1.4, 'va' => 'top']);
        $layers[] = $this->liliImage('bouquet', 'Blush lilies at the lower right', 760, 1420, 440, 440, true, 180);
        $layers[] = $this->liliImage('arch', 'Floral entrance arch', 0, 0, self::WIDTH, self::HEIGHT, widgetForeground: true);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function floralFrame(bool $widgetForeground = false): array
    {
        return $this->L('image', 'White flowers and gold frame', [
            'src' => '/img/layers/ekad-floral-frame.webp',
            'x' => 0, 'y' => 0, 'w' => self::WIDTH, 'h' => self::HEIGHT,
            'shape' => 'rect', 'fit' => 'cover', 'motion' => true, 'widgetForeground' => $widgetForeground,
            'locked' => true, 'editable' => false,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_melur_purnama(): array
    {
        // The frame's gold finials sit on both edges halfway down, so text keeps to x 200–880.
        $layers = [$this->tamanImage('night', 'Moonlit garden', widgetBackground: true)];
        $layers[] = $this->t('Di bawah purnama', 200, 470, 680, 90, ['f' => 'r', 's' => 44, 'c' => 'onbg', 'it' => true]);
        $layers[] = $this->eyebrow(580, 'acc2', 'WALIMATUL URUS', 200, 680);
        array_push($layers, ...$this->names(690, ['s' => 120, 'f' => 's', 'c' => 'head', 'amp' => 'acc', 'x' => 200, 'w' => 680]));
        $layers[] = $this->rule(410, 1180, 260, ['c' => 'acc', 'op' => 80]);
        array_push($layers, ...$this->when(1240, ['s' => 40, 'c' => 'onbg', 'x' => 200, 'w' => 680]));
        $layers[] = $this->floralFrame(true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_melur_purnama(): array
    {
        $layers = [$this->tamanImage('night', 'Moonlit garden', widgetBackground: true)];
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 200, 440, 680, 115, ['f' => 'r', 's' => 52, 'c' => 'acc2']);
        $layers[] = $this->eyebrow(600, 'acc', 'JEMPUTAN PERKAHWINAN', 200, 680);
        $layers[] = $this->t('{{groom_short}} & {{bride_short}}', 200, 700, 680, 240, ['f' => 's', 's' => 100, 'c' => 'head', 'lh' => 1.1]);
        $layers[] = $this->rule(410, 970, 260, ['c' => 'acc', 'op' => 80]);
        $layers[] = $this->t('{{wedding_message}}', 220, 1030, 640, 420, ['f' => 'r', 's' => 36, 'c' => 'ink', 'lh' => 1.4, 'va' => 'top', 'name' => 'Invitation message']);
        $layers[] = $this->floralFrame();
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_melur_purnama(): array
    {
        $layers = [$this->tamanImage('night', 'Moonlit garden', widgetBackground: true)];
        $layers[] = $this->eyebrow(460, 'acc', 'BUTIRAN MAJLIS', 200, 680);
        $layers[] = $this->t('Majlis Perkahwinan', 200, 560, 680, 170, ['f' => 'd', 's' => 76, 'c' => 'head', 'lh' => 1.1]);
        $layers[] = $this->rule(410, 770, 260, ['c' => 'acc', 'op' => 80]);
        $layers[] = $this->t('{{date_full}}', 200, 840, 680, 150, ['f' => 'r', 's' => 56, 'c' => 'ink', 'lh' => 1.2]);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 200, 1010, 680, 80, ['f' => 'n', 's' => 32, 'c' => 'acc2']);
        $layers[] = $this->t('{{venue_name}}', 200, 1130, 680, 170, ['f' => 'd', 's' => 62, 'c' => 'head', 'lh' => 1.15]);
        $layers[] = $this->t('{{venue_address}}', 220, 1320, 640, 220, ['f' => 'r', 's' => 36, 'c' => 'ink', 'lh' => 1.35, 'va' => 'top']);
        $layers[] = $this->floralFrame(true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_mihrab_kasih(): array
    {
        $layers = [$this->artworkImage('mihrab-bloom', 'Ivory mihrab and mauve flowers', true, true)];
        $layers[] = $this->eyebrow(470, 'acc', 'WALIMATUL URUS', 200, 680);
        $layers[] = $this->t('{{groom_short}} & {{bride_short}}', 200, 550, 680, 220, ['f' => 's', 's' => 104, 'c' => 'head', 'lh' => 1.1]);
        $layers[] = $this->t('{{wedding_date}}', 200, 790, 680, 75, ['f' => 'r', 's' => 40, 'c' => 'ink', 'ls' => .2, 'up' => true]);
        $layers[] = $this->liliImage('couple', 'Illustrated bride and groom', 310, 900, 460, 690);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_mihrab_kasih(): array
    {
        // The flowers climb both edges from about y 750, so everything below that keeps to x 270–810.
        $layers = [$this->artworkImage('mihrab-bloom', 'Ivory mihrab and mauve flowers', true, true)];
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 200, 440, 680, 100, ['f' => 'r', 's' => 52, 'c' => 'acc']);
        $layers[] = $this->t('Dengan penuh kesyukuran, kami menjemput', 220, 570, 640, 110, ['f' => 'r', 's' => 38, 'c' => 'ink', 'it' => true, 'lh' => 1.3]);
        $layers[] = $this->t('{{groom_short}}', 270, 700, 540, 140, ['f' => 's', 's' => 100, 'c' => 'head']);
        $layers[] = $this->t('&', 440, 840, 200, 80, ['f' => 'd', 's' => 64, 'c' => 'acc']);
        $layers[] = $this->t('{{bride_short}}', 270, 920, 540, 140, ['f' => 's', 's' => 100, 'c' => 'head']);
        $layers[] = $this->rule(420, 1090, 240, ['op' => 55]);
        $layers[] = $this->t('{{wedding_message}}', 270, 1140, 540, 440, ['f' => 'r', 's' => 32, 'c' => 'ink', 'lh' => 1.35, 'va' => 'top', 'name' => 'Invitation message']);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_mihrab_kasih(): array
    {
        $layers = [$this->artworkImage('mihrab-bloom', 'Ivory mihrab and mauve flowers', true, true)];
        $layers[] = $this->eyebrow(480, 'acc', 'BUTIRAN MAJLIS', 200, 680);
        $layers[] = $this->t('Hari Bahagia Kami', 160, 580, 760, 150, ['f' => 's', 's' => 80, 'c' => 'head']);
        $layers[] = $this->rule(420, 760, 240, ['op' => 55]);
        $layers[] = $this->t('{{date_full}}', 240, 810, 600, 140, ['f' => 'r', 's' => 50, 'c' => 'ink', 'lh' => 1.2]);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 270, 970, 540, 70, ['f' => 'n', 's' => 30, 'c' => 'acc', 'ls' => .12]);
        $layers[] = $this->t('{{venue_name}}', 270, 1070, 540, 160, ['f' => 'd', 's' => 54, 'c' => 'pri', 'lh' => 1.15]);
        $layers[] = $this->t('{{venue_address}}', 270, 1250, 540, 260, ['f' => 'r', 's' => 32, 'c' => 'ink', 'lh' => 1.4, 'va' => 'top']);
        $layers[] = $this->liliButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cover_taman_zaitun(): array
    {
        // The olive cartouche is dark inside, so the names on it take "head" (cream);
        // everything on the sage paper around it takes "ink" or "pri".
        $layers = [$this->artworkImage('sage', 'Sage botanical paper', widgetBackground: true)];
        $layers[] = $this->t('WALIMATUL URUS', 190, 250, 700, 70, ['f' => 'n', 's' => 29, 'c' => 'pri', 'ls' => .32]);
        $layers[] = $this->suteraImage('olive-frame', 'Olive lace cartouche', 180, 340, 720, 1080);
        $layers[] = $this->t('{{groom_short}}', 300, 640, 480, 150, ['f' => 's', 's' => 90, 'c' => 'head']);
        $layers[] = $this->t('&', 440, 800, 200, 80, ['f' => 'd', 's' => 64, 'c' => 'acc2']);
        $layers[] = $this->t('{{bride_short}}', 300, 890, 480, 150, ['f' => 's', 's' => 90, 'c' => 'head']);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy flowers at the lower left', -50, 1500, 360, 379);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy spray at the upper right', 720, 110, 320, 337, true, 180);
        array_push($layers, ...$this->when(1450, ['s' => 40, 'c' => 'ink', 'x' => 280, 'w' => 700]));

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_taman_zaitun(): array
    {
        $layers = [$this->artworkImage('sage', 'Sage botanical paper', widgetBackground: true)];
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 200, 330, 680, 115, ['f' => 'r', 's' => 52, 'c' => 'pri']);
        $layers[] = $this->eyebrow(490, 'acc', 'JEMPUTAN PERKAHWINAN', 200, 680);
        array_push($layers, ...$this->names(600, ['s' => 110, 'f' => 's', 'c' => 'pri', 'x' => 200, 'w' => 680]));
        $layers[] = $this->rule(400, 1010, 280, ['op' => 70]);
        $layers[] = $this->t('{{wedding_message}}', 210, 1080, 660, 380, ['f' => 'r', 's' => 36, 'c' => 'ink', 'lh' => 1.4, 'va' => 'top', 'name' => 'Invitation message']);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy flowers at the lower left', -30, 1480, 400, 421);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy spray at the upper right', 740, 40, 300, 316, true, 180);

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_taman_zaitun(): array
    {
        // Only the middle of the oval is wide enough for a line, so the details stay
        // inside x 320–760 and y 620–1400.
        $layers = [$this->artworkImage('sage', 'Sage botanical paper', widgetBackground: true)];
        $layers[] = $this->suteraImage('olive-frame', 'Olive lace cartouche', 100, 300, 880, 1320);
        $layers[] = $this->t('BUTIRAN MAJLIS', 320, 630, 440, 60, ['f' => 'n', 's' => 24, 'c' => 'acc2', 'ls' => .24]);
        $layers[] = $this->t('Majlis Perkahwinan', 320, 700, 440, 130, ['f' => 'd', 's' => 52, 'c' => 'head', 'lh' => 1.1]);
        $layers[] = $this->rule(460, 845, 160, ['c' => 'acc2', 'op' => 75]);
        $layers[] = $this->t('{{date_full}}', 320, 880, 440, 120, ['f' => 'r', 's' => 40, 'c' => 'head', 'lh' => 1.2]);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 320, 1010, 440, 60, ['f' => 'n', 's' => 26, 'c' => 'acc2']);
        $layers[] = $this->t('{{venue_name}}', 320, 1080, 440, 120, ['f' => 'd', 's' => 44, 'c' => 'head', 'lh' => 1.15]);
        $layers[] = $this->t('{{venue_address}}', 350, 1200, 380, 160, ['f' => 'r', 's' => 25, 'c' => 'head', 'lh' => 1.3, 'va' => 'top']);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy flowers at the lower left', -40, 1450, 440, 463);
        $layers[] = $this->suteraImage('burgundy-flowers', 'Burgundy spray at the upper right', 730, 60, 310, 327, true, 180);

        return $layers;
    }

    /**
     * The gateway's pillars and hanging wisteria leave a clear opening only about
     * 580 wide down the middle, so every line on these three scenes stays inside
     * x 250–830; anything wider slips behind the flowers.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function cover_gerbang_wisteria(): array
    {
        $layers = [$this->artworkImage('ivory', 'Ivory paper', widgetBackground: true)];
        $layers[] = $this->eyebrow(450, 'acc', 'WALIMATUL URUS', 250, 580);
        $layers[] = $this->t('Sebuah kisah cinta', 250, 530, 580, 80, ['f' => 'r', 's' => 42, 'c' => 'ink', 'it' => true]);
        array_push($layers, ...$this->names(660, ['s' => 108, 'f' => 'd', 'c' => 'head', 'amp' => 'acc2', 'ampF' => 's', 'x' => 250, 'w' => 580]));
        $layers[] = $this->rule(420, 1110, 240, ['op' => 70]);
        array_push($layers, ...$this->when(1170, ['s' => 38, 'c' => 'ink', 'x' => 250, 'w' => 580]));
        $layers[] = $this->tamanImage('arch', 'Jasmine and wisteria gateway', true, widgetForeground: true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inv_gerbang_wisteria(): array
    {
        $layers = [$this->artworkImage('ivory', 'Ivory paper', widgetBackground: true)];
        $layers[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 250, 430, 580, 100, ['f' => 'r', 's' => 44, 'c' => 'acc']);
        $layers[] = $this->t('JEMPUTAN PERKAHWINAN', 250, 560, 580, 56, ['f' => 'n', 's' => 24, 'wt' => 500, 'ls' => .24, 'c' => 'acc2', 'name' => 'Heading line']);
        $layers[] = $this->t('{{groom_short}}', 250, 650, 580, 140, ['f' => 's', 's' => 100, 'c' => 'head']);
        $layers[] = $this->t('&', 440, 790, 200, 90, ['f' => 'd', 's' => 70, 'c' => 'acc']);
        $layers[] = $this->t('{{bride_short}}', 250, 880, 580, 140, ['f' => 's', 's' => 100, 'c' => 'head']);
        $layers[] = $this->rule(420, 1050, 240, ['op' => 70]);
        $layers[] = $this->t('{{wedding_message}}', 250, 1100, 580, 460, ['f' => 'r', 's' => 34, 'c' => 'ink', 'lh' => 1.35, 'va' => 'top', 'name' => 'Invitation message']);
        $layers[] = $this->tamanImage('arch', 'Jasmine and wisteria gateway', true, widgetForeground: true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function evt_gerbang_wisteria(): array
    {
        $layers = [$this->artworkImage('ivory', 'Ivory paper', widgetBackground: true)];
        $layers[] = $this->eyebrow(450, 'acc', 'BUTIRAN MAJLIS', 250, 580);
        $layers[] = $this->t('Majlis Perkahwinan', 250, 540, 580, 190, ['f' => 'd', 's' => 72, 'c' => 'head', 'lh' => 1.1]);
        $layers[] = $this->rule(420, 760, 240, ['op' => 70]);
        $layers[] = $this->t('{{date_full}}', 250, 820, 580, 150, ['f' => 'r', 's' => 52, 'c' => 'ink', 'lh' => 1.2]);
        $layers[] = $this->t('{{time_12}} – {{end_time_12}}', 250, 990, 580, 70, ['f' => 'n', 's' => 30, 'c' => 'acc2']);
        $layers[] = $this->t('{{venue_name}}', 250, 1090, 580, 170, ['f' => 'd', 's' => 58, 'c' => 'head', 'lh' => 1.15]);
        $layers[] = $this->t('{{venue_address}}', 250, 1280, 580, 260, ['f' => 'r', 's' => 34, 'c' => 'ink', 'lh' => 1.35, 'va' => 'top']);
        $layers[] = $this->tamanImage('arch', 'Jasmine and wisteria gateway', true, widgetForeground: true);
        $layers[] = $this->tamanButterflies();

        return $layers;
    }
}
