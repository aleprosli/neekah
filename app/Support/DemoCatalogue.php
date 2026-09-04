<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Static placeholder marketplace data used until real vendor models exist.
 *
 * @phpstan-type Category array{slug: string, name: string, icon: string, examples: string}
 * @phpstan-type Package array{name: string, price: int, duration: string, features: array<int, string>}
 * @phpstan-type Vendor array{slug: string, name: string, category: string, city: string, state: string, rating: float, reviews: int, price_from: int, price_unit: string, tier: string, tone: string, tagline: string, description: string, highlights: array<int, string>, completed_bookings: int, response_rate: int, packages: array<int, Package>}
 */
class DemoCatalogue
{
    public const TIERS = ['New', 'Verified', 'Trusted', 'Top', 'Recommended'];

    public const STATES = [
        'Kedah', 'Pulau Pinang', 'Perak', 'Selangor', 'Kuala Lumpur', 'Negeri Sembilan',
        'Melaka', 'Johor', 'Pahang', 'Terengganu', 'Kelantan', 'Sabah', 'Sarawak',
    ];

    public const SORTS = [
        'recommended' => 'Disyorkan',
        'rating' => 'Rating tertinggi',
        'price_asc' => 'Harga: rendah ke tinggi',
        'price_desc' => 'Harga: tinggi ke rendah',
        'reviews' => 'Paling banyak review',
    ];

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return collect([
            ['slug' => 'catering', 'name' => 'Catering', 'icon' => '🍽️', 'examples' => 'Buffet, dome, food station'],
            ['slug' => 'pelamin', 'name' => 'Pelamin', 'icon' => '🌸', 'examples' => 'Basic, premium, custom'],
            ['slug' => 'decoration', 'name' => 'Decoration', 'icon' => '✨', 'examples' => 'Dewan, meja, entrance'],
            ['slug' => 'photography', 'name' => 'Photography', 'icon' => '📸', 'examples' => 'Wedding photography'],
            ['slug' => 'videography', 'name' => 'Videography', 'icon' => '🎥', 'examples' => 'Highlight, full video'],
            ['slug' => 'emcee', 'name' => 'Emcee', 'icon' => '🎤', 'examples' => 'Formal, casual, bilingual'],
            ['slug' => 'makeup', 'name' => 'Makeup', 'icon' => '💄', 'examples' => 'Bride, groom, family'],
            ['slug' => 'bridal', 'name' => 'Bridal', 'icon' => '👗', 'examples' => 'Dress, suit, fitting'],
            ['slug' => 'venue', 'name' => 'Venue', 'icon' => '🏛️', 'examples' => 'Hall, hotel, outdoor'],
            ['slug' => 'cake', 'name' => 'Wedding Cake', 'icon' => '🎂', 'examples' => 'Custom wedding cake'],
            ['slug' => 'entertainment', 'name' => 'Entertainment', 'icon' => '🎶', 'examples' => 'DJ, live band'],
            ['slug' => 'invitation', 'name' => 'Invitation', 'icon' => '💌', 'examples' => 'Digital & physical'],
        ]);
    }

    /**
     * @return Category|null
     */
    public function category(string $slug): ?array
    {
        return $this->categories()->firstWhere('slug', $slug);
    }

    /**
     * @return Collection<int, Vendor>
     */
    public function vendors(): Collection
    {
        return collect([
            $this->vendor('ABC Wedding Photography', 'photography', 'Alor Setar', 'Kedah', 4.9, 128, 1500, 'Recommended', 'from-rose-400 to-amber-300', 'Candid, natural light wedding photography.', 214, 98, [
                ['Basic Package', 1500, '6 jam', ['1 photographer', '300 edited photos', 'Online gallery']],
                ['Premium Package', 2500, '10 jam', ['2 photographers', '500 edited photos', 'Highlight video', 'Album 30 muka surat']],
            ]),
            $this->vendor('Dapur Warisan Catering', 'catering', 'Sungai Petani', 'Kedah', 4.8, 214, 18, 'Top', 'from-amber-500 to-orange-300', 'Masakan kampung tradisional untuk majlis besar.', 342, 95, [
                ['Buffet Standard', 18, 'per pax', ['5 lauk', 'Nasi putih & minyak', 'Air & pencuci mulut']],
                ['Buffet Premium', 28, 'per pax', ['8 lauk', 'Food station', 'Dome & kek', 'Crew berpakaian seragam']],
            ], 'pax'),
            $this->vendor('Seri Pelamin Studio', 'pelamin', 'Shah Alam', 'Selangor', 4.9, 96, 2800, 'Recommended', 'from-fuchsia-400 to-rose-300', 'Pelamin moden dengan bunga segar.', 156, 97, [
                ['Pelamin Basic', 2800, '1 hari', ['Backdrop 12 kaki', 'Bunga tiruan', 'Set sofa']],
                ['Pelamin Premium', 5500, '1 hari', ['Backdrop 20 kaki', 'Bunga segar', 'Aisle & pintu gerbang', 'Lighting']],
            ]),
            $this->vendor('Cinema Kasih Films', 'videography', 'Bangsar', 'Kuala Lumpur', 4.7, 73, 2200, 'Trusted', 'from-slate-700 to-slate-400', 'Cinematic wedding film dengan drone.', 88, 92, [
                ['Highlight Video', 2200, '8 jam', ['3-5 minit highlight', '1 videographer', 'Drone shot']],
                ['Full Documentary', 3800, '12 jam', ['Highlight + full video', '2 videographers', 'Drone', 'Same-day edit']],
            ]),
            $this->vendor('Glow by Nadia', 'makeup', 'Johor Bahru', 'Johor', 5.0, 152, 650, 'Top', 'from-pink-400 to-rose-200', 'Makeup pengantin airbrush tahan lama.', 260, 99, [
                ['Bride Only', 650, '1 sesi', ['Airbrush makeup', 'Hairdo', 'Touch-up kit']],
                ['Bride & Groom', 900, '1 sesi', ['Makeup pengantin lelaki & perempuan', 'Hairdo', 'Touch-up on site']],
            ]),
            $this->vendor('Dewan Seri Melati', 'venue', 'Ipoh', 'Perak', 4.6, 41, 4500, 'Verified', 'from-emerald-500 to-teal-300', 'Dewan berhawa dingin untuk 800 tetamu.', 52, 88, [
                ['Sewa Dewan', 4500, '1 hari', ['800 kerusi & meja', 'PA system', 'Parking 200 kereta']],
                ['Dewan + Katering', 22000, '1 hari', ['Dewan penuh', 'Buffet 500 pax', 'Pelamin standard']],
            ]),
            $this->vendor('Rasa Sayang Catering', 'catering', 'Seremban', 'Negeri Sembilan', 4.7, 189, 22, 'Trusted', 'from-orange-400 to-yellow-300', 'Katering Minang dan Melayu klasik.', 210, 93, [
                ['Buffet Klasik', 22, 'per pax', ['6 lauk', 'Rendang daging', 'Air & buah']],
            ], 'pax'),
            $this->vendor('Lensa Cahaya Studio', 'photography', 'Georgetown', 'Pulau Pinang', 4.8, 64, 1800, 'Trusted', 'from-sky-400 to-indigo-300', 'Fine-art wedding photography.', 97, 96, [
                ['Akad Package', 1800, '5 jam', ['1 photographer', '250 edited photos']],
                ['Full Day', 3200, '12 jam', ['2 photographers', '600 edited photos', 'Album']],
            ]),
            $this->vendor('MC Hafiz Rahman', 'emcee', 'Petaling Jaya', 'Selangor', 4.9, 117, 800, 'Top', 'from-violet-500 to-purple-300', 'Pengacara majlis dwibahasa yang ceria.', 180, 99, [
                ['Akad & Sanding', 800, '4 jam', ['Pengacara dwibahasa', 'Skrip custom', 'Koordinasi dengan vendor']],
            ]),
            $this->vendor('Bunga Rampai Deco', 'decoration', 'Kota Bharu', 'Kelantan', 4.5, 38, 1200, 'Verified', 'from-lime-400 to-emerald-300', 'Hiasan dewan dan meja bertema.', 44, 85, [
                ['Deco Meja', 1200, '1 hari', ['20 meja', 'Centerpiece', 'Kain meja']],
                ['Deco Dewan Penuh', 3500, '1 hari', ['Entrance', 'Meja & kerusi', 'Photo booth']],
            ]),
            $this->vendor('Butik Kasih Bridal', 'bridal', 'Melaka Tengah', 'Melaka', 4.8, 91, 1200, 'Trusted', 'from-rose-300 to-pink-200', 'Sewa baju pengantin dan songket.', 130, 94, [
                ['Sewa Set Sanding', 1200, '3 hari', ['Baju pengantin', 'Baju pengantin lelaki', 'Aksesori']],
                ['Set Akad + Sanding', 2000, '3 hari', ['2 set baju', 'Fitting 2 kali', 'Aksesori & veil']],
            ]),
            $this->vendor('Sweet Layers Cakery', 'cake', 'Kuantan', 'Pahang', 4.9, 58, 450, 'Trusted', 'from-yellow-300 to-amber-200', 'Kek kahwin custom 3 tingkat.', 76, 97, [
                ['Kek 3 Tingkat', 450, '1 kek', ['Fondant', 'Custom topper', 'Penghantaran']],
            ]),
            $this->vendor('Irama Malam Live Band', 'entertainment', 'Kuala Terengganu', 'Terengganu', 4.6, 47, 1500, 'Verified', 'from-indigo-500 to-blue-300', 'Live band akustik 4 orang.', 61, 90, [
                ['Akustik 2 Jam', 1500, '2 jam', ['4 pemuzik', 'PA system', '20 lagu']],
            ]),
            $this->vendor('Kad Kita Digital', 'invitation', 'Cyberjaya', 'Selangor', 4.9, 203, 120, 'Recommended', 'from-cyan-400 to-sky-300', 'Kad jemputan digital dengan RSVP.', 410, 99, [
                ['Kad Digital', 120, '1 kad', ['Design custom', 'RSVP form', 'Peta lokasi']],
                ['Digital + Cetak 200', 480, '1 set', ['Kad digital', '200 kad cetak', 'Sampul']],
            ]),
            $this->vendor('Studio Kita Photography', 'photography', 'Kota Kinabalu', 'Sabah', 4.4, 22, 1300, 'New', 'from-teal-400 to-cyan-200', 'Photographer muda, gaya dokumentari.', 18, 82, [
                ['Basic', 1300, '6 jam', ['1 photographer', '200 edited photos']],
            ]),
            $this->vendor('Pelamin Warisan Kuching', 'pelamin', 'Kuching', 'Sarawak', 4.7, 53, 2400, 'Verified', 'from-red-400 to-orange-300', 'Pelamin tradisional Melayu Sarawak.', 70, 91, [
                ['Pelamin Tradisional', 2400, '1 hari', ['Backdrop kayu ukir', 'Bunga tiruan', 'Set sofa']],
            ]),
            $this->vendor('Hotel Seri Bayu Ballroom', 'venue', 'Bayan Lepas', 'Pulau Pinang', 4.8, 112, 15000, 'Top', 'from-amber-600 to-yellow-300', 'Ballroom hotel 5 bintang untuk 600 tetamu.', 140, 96, [
                ['Ballroom + Buffet 500 pax', 15000, '1 hari', ['Ballroom', 'Buffet 500 pax', 'Bilik pengantin', 'Parking']],
            ]),
            $this->vendor('Makeup by Zulaikha', 'makeup', 'Kuala Lumpur', 'Kuala Lumpur', 4.7, 84, 550, 'Trusted', 'from-pink-500 to-fuchsia-300', 'Natural glam untuk pengantin.', 120, 95, [
                ['Bride Only', 550, '1 sesi', ['Makeup', 'Hairdo']],
                ['Bride + 2 Family', 950, '1 sesi', ['Makeup pengantin', '2 ahli keluarga', 'Touch-up']],
            ]),
        ]);
    }

    /**
     * @return Vendor|null
     */
    public function find(string $slug): ?array
    {
        return $this->vendors()->firstWhere('slug', $slug);
    }

    /**
     * Vendors to highlight on the landing page.
     *
     * @return Collection<int, Vendor>
     */
    public function featured(int $limit = 6): Collection
    {
        return $this->sort($this->vendors(), 'recommended')->take($limit)->values();
    }

    /**
     * @param  array{q?: string|null, category?: string|null, state?: string|null, min_price?: int|null, max_price?: int|null, min_rating?: float|null, tier?: string|null, sort?: string|null}  $filters
     * @return Collection<int, Vendor>
     */
    public function search(array $filters): Collection
    {
        $vendors = $this->vendors();

        if ($keyword = trim((string) ($filters['q'] ?? ''))) {
            $vendors = $vendors->filter(fn (array $vendor): bool => Str::contains(
                Str::lower($vendor['name'].' '.$vendor['tagline'].' '.$vendor['city'].' '.$vendor['state'].' '.$vendor['category']),
                Str::lower($keyword)
            ));
        }

        if ($category = $filters['category'] ?? null) {
            $vendors = $vendors->where('category', $category);
        }

        if ($state = $filters['state'] ?? null) {
            $vendors = $vendors->where('state', $state);
        }

        if (($minPrice = $filters['min_price'] ?? null) !== null) {
            $vendors = $vendors->where('price_from', '>=', (int) $minPrice);
        }

        if (($maxPrice = $filters['max_price'] ?? null) !== null) {
            $vendors = $vendors->where('price_from', '<=', (int) $maxPrice);
        }

        if (($minRating = $filters['min_rating'] ?? null) !== null) {
            $vendors = $vendors->where('rating', '>=', (float) $minRating);
        }

        if ($tier = $filters['tier'] ?? null) {
            $vendors = $vendors->where('tier', $tier);
        }

        return $this->sort($vendors, $filters['sort'] ?? 'recommended')->values();
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @return Collection<int, Vendor>
     */
    private function sort(Collection $vendors, string $sort): Collection
    {
        return match ($sort) {
            'rating' => $vendors->sortByDesc('rating'),
            'price_asc' => $vendors->sortBy('price_from'),
            'price_desc' => $vendors->sortByDesc('price_from'),
            'reviews' => $vendors->sortByDesc('reviews'),
            default => $vendors->sortByDesc(fn (array $vendor): float => $this->score($vendor)),
        };
    }

    /**
     * Approximation of the Recommended Vendor score weights from the kertas kerja.
     *
     * @param  Vendor  $vendor
     */
    private function score(array $vendor): float
    {
        $tierRank = array_search($vendor['tier'], self::TIERS, true) ?: 0;

        return ($vendor['rating'] / 5 * 30)
            + (min($vendor['completed_bookings'], 300) / 300 * 20)
            + ($vendor['response_rate'] / 100 * 15)
            + ($tierRank / 4 * 35);
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: string, 3: array<int, string>}>  $packages
     * @return Vendor
     */
    private function vendor(
        string $name,
        string $category,
        string $city,
        string $state,
        float $rating,
        int $reviews,
        int $priceFrom,
        string $tier,
        string $tone,
        string $tagline,
        int $completedBookings,
        int $responseRate,
        array $packages,
        string $priceUnit = 'pakej',
    ): array {
        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'category' => $category,
            'city' => $city,
            'state' => $state,
            'rating' => $rating,
            'reviews' => $reviews,
            'price_from' => $priceFrom,
            'price_unit' => $priceUnit,
            'tier' => $tier,
            'tone' => $tone,
            'tagline' => $tagline,
            'description' => $tagline.' Kami telah mengendalikan '.$completedBookings.' majlis melalui Neekah dengan response rate '.$responseRate.'%. Semua booking dan pembayaran direkod dalam platform untuk perlindungan anda.',
            'highlights' => ['Booking & bayaran melalui Neekah', 'Response rate '.$responseRate.'%', $completedBookings.' majlis selesai', 'Review daripada booking sebenar'],
            'completed_bookings' => $completedBookings,
            'response_rate' => $responseRate,
            'packages' => array_map(fn (array $package): array => [
                'name' => $package[0],
                'price' => $package[1],
                'duration' => $package[2],
                'features' => $package[3],
            ], $packages),
        ];
    }
}
