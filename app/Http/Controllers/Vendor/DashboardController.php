<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vendor;
use App\Support\ImageSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $vendor = $request->user()->vendor;

        $stats = [
            'upcoming' => $vendor->bookings()->where('status', BookingStatus::Confirmed)->whereDate('event_date', '>=', today())->count(),
            'pending' => $vendor->bookings()->where('status', BookingStatus::PendingPayment)->count(),
            'completed' => $vendor->completed_bookings_count,
            'open_enquiries' => $vendor->enquiries()->where('status', EnquiryStatus::Open)->count(),
            'paid_total' => (float) Payment::query()
                ->where('status', PaymentStatus::Paid)
                ->whereHas('booking', fn ($query) => $query->whereBelongsTo($vendor))
                ->sum('amount'),
        ];

        $upcomingBookings = $vendor->bookings()
            ->with('user')
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
            ->whereDate('event_date', '>=', today())
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        return view('vendor.dashboard', [
            'vendor' => $vendor,
            'onboarding' => $this->onboarding($vendor),
            'props' => VueProps::for([
                'stats' => [
                    ['label' => __('props.vendor.majlis_akan_datang'), 'value' => $stats['upcoming'], 'href' => route('vendor.bookings.index', ['status' => 'confirmed'])],
                    ['label' => __('props.vendor.menunggu_deposit'), 'value' => $stats['pending'], 'href' => route('vendor.bookings.index', ['status' => 'pending_payment'])],
                    ['label' => __('props.vendor.enquiry_baru'), 'value' => $stats['open_enquiries'], 'href' => route('vendor.enquiries.index')],
                    ['label' => __('props.vendor.bayaran_diterima'), 'value' => 'RM'.number_format($stats['paid_total'], 2), 'hint' => __('props.units.weddings_completed', ['count' => $stats['completed']])],
                ],
                'upcoming' => $upcomingBookings->map(fn (Booking $booking): array => [
                    'reference' => $booking->reference,
                    'url' => route('vendor.bookings.show', $booking),
                    'day' => $booking->event_date->format('j'),
                    'month' => $booking->event_date->translatedFormat('M'),
                    'customer' => $booking->user->name,
                    'package_name' => $booking->package_name,
                    'status_label' => $booking->status->label(),
                    'status_tone' => $booking->status->tone(),
                ])->values(),
            ]),
        ]);
    }

    /**
     * The onboarding panel: what is still missing, why a couple cares about it,
     * and where it appears on the public page. Sizes come from ImageSettings so
     * the limits quoted here are the ones uploads are actually checked against.
     *
     * @return array<int, array<string, mixed>>
     */
    private function onboarding(Vendor $vendor, ImageSettings $images = new ImageSettings): array
    {
        $formats = $images->acceptedFormatsLabel().' · maksimum '.$images->effectiveUploadMegabytes().'MB';

        return [
            [
                'key' => 'profil',
                'label' => __('props.vendor.tagline_dan_penerangan'),
                'why' => 'Ayat pertama yang pengantin baca tentang anda. Ia muncul di bawah nama perniagaan, dan juga dalam hasil carian Google serta pratonton apabila pautan anda dikongsi di WhatsApp.',
                'specs' => ['Tagline: satu baris', 'Penerangan: 2 hingga 3 perenggan'],
                'preview' => 'profil',
                'action' => 'Tulis sekarang',
                'href' => route('vendor.profile.edit'),
                'done' => filled($vendor->tagline) && filled($vendor->description),
            ],
            [
                'key' => 'cover',
                'label' => __('props.vendor.gambar_muka_depan'),
                'why' => 'Gambar yang mewakili anda dalam senarai vendor dan grid carian — sebelum pengantin membuka profil anda. Tanpa gambar ini, kad anda hanya warna latar kosong berbanding pesaing yang ada gambar.',
                'specs' => [$formats, 'Landskap 1920 × 1080px', 'Satu gambar terbaik anda'],
                'preview' => 'portfolio',
                'action' => 'Muat naik gambar',
                'href' => route('vendor.profile.edit'),
                'done' => filled($vendor->cover_image),
            ],
            [
                'key' => 'portfolio',
                'label' => __('props.vendor.gambar_portfolio'),
                'why' => 'Bukti kerja anda. Lima gambar pertama mengisi grid besar di atas halaman awam; selebihnya dibuka apabila pengantin menekan "Tunjuk semua gambar". Anda boleh susun sendiri dan sembunyikan mana-mana gambar.',
                'specs' => [$formats, '1600 × 1200px atau lebih', 'Sekurang-kurangnya 3 gambar, 5 lebih baik'],
                'preview' => 'portfolio',
                'action' => 'Muat naik portfolio',
                'href' => route('vendor.portfolio.index'),
                'done' => $vendor->portfolioItems()->count() >= 3,
            ],
            [
                'key' => 'pakej',
                'label' => __('props.vendor.pakej_dan_gambar_pakej'),
                'why' => 'Pengantin memilih salah satu pakej ini semasa menempah. Setiap pakej boleh ada gambarnya sendiri, yang dipaparkan di atas kad pakej — pakej bergambar dipilih jauh lebih kerap daripada pakej berteks sahaja.',
                'specs' => [$formats, 'Landskap 1600 × 1200px', 'Satu gambar untuk setiap pakej'],
                'preview' => 'pakej',
                'action' => 'Tambah pakej',
                'href' => route('vendor.packages.index'),
                'done' => $vendor->packages()->exists(),
            ],
            [
                'key' => 'harga',
                'label' => __('props.vendor.harga_bermula'),
                'why' => 'Nombor "Dari RM…" pada kad tempah dan penapis harga di marketplace. Vendor tanpa harga tidak muncul apabila pengantin menapis ikut bajet mereka.',
                'specs' => ['Diambil automatik daripada pakej termurah anda'],
                'preview' => 'harga',
                'action' => 'Tetapkan harga',
                'href' => route('vendor.profile.edit'),
                'done' => (float) $vendor->price_from > 0,
            ],
            [
                'key' => 'kalendar',
                'label' => __('props.vendor.tarikh_tidak_tersedia'),
                'why' => 'Tandakan tarikh yang anda sudah penuh. Pengantin tidak boleh menempah tarikh itu, jadi anda tidak perlu menolak permintaan yang tidak boleh diterima.',
                'specs' => ['Boleh dikemas kini bila-bila masa'],
                'action' => 'Buka kalendar',
                'href' => route('vendor.availability.index'),
                'done' => $vendor->unavailableDates()->exists(),
            ],
        ];
    }
}
