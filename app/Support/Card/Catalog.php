<?php

namespace App\Support\Card;

use Illuminate\Support\Str;

/**
 * The Neekah marketplace: 50 layered wedding-invitation designs.
 *
 * pal keys — bg/bg2: cover background · onbg: text on the cover background · head: names colour ·
 * acc/acc2: accent + light accent · card: inner-scene surface · ink: body text on card · pri: headings on card · mut: muted text.
 * cover/inv/evt pick the composition of the three designed scenes (see Covers, CoversB, Inner).
 */
class Catalog
{
    /**
     * The Malay line each design is described by in the gallery, keyed by slug.
     *
     * The names are proper nouns and stay as they are — a design has a name the way
     * a paint colour does — but the sentence under it is read by a couple choosing,
     * and Neekah is a Malay site first.
     *
     * @var array<string, string>
     */
    protected const MS = [
        'royal-songket-gold' => 'Jaringan songket zamrud, bingkai emas berukir, bunga diraja dan medalion potret.',
        'palace-gold' => 'Biru malam dengan gerbang istana, tiang emas dan kaligrafi Bismillah.',
        'seri-melayu' => 'Marun dan emas antik: sempadan berlekuk, sulaman sudut dan jalur pucuk rebung.',
        'songket-ivory' => 'Gading yang tenang dengan bisikan songket, bingkai emas halus dan satu sudut berbunga.',
        'cempaka-gold' => 'Bunga cempaka keemasan dan dedaun mengelilingi kad krim dengan cahaya kuning lembut.',
        'nusantara-royal' => 'Hijau hutan dengan dedaun tropika, jalur motif pucuk dan sempadan geometri emas.',
        'balai-seri' => 'Gerbang balai besar dalam marun dan emas dengan sudut mawar serta potret bergerbang.',
        'tenun-heritage' => 'Jalur tenunan dalam terakota dan emas di sekeliling kad kertas krim.',
        'puteri-melayu' => 'Bunga merah jambu lembut dalam kalungan mengelilingi pengantin, dengan hiasan champagne.',
        'maharaja' => 'Hitam dramatik dengan hiasan emas logam, medalion bintang dan nama yang menjulang.',
        'black-gold' => 'Hitam bergaya fesyen dengan nama editorial yang besar dan satu aksen emas.',
        'white-luxury' => 'Putih bersih, ruang lapang, bingkai emas sehalus garisan dan kalungan yang halus.',
        'champagne' => 'Cahaya champagne lembut yang lebur dari potret penuh ke tipografi serif elegan.',
        'editorial-wedding' => 'Seperti muka majalah: nama besar tidak simetri, potret terpesong dan butiran isu.',
        'modern-gold-line' => 'Kanvas putih dilintangi geometri emas nipis dengan nama dalam bulatan.',
        'midnight-luxury' => 'Langit biru malam dengan bintang emas hanyut dan lingkaran cahaya di sekeliling pengantin.',
        'velvet-burgundy' => 'Baldu marun bertekstur dengan bingkai emas Art Deco — mewah seperti ballroom hotel.',
        'emerald-estate' => 'Zamrud gelap dengan dahan olib dan eukaliptus lukisan tangan mengelilingi potret bujur.',
        'royal-blue' => 'Biru diraja klasik, jambangan laurel, bingkai emas berganda dan huruf serif bersih.',
        'obsidian' => 'Batu hitam bertekstur, palang gangsa dan huruf besar yang tegas.',
        'english-garden' => 'Bunga pastel tumpah dari sudut ke sudut di atas kertas krim yang hangat.',
        'rose-garden' => 'Mawar merah jambu lembut dalam jambangan di atas gading, romantis dan tenang.',
        'peony-romance' => 'Peoni merah jambu yang besar membuak di belakang nama pengantin.',
        'white-blossom' => 'Bunga putih halus di atas krim hangat — sangat bersih, sangat mewah.',
        'botanical-green' => 'Eukaliptus dan olib segar menuruni tepi kad putih yang bersih.',
        'wildflower' => 'Bunga rimba kecil bertaburan dengan nota tulisan tangan di tengahnya.',
        'floral-arch' => 'Gerbang peoni yang lebat memeluk potret, dengan cahaya lembut berkilau.',
        'garden-gate' => 'Pintu taman dilitupi susur bunga terbuka kepada pengantin, di atas krim lembut.',
        'romantic-burgundy-floral' => 'Mawar marun dalam jambangan penuh di bawah nama emas atas latar champagne.',
        'lavender-dream' => 'Padang lavender waktu senja dengan debunga dan cahaya ungu lembut.',
        'islamic-gold' => 'Gading dengan corak bintang Islam keemasan dan kartus medalion.',
        'masjid-arch' => 'Gerbang mihrab tinggi bergaris emas dengan nama pengantin di dalamnya.',
        'geometric-noor' => 'Bintang emas besar dan hamparan corak dengan tipografi moden rata kiri.',
        'emerald-islamic' => 'Hijau zamrud dengan geometri emas bersilang dan medalion potret.',
        'midnight-noor' => 'Bulan sabit di atas langit malam bercorak heksagon dengan bintang halus.',
        'minimal-ivory' => 'Hampir seluruhnya putih. Satu dahan olib, taip kecil, lautan ruang.',
        'beige-modern' => 'Krim hangat dengan potret bujur dan sebatang dahan eukaliptus.',
        'black-minimal' => 'Hitam, taip putih dan satu titik emas kecil. Editorial yang sangat terkawal.',
        'soft-grey' => 'Kad putih terapung di atas kelabu cerah dengan bingkai bergaris halus.',
        'modern-monogram' => 'Huruf awal yang besar membayang di belakang nama, dengan bingkai nipis.',
        'editorial-magazine' => 'Gambar kulit sepenuh halaman dengan kepala berita tebal dan baris kulit.',
        'film-wedding' => 'Rakaman sinematik dengan grain, cahaya hangat dan palang letterbox.',
        'polaroid-love' => 'Tiga polaroid berpita bertaburan di atas kertas hangat dengan kapsyen tulisan tangan.',
        'scrapbook-romance' => 'Kertas berlapis, pita washi, bunga tekan, gambar dan tulisan tangan.',
        'vintage-letter' => 'Kertas surat lama, mawar pudar dan meteri lilin merah.',
        'newspaper-wedding' => 'Surat khabar hitam putih: kepala akhbar, nama sebagai tajuk besar dan butiran majlis dalam kolum.',
        'postcard-love' => 'Poskad lama: setem, cop pos, sempadan berbintik dan nota tulisan tangan.',
        'film-strip' => 'Dua jalur filem di atas latar gelap — sebuah kisah cinta penuh.',
        'modern-abstract' => 'Bentuk pasir, terakota dan emas dalam komposisi grafik kontemporari.',
        'neekah-signature' => 'Yang teristimewa: peoni dan mawar berlapis, potret bergerbang, bingkai emas, grain kertas dan cahaya lembut.',
    ];

    /**
     * @param  array<int, string>  $c  bg, bg2, onbg, head, acc, acc2, card, ink, pri, mut
     * @return array<string, string>
     */
    protected static function pal(array $c): array
    {
        return array_combine(['bg', 'bg2', 'onbg', 'head', 'acc', 'acc2', 'card', 'ink', 'pri', 'mut'], $c);
    }

    /**
     * @param  array<int, string>  $pal
     * @param  array<int, string>  $fonts  display, script, serif, sans
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    protected static function d(int $n, string $name, string $cat, string $style, bool $premium, string $desc, array $pal, array $fonts, string $cover, string $inv, string $evt, string $bg, array $o = []): array
    {
        return [
            'n' => $n, 'name' => $name, 'slug' => $slug = Str::slug($name), 'category' => $cat, 'style' => $style, 'premium' => $premium,
            'desc' => ['en' => $desc, 'ms' => self::MS[$slug] ?? $desc], 'pal' => self::pal($pal), 'fonts' => $fonts, 'cover' => $cover, 'inv' => $inv, 'evt' => $evt, 'bg' => $bg,
            'orn' => null, 'frame' => null, 'div' => 'divider-diamond',
        ] + $o;
    }

    /**
     * The design each card wore before the layered ones, mapped to the nearest of
     * these.
     *
     * A published card is out in the world on people's phones. Moving every couple
     * to one flagship design would change a card their guests have already opened,
     * so the switch keeps each of them as close to what they chose as the new
     * catalogue allows: the same colour family, the same mood. The migration reads
     * this once and nothing else ever should.
     *
     * @var array<string, string>
     */
    public const REPLACES = [
        // Klasik
        'seri-gangsa' => 'seri-melayu',
        'gerbang-emas' => 'balai-seri',
        'bingkai-warisan' => 'songket-ivory',
        'kemboja' => 'cempaka-gold',
        'songket-diraja' => 'royal-songket-gold',
        // Islamik
        'nur-geometri' => 'geometric-noor',
        'tenun-songket' => 'tenun-heritage',
        'mihrab-nilam' => 'midnight-noor',
        // Bunga
        'mawar-pagi' => 'rose-garden',
        'taman-lavender' => 'lavender-dream',
        'kebun-sakura' => 'english-garden',
        'bunga-raya' => 'romantic-burgundy-floral',
        'daun-hijau' => 'botanical-green',
        'bunga-pic' => 'floral-arch',
        // Moden
        'putih-tenang' => 'white-luxury',
        'garis-halus' => 'modern-gold-line',
        'kanvas-pasir' => 'beige-modern',
        'mozek-kenangan' => 'polaroid-love',
        'pita-moden' => 'modern-abstract',
        'kraf-rustik' => 'scrapbook-romance',
        // Malam
        'malam-emas' => 'midnight-luxury',
        'zamrud' => 'emerald-estate',
        'marun-malam' => 'velvet-burgundy',
        'langit-senja' => 'lavender-dream',
    ];

    /**
     * Every photo a design may ask a couple for. The editor offers only the slots
     * the chosen design actually uses, but a save may carry any of them, because a
     * couple who switches design keeps the photos they already uploaded.
     *
     * @return array<int, string>
     */
    public static function photoSlotKeys(): array
    {
        return ['cover_image', 'couple_image', 'groom_image', 'bride_image', 'closing_image'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $CG = 'Cormorant Garamond';
        $PF = 'Playfair Display';

        return [
            // ---------------------------------------------------------------- TRADITIONAL MALAY / SONGKET
            self::d(1, 'Royal Songket Gold', 'Traditional', 'Luxury', true, 'Emerald songket lattice, a gold ornamental frame, royal florals and a portrait medallion.',
                ['#0F3D32', '#0A2A22', '#F3EBD4', '#F3E6BC', '#C8A951', '#EAD9A0', '#F8F2E7', '#23352E', '#0F3D32', '#9FB7AC'], [$CG, 'Great Vibes', $CG, 'Montserrat'],
                'centered', 'arch', 'frame', 'songket', ['orn' => 'corner-floral', 'frame' => 'frame-thin', 'glow' => 'acc', 'glowOp' => 30, 'patternOp' => 26]),
            self::d(2, 'Palace Gold', 'Traditional', 'Luxury', true, 'Midnight navy with an ogee palace arch, gold pillars and Bismillah calligraphy.',
                ['#0E1B33', '#08122A', '#EDE4CF', '#E9CF8B', '#C9A45C', '#F0DCA0', '#0E1B33', '#EDE4CF', '#E2C878', '#8F9BB5'], ['Cinzel', 'Great Vibes', $CG, 'Montserrat'],
                'palace', 'text', 'dark', 'grad', ['orn' => 'corner-filigree', 'frame' => 'frame-thin', 'glow' => 'acc', 'glowOp' => 22, 'div' => 'divider-star', 'card2' => 'bg2']),
            self::d(3, 'Seri Melayu', 'Traditional', 'Classic', false, 'Burgundy and antique gold: scalloped border, filigree corners and pucuk rebung bands.',
                ['#5A1526', '#7D263E', '#F4E8D0', '#F6E7C1', '#C9A45C', '#EBD79E', '#F7EEDC', '#4A2A2E', '#7D263E', '#A87F86'], [$PF, 'Great Vibes', $CG, 'Lato'],
                'border', 'circle', 'band', 'rebung', ['orn' => 'corner-floral', 'frame' => 'frame-scallop', 'patternOp' => 14, 'div' => 'divider-floral']),
            self::d(4, 'Songket Ivory', 'Traditional', 'Minimal', false, 'Quiet ivory with a whisper of songket, a thin gold frame and one blossom corner.',
                ['#F7F1E5', '#EFE6D3', '#4D4232', '#6E5A3A', '#B99A5B', '#DCC58E', '#FBF7EE', '#4D4232', '#6E5A3A', '#9C8F76'], [$CG, 'Allura', 'Lora', 'Montserrat'],
                'quiet', 'top', 'columns', 'songket', ['orn' => 'corner-blossom', 'frame' => 'frame-thin', 'patternOp' => 9, 'div' => 'divider-dots']),
            self::d(5, 'Cempaka Gold', 'Traditional', 'Romantic', false, 'Golden cempaka blooms and leaves framing a cream card with a soft yellow glow.',
                ['#FBF3DD', '#F4E6BB', '#4F4123', '#8A6B1E', '#C9A64B', '#E8D28A', '#FDF8E8', '#4F4123', '#8A6B1E', '#A99A70'], [$PF, 'Parisienne', 'Lora', 'Lato'],
                'cempaka', 'bloom', 'frame', 'grad', ['orn' => 'corner-cempaka', 'frame' => 'frame-single', 'glow' => 'acc2', 'glowOp' => 30, 'div' => 'divider-floral']),
            self::d(6, 'Nusantara Royal', 'Traditional', 'Classic', true, 'Forest green with tropical leaves, pucuk motif bands and gold geometric borders.',
                ['#16382B', '#0F2A20', '#F0E7CD', '#F2E4B8', '#D2B063', '#E9D79A', '#F5EFE0', '#22382D', '#1D4A37', '#8FAA9B'], ['DM Serif Display', 'Great Vibes', 'Libre Baskerville', 'Poppins'],
                'sidebands', 'text', 'list', 'grad', ['orn' => 'corner-tropical', 'glow' => 'acc', 'glowOp' => 16]),
            self::d(7, 'Balai Seri', 'Traditional', 'Luxury', true, 'A grand-hall arch in maroon and gold with rose corners and an arched portrait.',
                ['#4A1220', '#6D1F30', '#F1E4CE', '#F1DCA6', '#C8A24A', '#E9D08C', '#F1E4CE', '#4A2528', '#6D1F30', '#A98A80'], ['Cinzel', 'Allura', $CG, 'Montserrat'],
                'hall', 'duo', 'card', 'grad', ['orn' => 'corner-rose', 'frame' => 'frame-ornate', 'glow' => 'acc', 'glowOp' => 20, 'div' => 'divider-diamond']),
            self::d(8, 'Tenun Heritage', 'Traditional', 'Classic', false, 'Woven textile bands in terracotta and gold around a cream paper card.',
                ['#B4552F', '#96421F', '#F6EBD8', '#96421F', '#D9B36A', '#F0D9A0', '#F6EBD8', '#4A2C1F', '#96421F', '#A47B66'], [$PF, 'Great Vibes', 'Lora', 'Lato'],
                'tenun', 'editorial', 'band', 'solid', ['orn' => 'corner-blossom', 'div' => 'divider-wave']),
            self::d(9, 'Puteri Melayu', 'Traditional', 'Romantic', false, 'Dusty rose florals in a wreath around the couple, with champagne ornaments.',
                ['#EBD3CF', '#DDB6B4', '#5A3B3E', '#A5525E', '#C9A45C', '#E8D2A0', '#FBF1EC', '#5A3B3E', '#A5525E', '#A98A8C'], [$PF, 'Allura', $CG, 'Lato'],
                'wreath', 'circle', 'frame', 'grad', ['orn' => 'corner-rose', 'div' => 'divider-floral', 'glow' => '#ffffff', 'glowOp' => 30]),
            self::d(10, 'Maharaja', 'Traditional', 'Luxury', true, 'Dramatic black with metallic gold ornaments, a star medallion and towering names.',
                ['#0B0B0B', '#191410', '#EADFC5', '#F0D28A', '#C79B3B', '#F0D28A', '#0F0E0D', '#EADFC5', '#E2BC6A', '#8C8371'], ['Cinzel', 'Parisienne', $CG, 'Montserrat'],
                'ornament', 'text', 'dark', 'grad', ['orn' => 'corner-filigree', 'frame' => 'frame-ornate', 'glow' => 'acc', 'glowOp' => 16, 'vignette' => true, 'grain' => 26, 'card2' => 'bg2']),

            // ---------------------------------------------------------------- MODERN LUXURY
            self::d(11, 'Black & Gold', 'Modern', 'Luxury', true, 'High-fashion black with giant editorial names and a single gold accent.',
                ['#0A0A0A', '#121212', '#EDE6D6', '#E9CF8B', '#C8A24A', '#E9CF8B', '#0A0A0A', '#E6DCC4', '#E9CF8B', '#8A8A8A'], ['DM Serif Display', $CG, $CG, 'Inter'],
                'typo', 'editorial', 'list', 'solid', ['grain' => 22]),
            self::d(12, 'White Luxury', 'Modern', 'Elegant', false, 'Pure white, generous space, a hairline gold frame and a delicate garland.',
                ['#FFFFFF', '#FBF8F2', '#4A443A', '#3D3830', '#C9AE6A', '#E6D5A5', '#FFFFFF', '#4A443A', '#3D3830', '#9A927F'], [$CG, 'Allura', 'Lora', 'Montserrat'],
                'minimal', 'top', 'frame', 'grad', ['orn' => 'corner-blossom', 'frame' => 'frame-thin', 'div' => 'divider-dots']),
            self::d(13, 'Champagne', 'Modern', 'Elegant', false, 'Soft champagne light melting from a full-width portrait into elegant serif type.',
                ['#F3E7D3', '#E6D3B0', '#55452F', '#8B6B3A', '#B9975B', '#E5CFA0', '#FAF3E4', '#55452F', '#8B6B3A', '#A3927A'], [$PF, 'Great Vibes', 'Lora', 'Lato'],
                'split', 'circle', 'card', 'grad', ['orn' => 'corner-floral', 'glow' => 'acc2', 'glowOp' => 30]),
            self::d(14, 'Editorial Wedding', 'Modern', 'Editorial', true, 'A magazine spread: huge asymmetric names, an offset portrait and issue details.',
                ['#F5F0E6', '#EDE6D6', '#1C1B19', '#1C1B19', '#A8442F', '#D89A86', '#F5F0E6', '#1C1B19', '#1C1B19', '#6F6A5E'], [$PF, $PF, 'Lora', 'Inter'],
                'editorial', 'editorial', 'editorial', 'solid', []),
            self::d(15, 'Modern Gold Line', 'Modern', 'Luxury', false, 'White canvas crossed by ultra-thin gold geometry with names in a ring.',
                ['#FFFFFF', '#FAF7F0', '#2F2B24', '#2F2B24', '#C9A45C', '#E6D2A2', '#FFFFFF', '#2F2B24', '#2F2B24', '#9C9584'], ['Montserrat', 'Allura', 'Cormorant Garamond', 'Montserrat'],
                'geo', 'duo', 'columns', 'solid', ['frame' => 'frame-single', 'div' => 'divider-double']),
            self::d(16, 'Midnight Luxury', 'Modern', 'Luxury', true, 'Deep navy sky with drifting gold stars and a golden halo around the couple.',
                ['#0B1530', '#050B1E', '#E8E2D2', '#F0DCA0', '#C9A45C', '#F4E2A8', '#0B1530', '#E8E2D2', '#E6CB80', '#8E9AB8'], [$CG, 'Great Vibes', $CG, 'Montserrat'],
                'halo', 'text', 'dark', 'night', ['orn' => 'corner-filigree', 'frame' => 'frame-thin', 'glow' => 'acc', 'glowOp' => 18, 'div' => 'divider-star', 'card2' => 'bg2']),
            self::d(17, 'Velvet Burgundy', 'Modern', 'Luxury', true, 'Textured burgundy velvet with an Art Deco gold frame — luxury hotel ballroom.',
                ['#4A0E1E', '#2E0712', '#EFD9C0', '#EBCB86', '#C9A45C', '#EBCB86', '#3A0A18', '#F1DFC8', '#EBCB86', '#B58D8F'], ['Bodoni Moda', 'Great Vibes', $CG, 'Montserrat'],
                'deco', 'arch', 'list', 'velvet', ['frame' => 'frame-deco', 'orn' => 'corner-rose', 'glow' => 'acc', 'glowOp' => 20, 'vignette' => true, 'grain' => 24, 'div' => 'divider-double', 'card2' => 'bg2']),
            self::d(18, 'Emerald Estate', 'Modern', 'Elegant', true, 'Deep emerald with hand-drawn olive and eucalyptus branches around an oval portrait.',
                ['#0E3B2E', '#09271F', '#EFE7D0', '#F1E6BE', '#CDB06A', '#E9D9A4', '#F4EFE1', '#1E362D', '#0E3B2E', '#8FA89B'], [$PF, 'Parisienne', 'Libre Baskerville', 'Lato'],
                'botanical', 'top', 'card', 'grad', ['orn' => 'corner-tropical', 'frame' => 'frame-thin', 'glow' => 'acc', 'glowOp' => 14]),
            self::d(19, 'Royal Blue', 'Modern', 'Classic', false, 'Classic royal blue, laurel crest, double gold frame and refined serif capitals.',
                ['#14286B', '#0C1A4A', '#F1EAD6', '#F3E5B0', '#D0AE5F', '#EED9A2', '#F6F2E8', '#1B2447', '#14286B', '#8E98B8'], [$PF, 'Allura', 'Libre Baskerville', 'Lato'],
                'crest', 'duo', 'frame', 'grad', ['orn' => 'corner-floral', 'frame' => 'frame-single', 'glow' => 'acc', 'glowOp' => 14]),
            self::d(20, 'Obsidian', 'Modern', 'Luxury', true, 'Textured black stone, a bronze bar and heavy masculine capitals.',
                ['#0D0C0B', '#1A1714', '#E8DFCF', '#EFE2C8', '#B8865B', '#D9B48C', '#131110', '#E8DFCF', '#D9B48C', '#8A8175'], ['Cinzel', $CG, $CG, 'Montserrat'],
                'slab', 'editorial', 'list', 'obsidian', ['grain' => 34, 'vignette' => true]),

            // ---------------------------------------------------------------- FLORAL
            self::d(21, 'English Garden', 'Floral', 'Romantic', false, 'Pastel blossoms tumbling from the corners across warm cream paper.',
                ['#FCF6EA', '#F5EAD6', '#5C4B3E', '#9A5B6B', '#B99765', '#E8D3B0', '#FDF8EF', '#5C4B3E', '#9A5B6B', '#A99787'], [$PF, 'Allura', 'Lora', 'Lato'],
                'garden', 'bloom', 'frame', 'paper', ['orn' => 'corner-blossom', 'div' => 'divider-floral']),
            self::d(22, 'Rose Garden', 'Floral', 'Romantic', false, 'Dusty pink roses in sprays and clusters on ivory, softly romantic.',
                ['#FBF3EE', '#F3E2DB', '#5B3F43', '#B0606F', '#C8A56A', '#E6CFA1', '#FEF9F5', '#5B3F43', '#B0606F', '#AE9294'], [$PF, 'Parisienne', $CG, 'Lato'],
                'spray', 'arch', 'card', 'grad', ['orn' => 'corner-rose', 'div' => 'divider-floral']),
            self::d(23, 'Peony Romance', 'Floral', 'Romantic', true, 'Enormous blush peonies billowing behind the names.',
                ['#FBEDEE', '#F4D8DC', '#5B3B40', '#C2657A', '#D4A56A', '#EBC9B0', '#FEF5F5', '#5B3B40', '#C2657A', '#B29196'], ['Playfair Display', 'Allura', 'Lora', 'Lato'],
                'bloom', 'circle', 'columns', 'grad', ['orn' => 'corner-peony', 'div' => 'divider-floral']),
            self::d(24, 'White Blossom', 'Floral', 'Elegant', false, 'Ghostly white blossoms on warm beige — very clean, very luxurious.',
                ['#E9DDCB', '#DCCBB2', '#4E4131', '#4E4131', '#A98B55', '#D4BE8C', '#F3EBDD', '#4E4131', '#6E5A3A', '#9C8C74'], [$CG, 'Allura', 'Lora', 'Montserrat'],
                'branch', 'top', 'card', 'grad', ['orn' => 'corner-blossom', 'div' => 'divider-dots']),
            self::d(25, 'Botanical Green', 'Floral', 'Modern', false, 'Fresh eucalyptus and olive branches down the side of a crisp white card.',
                ['#FFFFFF', '#F5F8F3', '#2B3A31', '#2F5A44', '#6F9078', '#B7CDBB', '#FFFFFF', '#2B3A31', '#2F5A44', '#8FA598'], ['DM Serif Display', 'Allura', 'Libre Baskerville', 'Inter'],
                'sideleaf', 'editorial', 'list', 'solid', ['orn' => 'corner-tropical', 'div' => 'divider-wave']),
            self::d(26, 'Wildflower', 'Floral', 'Playful', false, 'Tiny wildflowers scattered everywhere with a handwritten note in the middle.',
                ['#FBF6EC', '#F4EBD8', '#5A4636', '#C2607A', '#D6A24B', '#F0D7A0', '#FFFCF5', '#5A4636', '#C2607A', '#A99684'], ['Playfair Display', 'Caveat', 'Lora', 'Inter'],
                'wild', 'polaroid', 'frame', 'paper', ['orn' => 'corner-wildflower', 'div' => 'divider-dots']),
            self::d(27, 'Floral Arch', 'Floral', 'Romantic', true, 'A lush peony arch wrapping a portrait, with soft glowing light.',
                ['#FCF3EC', '#F5E1D6', '#5B3F3A', '#B25A66', '#C7A05C', '#E9D2A6', '#FEF8F3', '#5B3F3A', '#B25A66', '#AE948C'], [$PF, 'Parisienne', $CG, 'Lato'],
                'floralarch', 'duo', 'frame', 'grad', ['orn' => 'corner-peony', 'div' => 'divider-floral', 'glow' => 'acc2', 'glowOp' => 20]),
            self::d(28, 'Garden Gate', 'Floral', 'Romantic', false, 'A vine-covered garden gate opening onto the couple, on soft cream.',
                ['#FBF6EA', '#F3ECD7', '#4B5A45', '#4E7A57', '#B8975A', '#E4D3A8', '#FDFAF1', '#4B5A45', '#4E7A57', '#96A58E'], ['DM Serif Display', 'Allura', 'Lora', 'Lato'],
                'gate', 'circle', 'columns', 'grad', ['orn' => 'corner-blossom', 'div' => 'divider-floral']),
            self::d(29, 'Romantic Burgundy Floral', 'Floral', 'Luxury', true, 'Deep burgundy roses in a rich bouquet under gold names on champagne.',
                ['#F3E6D2', '#E8D2B0', '#4A2A2E', '#B58A3C', '#B58A3C', '#DDBE7C', '#FAF2E3', '#4A2A2E', '#7D263E', '#A98A84'], ['Bodoni Moda', 'Great Vibes', $CG, 'Montserrat'],
                'bouquet', 'bloom', 'frame', 'grad', ['orn' => 'corner-rose', 'div' => 'divider-floral', 'glow' => '#ffffff', 'glowOp' => 30]),
            self::d(30, 'Lavender Dream', 'Floral', 'Romantic', false, 'Twilight lavender fields with pollen and soft purple light.',
                ['#EEE7F6', '#D9CBEA', '#4A3A63', '#6B4E9B', '#9B7BC9', '#D5C4EE', '#FAF7FD', '#4A3A63', '#6B4E9B', '#A196B8'], [$PF, 'Allura', $CG, 'Lato'],
                'lavender', 'arch', 'card', 'grad', ['orn' => 'corner-wildflower', 'div' => 'divider-dots']),

            // ---------------------------------------------------------------- ISLAMIC / ELEGANT
            self::d(31, 'Islamic Gold', 'Islamic', 'Luxury', true, 'Ivory with a gold Islamic star pattern and a medallion cartouche.',
                ['#F8F2E4', '#F0E6CE', '#3F3521', '#7A5D1B', '#BC9A45', '#E4CF8E', '#FBF6E9', '#3F3521', '#7A5D1B', '#A2966F'], [$CG, 'Great Vibes', 'Amiri', 'Montserrat'],
                'cartouche', 'text', 'arch', 'islamic', ['orn' => 'corner-arabesque', 'frame' => 'frame-thin', 'patternOp' => 22, 'div' => 'divider-star']),
            self::d(32, 'Masjid Arch', 'Islamic', 'Elegant', false, 'A tall pointed mihrab arch in gold outline with the names inside.',
                ['#FAF3E3', '#F1E5C8', '#3F3521', '#6B4F14', '#B8943F', '#E0C982', '#FCF8EC', '#3F3521', '#6B4F14', '#A2966F'], [$CG, 'Allura', 'Amiri', 'Montserrat'],
                'mihrab', 'arch', 'arch', 'grad', ['orn' => 'corner-arabesque', 'div' => 'divider-star', 'glow' => 'acc2', 'glowOp' => 22]),
            self::d(33, 'Geometric Noor', 'Islamic', 'Modern', false, 'A giant gold star and pattern field with left-aligned modern type.',
                ['#FFFFFF', '#FAF6EC', '#3A3427', '#3A3427', '#C2A15A', '#E9D9AC', '#FFFFFF', '#3A3427', '#3A3427', '#9C9482'], ['Montserrat', $CG, $CG, 'Montserrat'],
                'noor', 'editorial', 'list', 'solid', ['div' => 'divider-double']),
            self::d(34, 'Emerald Islamic', 'Islamic', 'Luxury', true, 'Emerald green with interlocking gold geometry and a portrait medallion.',
                ['#0E3F34', '#092A23', '#EFE7CF', '#F1E4B4', '#CBAA5F', '#E8D598', '#0E3F34', '#EFE7CF', '#E8CE84', '#8EAA9E'], [$CG, 'Great Vibes', 'Amiri', 'Montserrat'],
                'medal', 'text', 'dark', 'islamic', ['orn' => 'corner-arabesque', 'frame' => 'frame-thin', 'patternOp' => 18, 'glow' => 'acc', 'glowOp' => 14, 'div' => 'divider-star', 'card2' => 'bg2']),
            self::d(35, 'Midnight Noor', 'Islamic', 'Luxury', true, 'A crescent moon over a navy hex-pattern night sky with tiny stars.',
                ['#0C1836', '#060E24', '#E8E2D2', '#F0DCA0', '#C9A45C', '#F4E2A8', '#0C1836', '#E8E2D2', '#E6CB80', '#8E9AB8'], [$CG, 'Great Vibes', 'Amiri', 'Montserrat'],
                'crescent', 'text', 'dark', 'hex', ['orn' => 'corner-arabesque', 'frame' => 'frame-thin', 'patternOp' => 14, 'glow' => 'acc', 'glowOp' => 12, 'div' => 'divider-star', 'card2' => 'bg2']),

            // ---------------------------------------------------------------- MINIMALIST
            self::d(36, 'Minimal Ivory', 'Minimalist', 'Minimal', false, 'Almost entirely white. One olive sprig, small type, an ocean of space.',
                ['#FDFBF6', '#F8F4EA', '#4A443A', '#3F3A32', '#B49F76', '#D9CBA7', '#FDFBF6', '#4A443A', '#3F3A32', '#A29A88'], [$CG, $CG, 'Lora', 'Montserrat'],
                'whitespace', 'circle', 'list', 'solid', ['orn' => 'corner-blossom', 'div' => 'divider-dots']),
            self::d(37, 'Beige Modern', 'Minimalist', 'Modern', false, 'Warm beige, an oval portrait and a single eucalyptus sprig.',
                ['#EADFCE', '#DFD0B8', '#4A3F30', '#4A3F30', '#9C7F55', '#CDB58D', '#F2EADC', '#4A3F30', '#4A3F30', '#9D8F79'], ['DM Serif Display', $CG, 'Lora', 'Inter'],
                'oval', 'top', 'columns', 'grad', ['div' => 'divider-dots']),
            self::d(38, 'Black Minimal', 'Minimalist', 'Editorial', false, 'Black, white type and one small gold dot. Pure editorial restraint.',
                ['#0B0B0B', '#111111', '#F2F0EA', '#F5F3EE', '#C8A24A', '#E4CB84', '#0B0B0B', '#EAE7DF', '#F5F3EE', '#8B8B8B'], ['Montserrat', $CG, $CG, 'Montserrat'],
                'blackmin', 'editorial', 'list', 'solid', ['grain' => 18]),
            self::d(39, 'Soft Grey', 'Minimalist', 'Modern', false, 'A white card floating on light grey with a fine line frame.',
                ['#DCDCDA', '#CFCFCC', '#3B3B39', '#3B3B39', '#A08C5E', '#D3C39A', '#FFFFFF', '#3B3B39', '#3B3B39', '#8E8E8A'], ['Montserrat', $CG, $CG, 'Montserrat'],
                'card', 'duo', 'card', 'grad', ['frame' => 'frame-thin', 'div' => 'divider-double']),
            self::d(40, 'Modern Monogram', 'Minimalist', 'Modern', false, 'Giant ghosted initials behind the names, with a slim frame.',
                ['#F6F1E8', '#EEE6D6', '#3C362C', '#3C362C', '#B08E4E', '#DCC48E', '#F6F1E8', '#3C362C', '#3C362C', '#9E9482'], [$PF, $PF, 'Lora', 'Montserrat'],
                'monogram', 'circle', 'frame', 'grad', ['frame' => 'frame-single', 'div' => 'divider-dots']),

            // ---------------------------------------------------------------- CONTEMPORARY / CREATIVE
            self::d(41, 'Editorial Magazine', 'Creative', 'Editorial', true, 'A full-page cover photo with a bold masthead and cover lines.',
                ['#1A1816', '#0E0D0C', '#FFFFFF', '#FFFFFF', '#D9B45B', '#F0D793', '#F7F3EA', '#2A2622', '#1A1816', '#8E877A'], ['Playfair Display', $PF, 'Lora', 'Inter'],
                'magazine', 'top', 'editorial', 'solid', []),
            self::d(42, 'Film Wedding', 'Creative', 'Cinematic', true, 'A cinematic still with grain, warm light leaks and letterbox bars.',
                ['#1A1512', '#0A0807', '#F3EBDD', '#FFFFFF', '#D9A45B', '#F0CE95', '#14110F', '#EFE6D6', '#F0CE95', '#8E8271'], [$CG, $CG, $CG, 'Inter'],
                'cinema', 'top', 'dark', 'solid', ['grain' => 20]),
            self::d(43, 'Polaroid Love', 'Creative', 'Playful', false, 'Three taped polaroids scattered on warm paper with handwritten captions.',
                ['#EFE3D0', '#E3D2B8', '#4B3F33', '#B0475E', '#C8A468', '#E6CB98', '#F6EEDF', '#4B3F33', '#B0475E', '#A39584'], ['Playfair Display', 'Caveat', 'Lora', 'Inter'],
                'polaroid', 'polaroid', 'columns', 'paper', ['orn' => 'corner-wildflower']),
            self::d(44, 'Scrapbook Romance', 'Creative', 'Playful', false, 'Layered paper, washi tape, pressed flowers, photos and handwriting.',
                ['#D9C4A2', '#C9B08A', '#4B3D2C', '#B0475E', '#8C6B3E', '#D9BE86', '#F7F0E2', '#4B3D2C', '#B0475E', '#9B8B70'], ['Playfair Display', 'Caveat', 'Lora', 'Inter'],
                'scrapbook', 'polaroid', 'card', 'paper', ['orn' => 'corner-blossom']),
            self::d(45, 'Vintage Letter', 'Creative', 'Vintage', false, 'Aged letter paper, faded roses and a red wax seal.',
                ['#EBDDBF', '#DCC79F', '#4A3A26', '#7A2A32', '#9C7A3E', '#D4B673', '#F2E7CC', '#4A3A26', '#7A2A32', '#9C8B6C'], ['Special Elite', 'Parisienne', $CG, 'Special Elite'],
                'letter', 'arch', 'frame', 'paper', ['orn' => 'corner-rose', 'frame' => 'frame-single', 'div' => 'divider-floral']),
            self::d(46, 'Newspaper Wedding', 'Creative', 'Editorial', false, 'Black and white broadsheet: masthead, big headline names and small event columns.',
                ['#EFEDE6', '#E4E1D6', '#161616', '#161616', '#161616', '#5A5A5A', '#EFEDE6', '#161616', '#161616', '#6A6A66'], ['Abril Fatface', 'Abril Fatface', 'Lora', 'Inter'],
                'newspaper', 'editorial', 'list', 'paper', []),
            self::d(47, 'Postcard Love', 'Creative', 'Vintage', false, 'A vintage postcard: stamp, postmark, dashed border and handwritten note.',
                ['#F4EAD5', '#EADBB9', '#3E3A45', '#B0413E', '#B0413E', '#D98A7E', '#F4EAD5', '#3E3A45', '#B0413E', '#A19782'], ['Abril Fatface', 'Caveat', 'Lora', 'Inter'],
                'postcard', 'duo', 'columns', 'paper', ['frame' => 'frame-postcard', 'div' => 'divider-wave']),
            self::d(48, 'Film Strip', 'Creative', 'Cinematic', false, 'Two strips of film frames on a dark background — a whole love story.',
                ['#161412', '#0B0A09', '#EDE6D6', '#F1E3BA', '#D9B45B', '#F0D793', '#161412', '#EDE6D6', '#F1E3BA', '#8B8375'], ['Playfair Display', 'Great Vibes', $CG, 'Inter'],
                'filmstrip', 'duo', 'dark', 'solid', ['grain' => 18]),
            self::d(49, 'Modern Abstract', 'Creative', 'Modern', false, 'Sand, terracotta and gold shapes in a contemporary graphic composition.',
                ['#EFE4D3', '#E6D6BE', '#4A3B2E', '#A8532F', '#C29A55', '#E3CE9B', '#F6EEE0', '#4A3B2E', '#A8532F', '#A0917C'], ['DM Serif Display', $CG, 'Lora', 'Inter'],
                'abstract', 'top', 'band', 'solid', ['div' => 'divider-dots']),
            self::d(50, 'Neekah Signature', 'Creative', 'Signature', true, 'The flagship: layered peonies and roses, arched portrait, gold frame, paper grain and soft light.',
                ['#F8F0E4', '#EFDFC8', '#4A3A36', '#9E4D5F', '#B8925A', '#E6CF9F', '#FBF5EA', '#4A3A36', '#9E4D5F', '#A48F84'], ['Playfair Display', 'Great Vibes', $CG, 'Montserrat'],
                'signature', 'bloom', 'frame', 'grad', ['orn' => 'corner-peony', 'frame' => 'frame-thin', 'glow' => 'acc2', 'glowOp' => 26, 'div' => 'divider-floral']),
        ];
    }
}
