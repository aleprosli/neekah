<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentStatus;
use App\Enums\PriceUnit;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vendor;
use App\Support\ImageSettings;
use App\Support\VendorAnalytics;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $vendor = $request->user()->vendor;

        if ($vendor->isAwaitingApproval()) {
            return $this->setup($vendor);
        }

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
            'reach' => (new VendorAnalytics($vendor))->totals(VendorAnalytics::TEASER_DAYS),
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
     * Before approval the dashboard is the whole vendor area: a short guide to
     * what happens next and one card per missing piece, each saving in place.
     */
    private function setup(Vendor $vendor, ImageSettings $images = new ImageSettings): View
    {
        $steps = collect($this->onboarding($vendor))->keyBy('key');

        return view('vendor.setup', [
            'vendor' => $vendor,
            'steps' => $steps,
            'doneCount' => $steps->where('done', true)->count(),
            'portfolio' => $vendor->portfolioItems()->orderBy('sort_order')->get(),
            'packages' => $vendor->packages()->orderBy('sort_order')->get(),
            'priceUnits' => PriceUnit::cases(),
            'imageHint' => $images->uploadHint(),
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
        $formats = __('props.vendor_onboarding.formats', [
            'formats' => $images->acceptedFormatsLabel(),
            'size' => $images->effectiveUploadMegabytes(),
        ]);

        return [
            [
                'key' => 'profil',
                'label' => __('props.vendor.tagline_dan_penerangan'),
                'why' => __('props.vendor_onboarding.profil_why'),
                'specs' => [__('props.vendor_onboarding.profil_spec_1'), __('props.vendor_onboarding.profil_spec_2')],
                'preview' => 'profil',
                'action' => __('props.vendor_onboarding.profil_action'),
                'href' => route('vendor.profile.edit'),
                'done' => filled($vendor->tagline) && filled($vendor->description),
            ],
            [
                'key' => 'cover',
                'label' => __('props.vendor.gambar_muka_depan'),
                'why' => __('props.vendor_onboarding.cover_why'),
                'specs' => [$formats, __('props.vendor_onboarding.cover_spec_1'), __('props.vendor_onboarding.cover_spec_2')],
                'preview' => 'portfolio',
                'action' => __('props.vendor_onboarding.cover_action'),
                'href' => route('vendor.profile.edit'),
                'done' => filled($vendor->cover_image),
            ],
            [
                'key' => 'portfolio',
                'label' => __('props.vendor.gambar_portfolio'),
                'why' => __('props.vendor_onboarding.portfolio_why'),
                'specs' => [$formats, __('props.vendor_onboarding.portfolio_spec_1'), __('props.vendor_onboarding.portfolio_spec_2')],
                'preview' => 'portfolio',
                'action' => __('props.vendor_onboarding.portfolio_action'),
                'href' => route('vendor.portfolio.index'),
                'done' => $vendor->portfolioItems()->count() >= 3,
            ],
            [
                'key' => 'pakej',
                'label' => __('props.vendor.pakej_dan_gambar_pakej'),
                'why' => __('props.vendor_onboarding.pakej_why'),
                'specs' => [$formats, __('props.vendor_onboarding.pakej_spec_1'), __('props.vendor_onboarding.pakej_spec_2')],
                'preview' => 'pakej',
                'action' => __('props.vendor_onboarding.pakej_action'),
                'href' => route('vendor.packages.index'),
                'done' => $vendor->packages()->exists(),
            ],
            [
                'key' => 'harga',
                'label' => __('props.vendor.harga_bermula'),
                'why' => __('props.vendor_onboarding.harga_why'),
                'specs' => [__('props.vendor_onboarding.harga_spec_1')],
                'preview' => 'harga',
                'action' => __('props.vendor_onboarding.harga_action'),
                'href' => route('vendor.profile.edit'),
                'done' => (float) $vendor->price_from > 0,
            ],
        ];
    }
}
