<?php

namespace App\Support\Card;

/**
 * Invitation (scene 2) and event-details (scene 3) layouts, styled from the template's palette,
 * ornaments and frame so the whole card keeps one visual identity.
 */
trait Inner
{
    /**
     * Frame + corner ornaments shared by inner scenes.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function innerDecor(string $mode = 'top', int $size = 380, int $inset = 0): array
    {
        $L = [];
        if (! empty($this->d['frame'])) {
            $L[] = $this->o($this->d['frame'], 40, 60, 1000, 1800, ['c' => 'acc', 'c2' => 'acc2', 'op' => 85, 'name' => 'Frame']);
        }
        if (! empty($this->d['orn']) && $mode !== 'none') {
            array_push($L, ...$this->corners($this->d['orn'], $mode, $size, $inset, ['op' => 78]));
        }

        return $L;
    }

    protected function greeting(int $y, int $size = 84, string $c = 'pri'): array
    {
        return $this->t('Assalamualaikum & Salam Sejahtera', 100, $y, 880, (int) ($size * 2.5), ['f' => 's', 's' => $size, 'c' => $c, 'lh' => 1.1, 'name' => 'Greeting']);
    }

    protected function message(int $y, int $h = 300, int $size = 36): array
    {
        return $this->t('{{wedding_message}}', 130, $y, 820, $h, ['f' => 'r', 's' => $size, 'c' => 'ink', 'lh' => 1.55, 'va' => 'top', 'name' => 'Invitation message']);
    }

    protected function div(int $y, int $w = 500): array
    {
        return $this->divider($this->d['div'] ?? 'divider-diamond', $y, $w)[0];
    }

    protected function inv_arch(): array
    {
        $L = $this->bg('inner');
        array_push($L, ...$this->innerDecor('top', 360));
        $L[] = $this->greeting(250);
        $L[] = $this->message(470, 260);
        $L[] = $this->o('arch-frame', 280, 800, 520, 693, ['c' => 'acc', 'c2' => 'acc2', 'sh' => true, 'name' => 'Arch frame']);
        $L[] = $this->i('couple_image', 341, 861, 399, 589, ['shape' => 'arch', 'sh' => 'deep', 'name' => 'Couple photo']);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 1540, 900, 130, ['f' => 's', 's' => 96, 'c' => 'pri', 'name' => 'Couple names']);
        $L[] = $this->div(1710, 460);

        return $L;
    }

    protected function inv_circle(): array
    {
        $L = $this->bg('inner');
        array_push($L, ...$this->innerDecor('diag', 400));
        $L[] = $this->t('Dengan nama Allah Yang Maha Pengasih', 140, 250, 800, 70, ['f' => 'r', 's' => 34, 'it' => true, 'c' => 'acc', 'name' => 'Opening line']);
        $L[] = $this->o('wreath-wild', 190, 350, 700, 700, ['c' => 'acc', 'c2' => 'acc2', 'op' => 80, 'name' => 'Photo wreath']);
        $L[] = $this->i('couple_image', 300, 460, 480, 480, ['shape' => 'circle', 'bd' => 6, 'bc' => 'acc', 'sh' => 'deep', 'name' => 'Couple photo']);
        $L[] = $this->greeting(1080, 78);
        $L[] = $this->message(1290, 300, 34);
        $L[] = $this->div(1680, 420);

        return $L;
    }

    protected function inv_top(): array
    {
        $L = $this->bg('inner');
        $L[] = $this->i('couple_image', 0, 0, 1080, 980, ['shape' => 'rect', 'r' => 0, 'name' => 'Feature photo']);
        $L[] = $this->s(0, 620, 1080, 400, ['kind' => 'fade', 'fill' => 'card', 'ang' => 0, 'name' => 'Photo fade']);
        $L[] = $this->s(-200, 900, 1480, 1480, ['kind' => 'circle', 'fill' => 'card', 'name' => 'Curve mask']);
        $L[] = $this->o('sparkle', 500, 820, 80, 80, ['c' => 'acc', 'sh' => 'glow']);
        $L[] = $this->greeting(960, 80);
        $L[] = $this->message(1200, 280, 34);
        $L[] = $this->div(1560, 420);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 1620, 900, 130, ['f' => 's', 's' => 92, 'c' => 'pri', 'name' => 'Couple names']);
        if (! empty($this->d['orn'])) {
            array_push($L, ...$this->corners($this->d['orn'], 'bottom', 320, 0, ['op' => 70]));
        }

        return $L;
    }

    protected function inv_text(): array
    {
        $L = $this->bg('inner');
        array_push($L, ...$this->innerDecor('all', 300, 30));
        $L[] = $this->o('medallion', 380, 200, 320, 320, ['c' => 'acc', 'c2' => 'acc2', 'op' => 90, 'sh' => 'glow', 'name' => 'Medallion']);
        $L[] = $this->t('بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ', 180, 560, 720, 100, ['f' => 'r', 's' => 58, 'c' => 'acc', 'name' => 'Bismillah']);
        $L[] = $this->greeting(700, 76);
        $L[] = $this->message(900, 260, 36);
        $L[] = $this->div(1210, 440);
        $L[] = $this->t('Bersama keluarga kedua belah pihak', 140, 1290, 800, 60, ['f' => 'r', 's' => 34, 'it' => true, 'c' => 'acc', 'name' => 'Families line']);
        $L[] = $this->t('{{groom_father}}\n& {{groom_mother}}', 90, 1370, 440, 150, ['f' => 'r', 's' => 30, 'c' => 'ink', 'lh' => 1.5, 'name' => 'Groom parents']);
        $L[] = $this->t('{{bride_father}}\n& {{bride_mother}}', 550, 1370, 440, 150, ['f' => 'r', 's' => 30, 'c' => 'ink', 'lh' => 1.5, 'name' => 'Bride parents']);
        $L[] = $this->rule(539, 1370, 150, ['v' => true, 'c' => 'acc', 'op' => 60]);

        return $L;
    }

    protected function inv_duo(): array
    {
        $L = $this->bg('inner');
        array_push($L, ...$this->innerDecor('top', 340));
        $L[] = $this->t('Dua hati, satu perjalanan', 140, 250, 800, 110, ['f' => 's', 's' => 84, 'c' => 'pri', 'name' => 'Heading']);
        $L[] = $this->message(400, 220, 32);
        foreach (['groom' => 90, 'bride' => 560] as $who => $x) {
            $L[] = $this->s($x - 10, 690, 440, 620, ['fill' => 'card', 'r' => 6, 'sh' => true, 'bd' => 2, 'bc' => 'acc', 'name' => ucfirst($who).' card']);
            $L[] = $this->i($who.'_image', $x + 10, 710, 400, 460, ['shape' => 'rect', 'r' => 2, 'name' => ucfirst($who).' photo']);
            $L[] = $this->t('{{'.$who.'_name}}', $x, 1190, 420, 90, ['f' => 'd', 's' => 44, 'c' => 'pri', 'lh' => 1.1, 'name' => ucfirst($who).' full name']);
        }
        $L[] = $this->s(490, 880, 100, 100, ['kind' => 'circle', 'fill' => 'card', 'bd' => 2, 'bc' => 'acc', 'sh' => true, 'name' => 'Ampersand badge']);
        $L[] = $this->t('&', 490, 880, 100, 100, ['f' => 'r', 's' => 70, 'c' => 'acc', 'it' => true, 'name' => 'Ampersand']);
        $L[] = $this->t('{{groom_bio}}', 90, 1360, 420, 200, ['f' => 'r', 's' => 28, 'c' => 'ink', 'it' => true, 'lh' => 1.45, 'va' => 'top', 'op' => 88, 'name' => 'Groom bio']);
        $L[] = $this->t('{{bride_bio}}', 570, 1360, 420, 200, ['f' => 'r', 's' => 28, 'c' => 'ink', 'it' => true, 'lh' => 1.45, 'va' => 'top', 'op' => 88, 'name' => 'Bride bio']);
        $L[] = $this->div(1680, 440);

        return $L;
    }

    protected function inv_editorial(): array
    {
        $L = $this->bg('inner');
        $L[] = $this->t('BAB SATU', 70, 100, 500, 40, ['f' => 'n', 's' => 22, 'ls' => .4, 'c' => 'acc', 'al' => 'left']);
        $L[] = $this->rule(70, 150, 940, ['c' => 'ink', 'op' => 80]);
        $L[] = $this->t('Dua\nkisah,\nsatu\njanji.', 60, 190, 470, 620, ['f' => 'd', 's' => 118, 'c' => 'pri', 'al' => 'left', 'lh' => .98, 'va' => 'top', 'name' => 'Headline']);
        $L[] = $this->s(560, 250, 450, 700, ['fill' => 'acc', 'op' => 90, 'name' => 'Photo offset']);
        $L[] = $this->i('couple_image', 530, 220, 450, 700, ['shape' => 'rect', 'r' => 0, 'sh' => 'deep', 'name' => 'Feature photo']);
        $L[] = $this->t('{{wedding_message}}', 70, 1010, 520, 480, ['f' => 'r', 's' => 36, 'c' => 'ink', 'al' => 'left', 'va' => 'top', 'lh' => 1.5, 'name' => 'Invitation message']);
        $L[] = $this->rule(70, 1570, 260, ['c' => 'acc', 'th' => 4]);
        $L[] = $this->t('{{groom_name}}\n& {{bride_name}}', 70, 1600, 700, 170, ['f' => 'r', 's' => 44, 'c' => 'ink', 'al' => 'left', 'it' => true, 'lh' => 1.3, 'va' => 'top', 'name' => 'Full names']);
        $L[] = $this->t('“Dan bermulalah perjalanan kami.”', 640, 1080, 380, 360, ['f' => 'r', 's' => 50, 'c' => 'acc', 'al' => 'left', 'it' => true, 'lh' => 1.25, 'va' => 'top', 'name' => 'Pull quote']);

        return $L;
    }

    protected function inv_polaroid(): array
    {
        $L = $this->bg('inner');
        $L[] = $this->t('dua hati, satu janji', 100, 150, 880, 110, ['f' => 's', 's' => 90, 'c' => 'pri', 'rot' => -2, 'name' => 'Heading']);
        foreach ([['couple_image', 110, 340, -5, 'photo-couple'], ['cover_image', 530, 560, 4, 'photo-landscape']] as $k => [$bind, $x, $y, $r, $ph]) {
            $L[] = $this->s($x, $y, 430, 520, ['fill' => '#fdfcf8', 'r' => 4, 'rot' => $r, 'sh' => 'deep', 'name' => 'Polaroid '.($k + 1)]);
            $L[] = $this->i($bind, $x + 24, $y + 24, 382, 382, ['shape' => 'rect', 'r' => 0, 'rot' => $r, 'ph' => $ph, 'name' => 'Polaroid photo '.($k + 1)]);
            $L[] = $this->o('tape', $x + 110, $y - 28, 200, 62, ['c' => 'acc2', 'op' => 85, 'rot' => $r + 7, 'name' => 'Tape '.($k + 1)]);
        }
        $L[] = $this->s(110, 1140, 860, 470, ['fill' => 'card', 'r' => 8, 'op' => 92, 'sh' => true, 'rot' => -1, 'name' => 'Note card']);
        $L[] = $this->t('{{wedding_message}}', 150, 1180, 780, 320, ['f' => 's', 's' => 46, 'c' => 'ink', 'lh' => 1.3, 'rot' => -1, 'name' => 'Handwritten message']);
        $L[] = $this->t('dengan kasih, {{groom_short}} & {{bride_short}}', 150, 1500, 780, 80, ['f' => 's', 's' => 56, 'c' => 'pri', 'rot' => -1, 'name' => 'Sign-off']);
        $L[] = $this->o('heart', 830, 1620, 80, 72, ['c' => 'pri', 'rot' => 12, 'name' => 'Heart']);
        if (! empty($this->d['orn'])) {
            array_push($L, ...$this->corners($this->d['orn'], 'anti', 340, 0, ['op' => 70]));
        }

        return $L;
    }

    protected function inv_bloom(): array
    {
        $L = $this->bg('inner');
        $L[] = $this->o($this->d['orn'] ? 'cluster-peony' : 'cluster-rose', 90, -140, 900, 900, ['c' => 'pri', 'c2' => 'acc', 'op' => 88, 'sh' => 'deep', 'name' => 'Bouquet top']);
        $L[] = $this->o('cluster-rose', 240, 1180, 780, 780, ['c' => 'acc', 'c2' => 'pri', 'op' => 85, 'rot' => 165, 'sh' => 'deep', 'name' => 'Bouquet bottom']);
        $L[] = $this->s(120, 640, 840, 760, ['fill' => 'card', 'r' => 30, 'op' => 90, 'sh' => 'deep', 'name' => 'Soft card']);
        $L[] = $this->greeting(690, 72);
        $L[] = $this->message(880, 280, 34);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 140, 1190, 800, 120, ['f' => 's', 's' => 86, 'c' => 'pri', 'name' => 'Couple names']);

        return $L;
    }

    // ------------------------------------------------------------------ event scenes

    protected function evt_frame(): array
    {
        $L = $this->bg('inner');
        array_push($L, ...$this->innerDecor('diag', 460));
        $L[] = $this->o('sparkle', 500, 260, 80, 80, ['c' => 'acc', 'sh' => 'glow']);
        $L[] = $this->t('Majlis Perkahwinan', 90, 420, 900, 230, ['f' => 's', 's' => 120, 'c' => 'pri', 'lh' => 1.05, 'name' => 'Title']);
        $L[] = $this->div(690, 560);
        $L[] = $this->t('{{date_full}}', 90, 800, 900, 140, ['f' => 'r', 's' => 62, 'c' => 'ink', 'wt' => 500, 'lh' => 1.15, 'name' => 'Date']);
        $L[] = $this->t('{{time_12}} – {{end_time_12}}', 90, 960, 900, 80, ['f' => 'n', 's' => 38, 'ls' => .2, 'c' => 'acc', 'name' => 'Time']);
        $L[] = $this->t('{{venue_name}}', 90, 1150, 900, 100, ['f' => 'r', 's' => 66, 'c' => 'ink', 'wt' => 600, 'name' => 'Venue']);
        $L[] = $this->t('{{venue_address}}', 160, 1270, 760, 200, ['f' => 'n', 's' => 32, 'c' => 'ink', 'lh' => 1.6, 'va' => 'top', 'op' => 80, 'name' => 'Address']);
        $L[] = $this->o('sparkle', 510, 1600, 60, 60, ['c' => 'acc', 'sh' => 'glow']);

        return $L;
    }

    protected function evt_columns(): array
    {
        $L = $this->bg('inner');
        array_push($L, ...$this->innerDecor('top', 340));
        $L[] = $this->t('SIMPAN TARIKH', 90, 330, 900, 60, ['f' => 'n', 's' => 28, 'ls' => .5, 'c' => 'acc', 'wt' => 500, 'name' => 'Heading line']);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 420, 900, 150, ['f' => 'd', 's' => 100, 'c' => 'pri', 'name' => 'Couple names']);
        $L[] = $this->rule(90, 700, 900, ['c' => 'acc', 'op' => 70]);
        $cols = [['TARIKH', '{{day}}', 90], ['MASA', '{{time_12}}', 410], ['TEMPAT', '{{venue_name}}', 730]];
        foreach ($cols as [$lab, $val, $x]) {
            $L[] = $this->t($lab, $x, 740, 260, 40, ['f' => 'n', 's' => 22, 'ls' => .45, 'c' => 'acc', 'name' => $lab.' label']);
            $L[] = $this->t($val, $x, 800, 260, $lab === 'TARIKH' ? 150 : 200, ['f' => 'd', 's' => $lab === 'TARIKH' ? 120 : 46, 'c' => 'ink', 'lh' => 1.05, 'va' => 'top', 'name' => $lab.' value']);
        }
        $L[] = $this->t('{{month}}', 90, 950, 260, 60, ['f' => 'd', 's' => 40, 'c' => 'ink', 'it' => true, 'name' => 'Month']);
        $L[] = $this->rule(360, 740, 300, ['v' => true, 'c' => 'acc', 'op' => 60]);
        $L[] = $this->rule(680, 740, 300, ['v' => true, 'c' => 'acc', 'op' => 60]);
        $L[] = $this->rule(90, 1100, 900, ['c' => 'acc', 'op' => 70]);
        $L[] = $this->t('{{venue_address}}', 160, 1150, 760, 160, ['f' => 'n', 's' => 32, 'c' => 'ink', 'lh' => 1.6, 'va' => 'top', 'name' => 'Address']);
        $L[] = $this->t('{{year}}', 90, 1420, 900, 200, ['f' => 'd', 's' => 190, 'c' => 'acc', 'op' => 22, 'name' => 'Year watermark']);

        return $L;
    }

    protected function evt_list(): array
    {
        $L = $this->bg('inner');
        array_push($L, ...$this->innerDecor('bottom', 380));
        $L[] = $this->t('Butiran\nMajlis', 90, 160, 900, 330, ['f' => 'd', 's' => 150, 'c' => 'pri', 'al' => 'left', 'lh' => 1, 'va' => 'top', 'name' => 'Title']);
        $rows = [['TARIKH', '{{date_full}}', 590], ['MASA', '{{time_12}} – {{end_time_12}}', 790], ['TEMPAT', '{{venue_name}}', 990], ['ALAMAT', '{{venue_address}}', 1190]];
        foreach ($rows as [$lab, $val, $y]) {
            $L[] = $this->rule(90, $y - 20, 900, ['c' => 'acc', 'op' => 60]);
            $L[] = $this->t($lab, 90, $y, 240, 60, ['f' => 'n', 's' => 22, 'ls' => .4, 'c' => 'acc', 'al' => 'left', 'wt' => 500, 'name' => $lab.' label']);
            $L[] = $this->t($val, 340, $y - 10, 650, 150, ['f' => 'r', 's' => 42, 'c' => 'ink', 'al' => 'left', 'lh' => 1.25, 'va' => 'top', 'name' => $lab.' value']);
        }
        $L[] = $this->rule(90, 1390, 900, ['c' => 'acc', 'op' => 60]);

        return $L;
    }

    protected function evt_dark(): array
    {
        $L = [$this->s(0, 0, 1080, 1920, ['name' => 'Background', 'fill' => 'bg', 'fill2' => 'bg2', 'ang' => 200])];
        $L[] = $this->s(40, 300, 1000, 1000, ['kind' => 'glow', 'fill' => 'acc', 'op' => 26, 'name' => 'Glow']);
        $L[] = $this->o('particles', 0, 0, 1080, 1920, ['c' => 'acc2', 'op' => 45, 'name' => 'Gold dust']);
        if (! empty($this->d['frame'])) {
            $L[] = $this->o('frame-thin', 40, 60, 1000, 1800, ['c' => 'acc', 'op' => 75, 'name' => 'Frame']);
        }
        $L[] = $this->o('crescent-star', 440, 240, 200, 200, ['c' => 'acc', 'c2' => 'acc2', 'sh' => 'glow', 'name' => 'Crescent']);
        $L[] = $this->t('Majlis Perkahwinan', 90, 520, 900, 200, ['f' => 's', 's' => 112, 'c' => 'acc', 'name' => 'Title']);
        $L[] = $this->div(760, 560);
        $L[] = $this->t('{{date_full}}', 90, 860, 900, 140, ['f' => 'r', 's' => 60, 'c' => 'onbg', 'wt' => 500, 'name' => 'Date']);
        $L[] = $this->t('{{time_12}} – {{end_time_12}}', 90, 1010, 900, 80, ['f' => 'n', 's' => 36, 'ls' => .22, 'c' => 'acc', 'name' => 'Time']);
        $L[] = $this->t('{{venue_name}}', 90, 1200, 900, 100, ['f' => 'r', 's' => 64, 'c' => 'onbg', 'wt' => 600, 'name' => 'Venue']);
        $L[] = $this->t('{{venue_address}}', 160, 1320, 760, 180, ['f' => 'n', 's' => 30, 'c' => 'onbg', 'lh' => 1.6, 'va' => 'top', 'op' => 78, 'name' => 'Address']);

        return $L;
    }

    protected function evt_arch(): array
    {
        $L = $this->bg('inner');
        $L[] = $this->o('arch-pointed', 120, 200, 840, 1204, ['c' => 'acc', 'c2' => 'acc2', 'sh' => true, 'name' => 'Arch']);
        $L[] = $this->o('star8', 420, 320, 240, 240, ['c' => 'acc', 'op' => 90, 'name' => 'Star']);
        $L[] = $this->t('Walimatul Urus', 180, 640, 720, 130, ['f' => 's', 's' => 96, 'c' => 'pri', 'name' => 'Title']);
        $L[] = $this->t('{{date_full}}', 180, 800, 720, 140, ['f' => 'r', 's' => 52, 'c' => 'ink', 'wt' => 500, 'lh' => 1.15, 'name' => 'Date']);
        $L[] = $this->t('{{time_12}}', 180, 950, 720, 70, ['f' => 'n', 's' => 34, 'ls' => .25, 'c' => 'acc', 'name' => 'Time']);
        $L[] = $this->t('{{venue_name}}', 180, 1050, 720, 90, ['f' => 'r', 's' => 52, 'c' => 'ink', 'wt' => 600, 'name' => 'Venue']);
        $L[] = $this->t('{{venue_address}}', 220, 1150, 640, 150, ['f' => 'n', 's' => 28, 'c' => 'ink', 'lh' => 1.6, 'va' => 'top', 'op' => 80, 'name' => 'Address']);
        $L[] = $this->o('pattern-islamic', 0, 1500, 1080, 420, ['tile' => 130, 'c' => 'acc', 'op' => 26, 'name' => 'Pattern']);
        $L[] = $this->o('divider-star', 260, 1560, 560, 56, ['c' => 'acc', 'name' => 'Divider']);

        return $L;
    }

    protected function evt_band(): array
    {
        $L = $this->bg('inner');
        $L[] = $this->s(0, 620, 1080, 720, ['fill' => 'bg', 'fill2' => 'bg2', 'ang' => 180, 'name' => 'Colour band']);
        $L[] = $this->o('pattern-lattice', 0, 620, 1080, 720, ['tile' => 120, 'c' => 'acc', 'op' => 20, 'name' => 'Band pattern']);
        $L[] = $this->rule(0, 620, 1080, ['th' => 5, 'c' => 'acc', 'op' => 100]);
        $L[] = $this->rule(0, 1335, 1080, ['th' => 5, 'c' => 'acc', 'op' => 100]);
        $L[] = $this->t('Majlis Perkahwinan', 90, 260, 900, 170, ['f' => 's', 's' => 110, 'c' => 'pri', 'name' => 'Title']);
        $L[] = $this->div(470, 520);
        $L[] = $this->t('{{date_full}}', 90, 700, 900, 130, ['f' => 'r', 's' => 60, 'c' => 'onbg', 'wt' => 500, 'name' => 'Date']);
        $L[] = $this->t('{{time_12}} – {{end_time_12}}', 90, 850, 900, 80, ['f' => 'n', 's' => 36, 'ls' => .22, 'c' => 'acc2', 'name' => 'Time']);
        $L[] = $this->t('{{venue_name}}', 90, 1000, 900, 100, ['f' => 'r', 's' => 64, 'c' => 'onbg', 'wt' => 600, 'name' => 'Venue']);
        $L[] = $this->t('{{venue_address}}', 160, 1110, 760, 180, ['f' => 'n', 's' => 28, 'c' => 'onbg', 'lh' => 1.6, 'va' => 'top', 'op' => 80, 'name' => 'Address']);
        $L[] = $this->t('Kami menantikan kehadiran tuan/puan', 140, 1500, 800, 120, ['f' => 'r', 's' => 42, 'c' => 'ink', 'it' => true, 'name' => 'Closing line']);
        if (! empty($this->d['orn'])) {
            array_push($L, ...$this->corners($this->d['orn'], 'bottom', 300, 0, ['op' => 70]));
        }

        return $L;
    }

    protected function evt_editorial(): array
    {
        $L = $this->bg('inner');
        $L[] = $this->t('{{day}}', 30, 60, 700, 620, ['f' => 'd', 's' => 560, 'c' => 'pri', 'al' => 'left', 'lh' => 1, 'wt' => 400, 'name' => 'Day numeral']);
        $L[] = $this->t('{{month}}', 560, 210, 500, 110, ['f' => 'n', 's' => 44, 'ls' => .16, 'c' => 'ink', 'up' => true, 'al' => 'left', 'wt' => 500, 'name' => 'Month']);
        $L[] = $this->t('{{year}}', 560, 330, 450, 110, ['f' => 'r', 's' => 80, 'c' => 'acc', 'al' => 'left', 'it' => true, 'name' => 'Year']);
        $L[] = $this->t('{{weekday}}', 560, 460, 450, 60, ['f' => 'n', 's' => 26, 'ls' => .4, 'c' => 'mut', 'up' => true, 'al' => 'left', 'name' => 'Weekday']);
        $L[] = $this->rule(90, 760, 900, ['c' => 'ink', 'op' => 80, 'th' => 3]);
        $L[] = $this->t('MASA', 90, 810, 300, 40, ['f' => 'n', 's' => 22, 'ls' => .4, 'c' => 'acc', 'al' => 'left', 'name' => 'Time label']);
        $L[] = $this->t('{{time_12}}', 90, 860, 700, 110, ['f' => 'r', 's' => 80, 'c' => 'ink', 'al' => 'left', 'name' => 'Time']);
        $L[] = $this->t('TEMPAT', 90, 1040, 300, 40, ['f' => 'n', 's' => 22, 'ls' => .4, 'c' => 'acc', 'al' => 'left', 'name' => 'Venue label']);
        $L[] = $this->t('{{venue_name}}', 90, 1090, 900, 120, ['f' => 'r', 's' => 76, 'c' => 'ink', 'al' => 'left', 'name' => 'Venue']);
        $L[] = $this->t('{{venue_address}}', 90, 1230, 800, 160, ['f' => 'n', 's' => 30, 'c' => 'ink', 'al' => 'left', 'lh' => 1.6, 'va' => 'top', 'op' => 80, 'name' => 'Address']);
        $L[] = $this->rule(90, 1500, 900, ['c' => 'ink', 'op' => 80, 'th' => 3]);
        $L[] = $this->t('{{groom_short}} & {{bride_short}}', 90, 1540, 900, 120, ['f' => 'd', 's' => 76, 'c' => 'pri', 'al' => 'left', 'name' => 'Couple names']);

        return $L;
    }

    protected function evt_card(): array
    {
        $L = [$this->s(0, 0, 1080, 1920, ['name' => 'Background', 'fill' => 'bg', 'fill2' => 'bg2', 'ang' => 165])];
        if (! empty($this->d['orn'])) {
            array_push($L, ...$this->corners($this->d['orn'], 'diag', 560, -20, ['op' => 70]));
        }
        $L[] = $this->s(120, 380, 840, 1160, ['fill' => 'card', 'r' => 24, 'sh' => 'deep', 'name' => 'Floating card']);
        $L[] = $this->o('frame-thin', 150, 410, 780, 1100, ['c' => 'acc', 'op' => 70, 'name' => 'Card frame']);
        $L[] = $this->t('ANDA DIJEMPUT', 150, 480, 780, 50, ['f' => 'n', 's' => 24, 'ls' => .5, 'c' => 'acc', 'wt' => 500, 'name' => 'Heading line']);
        $L[] = $this->t('{{date_full}}', 150, 570, 780, 160, ['f' => 'd', 's' => 66, 'c' => 'pri', 'lh' => 1.1, 'name' => 'Date']);
        $L[] = $this->div(770, 400);
        $L[] = $this->t('{{time_12}} – {{end_time_12}}', 150, 850, 780, 70, ['f' => 'n', 's' => 34, 'ls' => .2, 'c' => 'ink', 'name' => 'Time']);
        $L[] = $this->t('{{venue_name}}', 150, 990, 780, 100, ['f' => 'r', 's' => 58, 'c' => 'ink', 'wt' => 600, 'name' => 'Venue']);
        $L[] = $this->t('{{venue_address}}', 200, 1110, 680, 200, ['f' => 'n', 's' => 28, 'c' => 'ink', 'lh' => 1.6, 'va' => 'top', 'op' => 80, 'name' => 'Address']);
        $L[] = $this->t('Sahkan kehadiran di bawah', 150, 1380, 780, 60, ['f' => 's', 's' => 50, 'c' => 'acc', 'name' => 'RSVP note']);

        return $L;
    }
}
