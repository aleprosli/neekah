<?php

namespace Database\Seeders;

use App\Actions\AwardVendorPoints;
use App\Actions\RecalculateVendorStats;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PointReason;
use App\Enums\PriceUnit;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Demo accounts, vendors, packages, bookings and reviews for local development.
 * All demo passwords are "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Neekah', 'email' => 'admin@neekah.test']);
        $couple = User::factory()->create(['name' => 'Aina Zulkifli', 'email' => 'aina@neekah.test']);

        $wedding = Wedding::factory()->for($couple)->create([
            'title' => 'Aina & Hakim',
            'event_date' => '2026-12-20',
            'city' => 'Alor Setar',
            'state' => 'Kedah',
            'budget' => 30000,
        ]);

        $categories = Category::all()->keyBy('slug');
        $vendors = collect();

        foreach ($this->vendors() as $data) {
            $packages = $data['packages'];
            $categorySlug = $data['category'];
            unset($data['packages'], $data['category']);

            $owner = User::factory()->vendor()->create([
                'name' => $data['name'],
                'email' => Str::slug($data['name']).'@vendor.neekah.test',
            ]);

            $vendor = Vendor::create($data + [
                'user_id' => $owner->id,
                'category_id' => $categories[$categorySlug]->id,
                'slug' => Str::slug($data['name']),
                'status' => VendorStatus::Approved,
                'approved_at' => now()->subMonths(3),
                'description' => $data['tagline'].' Kami telah mengendalikan '.$data['completed_bookings_count'].' majlis melalui Neekah. Semua booking dan pembayaran direkod dalam platform untuk perlindungan anda.',
            ]);

            foreach ($packages as $index => [$name, $price, $duration, $features]) {
                $vendor->packages()->create([
                    'name' => $name,
                    'price' => $price,
                    'duration' => $duration,
                    'features' => $features,
                    'sort_order' => $index,
                ]);
            }

            $vendors->push($vendor);
        }

        $this->seedPendingVendor($categories);

        $this->seedHistory($couple, $wedding, $vendors);

        $recalculate = app(RecalculateVendorStats::class);

        $vendors->each(function (Vendor $vendor) use ($recalculate): void {
            $recalculate->handle($vendor->refresh());
        });
    }

    /**
     * One vendor awaiting approval so the admin dashboard has something to act on.
     *
     * @param  Collection<string, Category>  $categories
     */
    private function seedPendingVendor($categories): void
    {
        $owner = User::factory()->vendor()->create([
            'name' => 'Nur Photography',
            'email' => 'nur-photography@vendor.neekah.test',
        ]);

        Vendor::create([
            'user_id' => $owner->id,
            'category_id' => $categories['photography']->id,
            'name' => 'Nur Photography',
            'slug' => 'nur-photography',
            'tagline' => 'Photographer baharu di Melaka, gaya moden.',
            'city' => 'Melaka Tengah',
            'state' => 'Melaka',
            'phone' => '012-999 8888',
            'whatsapp' => '60129998888',
            'status' => VendorStatus::Pending,
            'tier' => VendorTier::New,
            'cover_tone' => 'from-sky-400 to-indigo-300',
        ]);
    }

    /**
     * Give each vendor a few completed bookings with reviews so ratings are real, plus one live booking for the demo couple.
     *
     * @param  Collection<int, Vendor>  $vendors
     */
    private function seedHistory(User $couple, Wedding $wedding, $vendors): void
    {
        $reviewers = User::factory()->count(6)->create();
        $comments = [
            'Sangat profesional dan cepat respon. Hasil kerja melebihi jangkaan kami!',
            'Harga berbaloi, pakej jelas, dan booking melalui Neekah buat kami rasa selamat.',
            'Team datang awal, semua berjalan lancar. Highly recommended.',
            'Servis mesra dan sangat membantu sepanjang persiapan majlis.',
            'Kualiti terbaik untuk harga yang ditawarkan. Terima kasih!',
        ];

        foreach ($vendors as $vendor) {
            $package = $vendor->packages()->first();
            $award = app(AwardVendorPoints::class);

            // Scale the seeded history off the headline review count so the ranking
            // ladder is visible in the demo without hand-setting any tier.
            $declaredRating = (float) $vendor->rating_avg;
            $reviewCount = min(16, max(2, intdiv($vendor->reviews_count, 8)));
            $completedCount = min(40, max($reviewCount, intdiv($vendor->reviews_count, 4)));

            // Ratings are only 4 or 5, so the count of fives lands the average on the headline figure.
            $fives = (int) round(max(0, min(1, $declaredRating - 4)) * $reviewCount);

            for ($i = 0; $i < $completedCount; $i++) {
                $customer = $reviewers[$i % $reviewers->count()];
                $booking = Booking::factory()->completed()->create([
                    'user_id' => $customer->id,
                    'vendor_id' => $vendor->id,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'total_amount' => $package->price,
                    'deposit_amount' => round($package->price * Booking::DEPOSIT_RATE, 2),
                    'commission_amount' => round($package->price * Booking::COMMISSION_RATE / 100, 2),
                ]);

                foreach ([[PaymentType::Deposit, $booking->deposit_amount], [PaymentType::Balance, $booking->balanceAmount()]] as [$type, $amount]) {
                    Payment::factory()->paid()->create([
                        'booking_id' => $booking->id,
                        'type' => $type,
                        'amount' => $amount,
                        'paid_at' => $booking->completed_at->subDays(10),
                    ]);
                }

                $award->award($vendor, PointReason::PlatformBooking, $booking);
                $award->award($vendor, PointReason::DepositPaid, $booking);
                $award->award($vendor, PointReason::FullPayment, $booking);
                $award->award($vendor, PointReason::BookingCompleted, $booking);

                if ($i >= $reviewCount) {
                    continue;
                }

                $review = Review::factory()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $customer->id,
                    'vendor_id' => $vendor->id,
                    'rating' => $i < $fives ? 5 : 4,
                    'comment' => $comments[($i + $vendor->id) % count($comments)],
                ]);

                $award->award($vendor, PointReason::PositiveReview, $review);
            }
        }

        // A live, confirmed booking for the demo couple with the first vendor.
        $vendor = $vendors->first();
        $package = $vendor->packages()->orderByDesc('price')->first();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $couple->id,
            'vendor_id' => $vendor->id,
            'wedding_id' => $wedding->id,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'event_date' => $wedding->event_date,
            'total_amount' => $package->price,
            'deposit_amount' => round($package->price * Booking::DEPOSIT_RATE, 2),
            'commission_amount' => round($package->price * Booking::COMMISSION_RATE / 100, 2),
        ]);
        Payment::factory()->paid()->create(['booking_id' => $booking->id, 'type' => PaymentType::Deposit, 'amount' => $booking->deposit_amount]);
        Payment::factory()->create(['booking_id' => $booking->id, 'type' => PaymentType::Balance, 'amount' => $booking->balanceAmount(), 'status' => PaymentStatus::Pending]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function vendors(): array
    {
        $v = fn (string $name, string $category, string $city, string $state, float $rating, int $reviews, float $priceFrom, VendorTier $tier, string $tone, string $tagline, int $completed, int $responseRate, array $packages, PriceUnit $unit = PriceUnit::Package): array => [
            'name' => $name, 'category' => $category, 'city' => $city, 'state' => $state,
            'rating_avg' => $rating, 'reviews_count' => $reviews, 'price_from' => $priceFrom, 'price_unit' => $unit,
            'tier' => $tier, 'cover_tone' => $tone, 'tagline' => $tagline,
            'completed_bookings_count' => $completed, 'response_rate' => $responseRate, 'packages' => $packages,
            'phone' => '012-345 6789', 'whatsapp' => '60123456789',
        ];

        return [
            $v('ABC Wedding Photography', 'photography', 'Alor Setar', 'Kedah', 4.9, 128, 1500, VendorTier::Recommended, 'from-rose-400 to-amber-300', 'Candid, natural light wedding photography.', 214, 98, [
                ['Basic Package', 1500, '6 jam', ['1 photographer', '300 edited photos', 'Online gallery']],
                ['Premium Package', 2500, '10 jam', ['2 photographers', '500 edited photos', 'Highlight video', 'Album 30 muka surat']],
            ]),
            $v('Dapur Warisan Catering', 'catering', 'Sungai Petani', 'Kedah', 4.8, 214, 18, VendorTier::Top, 'from-amber-500 to-orange-300', 'Masakan kampung tradisional untuk majlis besar.', 342, 95, [
                ['Buffet Standard', 18, 'per pax', ['5 lauk', 'Nasi putih & minyak', 'Air & pencuci mulut']],
                ['Buffet Premium', 28, 'per pax', ['8 lauk', 'Food station', 'Dome & kek', 'Crew berpakaian seragam']],
            ], PriceUnit::Pax),
            $v('Seri Pelamin Studio', 'pelamin', 'Shah Alam', 'Selangor', 4.9, 96, 2800, VendorTier::Recommended, 'from-fuchsia-400 to-rose-300', 'Pelamin moden dengan bunga segar.', 156, 97, [
                ['Pelamin Basic', 2800, '1 hari', ['Backdrop 12 kaki', 'Bunga tiruan', 'Set sofa']],
                ['Pelamin Premium', 5500, '1 hari', ['Backdrop 20 kaki', 'Bunga segar', 'Aisle & pintu gerbang', 'Lighting']],
            ]),
            $v('Cinema Kasih Films', 'videography', 'Bangsar', 'Kuala Lumpur', 4.7, 73, 2200, VendorTier::Trusted, 'from-slate-700 to-slate-400', 'Cinematic wedding film dengan drone.', 88, 92, [
                ['Highlight Video', 2200, '8 jam', ['3-5 minit highlight', '1 videographer', 'Drone shot']],
                ['Full Documentary', 3800, '12 jam', ['Highlight + full video', '2 videographers', 'Drone', 'Same-day edit']],
            ]),
            $v('Glow by Nadia', 'makeup', 'Johor Bahru', 'Johor', 5.0, 152, 650, VendorTier::Top, 'from-pink-400 to-rose-200', 'Makeup pengantin airbrush tahan lama.', 260, 99, [
                ['Bride Only', 650, '1 sesi', ['Airbrush makeup', 'Hairdo', 'Touch-up kit']],
                ['Bride & Groom', 900, '1 sesi', ['Makeup pengantin lelaki & perempuan', 'Hairdo', 'Touch-up on site']],
            ]),
            $v('Dewan Seri Melati', 'venue', 'Ipoh', 'Perak', 4.6, 41, 4500, VendorTier::Verified, 'from-emerald-500 to-teal-300', 'Dewan berhawa dingin untuk 800 tetamu.', 52, 88, [
                ['Sewa Dewan', 4500, '1 hari', ['800 kerusi & meja', 'PA system', 'Parking 200 kereta']],
                ['Dewan + Katering', 22000, '1 hari', ['Dewan penuh', 'Buffet 500 pax', 'Pelamin standard']],
            ]),
            $v('Rasa Sayang Catering', 'catering', 'Seremban', 'Negeri Sembilan', 4.7, 189, 22, VendorTier::Trusted, 'from-orange-400 to-yellow-300', 'Katering Minang dan Melayu klasik.', 210, 93, [
                ['Buffet Klasik', 22, 'per pax', ['6 lauk', 'Rendang daging', 'Air & buah']],
            ], PriceUnit::Pax),
            $v('Lensa Cahaya Studio', 'photography', 'Georgetown', 'Pulau Pinang', 4.8, 64, 1800, VendorTier::Trusted, 'from-sky-400 to-indigo-300', 'Fine-art wedding photography.', 97, 96, [
                ['Akad Package', 1800, '5 jam', ['1 photographer', '250 edited photos']],
                ['Full Day', 3200, '12 jam', ['2 photographers', '600 edited photos', 'Album']],
            ]),
            $v('MC Hafiz Rahman', 'emcee', 'Petaling Jaya', 'Selangor', 4.9, 117, 800, VendorTier::Top, 'from-violet-500 to-purple-300', 'Pengacara majlis dwibahasa yang ceria.', 180, 99, [
                ['Akad & Sanding', 800, '4 jam', ['Pengacara dwibahasa', 'Skrip custom', 'Koordinasi dengan vendor']],
            ]),
            $v('Bunga Rampai Deco', 'decoration', 'Kota Bharu', 'Kelantan', 4.5, 38, 1200, VendorTier::Verified, 'from-lime-400 to-emerald-300', 'Hiasan dewan dan meja bertema.', 44, 85, [
                ['Deco Meja', 1200, '1 hari', ['20 meja', 'Centerpiece', 'Kain meja']],
                ['Deco Dewan Penuh', 3500, '1 hari', ['Entrance', 'Meja & kerusi', 'Photo booth']],
            ]),
            $v('Butik Kasih Bridal', 'bridal', 'Melaka Tengah', 'Melaka', 4.8, 91, 1200, VendorTier::Trusted, 'from-rose-300 to-pink-200', 'Sewa baju pengantin dan songket.', 130, 94, [
                ['Sewa Set Sanding', 1200, '3 hari', ['Baju pengantin', 'Baju pengantin lelaki', 'Aksesori']],
                ['Set Akad + Sanding', 2000, '3 hari', ['2 set baju', 'Fitting 2 kali', 'Aksesori & veil']],
            ]),
            $v('Sweet Layers Cakery', 'cake', 'Kuantan', 'Pahang', 4.9, 58, 450, VendorTier::Trusted, 'from-yellow-300 to-amber-200', 'Kek kahwin custom 3 tingkat.', 76, 97, [
                ['Kek 3 Tingkat', 450, '1 kek', ['Fondant', 'Custom topper', 'Penghantaran']],
            ]),
            $v('Irama Malam Live Band', 'entertainment', 'Kuala Terengganu', 'Terengganu', 4.6, 47, 1500, VendorTier::Verified, 'from-indigo-500 to-blue-300', 'Live band akustik 4 orang.', 61, 90, [
                ['Akustik 2 Jam', 1500, '2 jam', ['4 pemuzik', 'PA system', '20 lagu']],
            ]),
            $v('Kad Kita Digital', 'invitation', 'Cyberjaya', 'Selangor', 4.9, 203, 120, VendorTier::Recommended, 'from-cyan-400 to-sky-300', 'Kad jemputan digital dengan RSVP.', 410, 99, [
                ['Kad Digital', 120, '1 kad', ['Design custom', 'RSVP form', 'Peta lokasi']],
                ['Digital + Cetak 200', 480, '1 set', ['Kad digital', '200 kad cetak', 'Sampul']],
            ]),
            $v('Studio Kita Photography', 'photography', 'Kota Kinabalu', 'Sabah', 4.4, 22, 1300, VendorTier::New, 'from-teal-400 to-cyan-200', 'Photographer muda, gaya dokumentari.', 18, 82, [
                ['Basic', 1300, '6 jam', ['1 photographer', '200 edited photos']],
            ]),
            $v('Pelamin Warisan Kuching', 'pelamin', 'Kuching', 'Sarawak', 4.7, 53, 2400, VendorTier::Verified, 'from-red-400 to-orange-300', 'Pelamin tradisional Melayu Sarawak.', 70, 91, [
                ['Pelamin Tradisional', 2400, '1 hari', ['Backdrop kayu ukir', 'Bunga tiruan', 'Set sofa']],
            ]),
            $v('Hotel Seri Bayu Ballroom', 'venue', 'Bayan Lepas', 'Pulau Pinang', 4.8, 112, 15000, VendorTier::Top, 'from-amber-600 to-yellow-300', 'Ballroom hotel 5 bintang untuk 600 tetamu.', 140, 96, [
                ['Ballroom + Buffet 500 pax', 15000, '1 hari', ['Ballroom', 'Buffet 500 pax', 'Bilik pengantin', 'Parking']],
            ]),
            $v('Makeup by Zulaikha', 'makeup', 'Kuala Lumpur', 'Kuala Lumpur', 4.7, 84, 550, VendorTier::Trusted, 'from-pink-500 to-fuchsia-300', 'Natural glam untuk pengantin.', 120, 95, [
                ['Bride Only', 550, '1 sesi', ['Makeup', 'Hairdo']],
                ['Bride + 2 Family', 950, '1 sesi', ['Makeup pengantin', '2 ahli keluarga', 'Touch-up']],
            ]),
        ];
    }
}
