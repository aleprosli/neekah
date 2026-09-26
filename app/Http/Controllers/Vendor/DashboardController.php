<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\OnlineBookingState;
use App\Enums\PaymentStatus;
use App\Enums\VendorFeature;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vendor;
use App\Support\ContactSettings;
use App\Support\ImageSettings;
use App\Support\PhoneNumber;
use App\Support\VendorAnalytics;
use App\Support\VendorAvailability;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $vendor = $request->user()->vendor;

        if ($vendor->isAwaitingApproval()) {
            return $this->setup($request, $vendor);
        }

        $isPro = $vendor->isPro();
        $reach = (new VendorAnalytics($vendor))->totals(VendorAnalytics::TEASER_DAYS);
        $openEnquiries = $vendor->enquiries()->where('status', EnquiryStatus::Open)->count();
        $boostEnds = $vendor->boosts()->where('ends_at', '>', now())->max('ends_at');

        return view('vendor.dashboard', [
            'vendor' => $vendor,
            'props' => VueProps::for([
                'vendor' => [
                    'name' => $vendor->name,
                    'firstName' => Str::of($request->user()->name)->before(' ')->value(),
                    'plan' => $isPro ? 'pro' : 'basic',
                    'proUntil' => $vendor->pro_until?->translatedFormat('j M Y'),
                    'tier' => $vendor->tier->label(),
                    'publicUrl' => route('vendors.show', $vendor),
                    'proUrl' => route('vendor.pro.index'),
                    'recordBookingUrl' => $isPro ? route('vendor.bookings.create') : null,
                ],
                'actions' => $this->actions($vendor, $openEnquiries),
                'reach' => [
                    ['label' => __('props.vendor_dashboard.views'), 'value' => number_format($reach['profile_views']), 'icon' => '👀'],
                    ['label' => __('props.vendor_dashboard.whatsapp'), 'value' => number_format($reach['whatsapp_clicks']), 'icon' => '💬'],
                    ['label' => __('props.vendor_dashboard.phone'), 'value' => number_format($reach['phone_clicks']), 'icon' => '📞'],
                    ['label' => __('props.vendor_dashboard.tokens'), 'value' => number_format((int) $vendor->boost_tokens), 'icon' => '🚀', 'href' => route('vendor.boost.index')],
                ],
                'reachUrl' => route('vendor.pro.index'),
                'business' => $isPro ? $this->business($vendor, $openEnquiries) : null,
                'upcoming' => $isPro ? $this->upcoming($vendor) : [],
                'standing' => [
                    'tier' => $vendor->tier->label(),
                    'score' => number_format((float) $vendor->score, 1),
                    'rating' => $vendor->reviews_count ? number_format((float) $vendor->rating_avg, 1) : null,
                    'reviews' => $vendor->reviews_count,
                    'views30' => number_format((int) $vendor->views_30d),
                    'trending' => $vendor->trending_at !== null,
                    'boostedUntil' => $boostEnds ? Carbon::parse($boostEnds)->translatedFormat('j M, g:i A') : null,
                    'pointsUrl' => $isPro ? route('vendor.points.index') : null,
                ],
                'locked' => $isPro ? [] : collect([VendorFeature::Calendar, VendorFeature::Bookings, VendorFeature::Enquiries, VendorFeature::Points])
                    ->map(fn (VendorFeature $feature): array => ['label' => $feature->label(), 'description' => $feature->description()])
                    ->all(),
            ]),
        ]);
    }

    /**
     * What needs the vendor now, most pressing first. Only what applies is
     * listed; a complete profile is simply not mentioned again.
     *
     * @return array<int, array{key: string, icon: string, tone: string, title: string, body: string, cta: string, href: string}>
     */
    private function actions(Vendor $vendor, int $openEnquiries): array
    {
        $isPro = $vendor->isPro();
        $actions = [];
        $add = function (string $key, string $icon, string $tone, string $title, string $body, string $cta, string $href) use (&$actions): void {
            $actions[] = compact('key', 'icon', 'tone', 'title', 'body', 'cta', 'href');
        };

        if ($isPro && $vendor->pro_until->lessThan(now()->addDays(7))) {
            $add('pro_expiring', '⏳', 'amber', __('props.vendor_dashboard.pro_expiring_title', ['date' => $vendor->pro_until->translatedFormat('j M')]), __('props.vendor_dashboard.pro_expiring_body'), __('props.vendor_dashboard.pro_expiring_cta'), route('vendor.pro.index'));
        }

        if ($isPro) {
            $receipts = Payment::query()->where('status', PaymentStatus::AwaitingVerification)
                ->whereHas('booking', fn ($query) => $query->whereBelongsTo($vendor))->count();

            if ($receipts) {
                $add('receipts', '🧾', 'brand', __('props.vendor_dashboard.receipts_title', ['count' => $receipts]), __('props.vendor_dashboard.receipts_body'), __('props.vendor_dashboard.receipts_cta'), route('vendor.bookings.index', ['status' => 'pending_payment']));
            }

            if ($openEnquiries) {
                $add('enquiries', '💬', 'brand', __('props.vendor_dashboard.enquiries_title', ['count' => $openEnquiries]), __('props.vendor_dashboard.enquiries_body'), __('props.vendor_dashboard.enquiries_cta'), route('vendor.enquiries.index'));
            }

            $state = VendorAvailability::for($vendor)->onlineState();

            if ($state === OnlineBookingState::CalendarStale) {
                $add('calendar', '📅', 'amber', __('props.vendor_dashboard.calendar_title'), __('props.vendor_dashboard.calendar_body'), __('props.vendor_dashboard.calendar_cta'), route('vendor.availability.index'));
            } elseif (! $state->isOpen() && $state !== OnlineBookingState::GloballyOff) {
                $add('online', '🗓️', 'muted', __('props.vendor_dashboard.online_title'), $state->label(), __('props.vendor_dashboard.online_cta'), route('vendor.availability.index', ['tab' => 'tempahan']));
            }
        } elseif ($openEnquiries) {
            $add('enquiries_locked', '🔒', 'gold', __('props.vendor_dashboard.enquiries_locked_title', ['count' => $openEnquiries]), __('props.vendor_dashboard.enquiries_locked_body'), __('props.vendor_dashboard.upgrade_cta'), route('vendor.enquiries.index'));
        }

        foreach ($this->onboarding($vendor) as $step) {
            if (! $step['done']) {
                $add('setup_'.$step['key'], '✨', 'muted', $step['label'], $step['why'], $step['action'], $step['href']);
            }
        }

        if ($vendor->boost_tokens > 0 && ! $vendor->boosts()->where('ends_at', '>', now())->exists()) {
            $add('boost', '🚀', 'gold', __('props.vendor_dashboard.boost_title', ['count' => $vendor->boost_tokens]), __('props.vendor_dashboard.boost_body'), __('props.vendor_dashboard.boost_cta'), route('vendor.boost.index'));
        }

        return $actions;
    }

    /**
     * The Pro business numbers.
     *
     * @return array<int, array<string, mixed>>
     */
    private function business(Vendor $vendor, int $openEnquiries): array
    {
        return [
            ['label' => __('props.vendor.majlis_akan_datang'), 'value' => $vendor->bookings()->where('status', BookingStatus::Confirmed)->whereDate('event_date', '>=', today())->count(), 'href' => route('vendor.bookings.index', ['status' => 'confirmed'])],
            ['label' => __('props.vendor.menunggu_deposit'), 'value' => $vendor->bookings()->where('status', BookingStatus::PendingPayment)->count(), 'href' => route('vendor.bookings.index', ['status' => 'pending_payment'])],
            ['label' => __('props.vendor.enquiry_baru'), 'value' => $openEnquiries, 'href' => route('vendor.enquiries.index')],
            ['label' => __('props.vendor.bayaran_diterima'), 'value' => 'RM'.number_format((float) Payment::query()
                ->where('status', PaymentStatus::Paid)
                ->whereHas('booking', fn ($query) => $query->whereBelongsTo($vendor))
                ->sum('amount'), 2), 'hint' => __('props.units.weddings_completed', ['count' => $vendor->completed_bookings_count])],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function upcoming(Vendor $vendor): array
    {
        return $vendor->bookings()
            ->with('user')
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
            ->whereDate('event_date', '>=', today())
            ->orderBy('event_date')
            ->limit(5)
            ->get()
            ->map(fn (Booking $booking): array => [
                'reference' => $booking->reference,
                'url' => route('vendor.bookings.show', $booking),
                'day' => $booking->event_date->format('j'),
                'month' => $booking->event_date->translatedFormat('M'),
                'customer' => $booking->user->name,
                'package_name' => $booking->package_name,
                'status_label' => $booking->status->label(),
                'status_tone' => $booking->status->tone(),
            ])
            ->values()
            ->all();
    }

    /**
     * Before approval the dashboard is the whole vendor area: a short guide to
     * what happens next and one card per missing piece, each saving in place.
     */
    private function setup(Request $request, Vendor $vendor, ImageSettings $images = new ImageSettings): View
    {
        $steps = collect($this->onboarding($vendor))->keyBy('key');
        $contact = app(ContactSettings::class);
        $phone = PhoneNumber::normalise($contact->phone());

        return view('vendor.setup', [
            'vendor' => $vendor,
            'requestedStep' => $request->string('langkah')->toString(),
            'steps' => $steps,
            'doneCount' => $steps->where('done', true)->count(),
            'portfolio' => $vendor->portfolioItems()->orderBy('sort_order')->get(),
            'packages' => $vendor->packages()->orderBy('sort_order')->get(),
            'imageHint' => $images->uploadHint(),
            'support' => array_filter([
                'email' => $contact->email() ?: null,
                'whatsapp' => $contact->whatsappUrl(__('pages.vendor_setup.help_whatsapp_message', ['name' => $vendor->name])),
                'phone' => $phone ? ['label' => PhoneNumber::display($phone), 'url' => 'tel:+'.$phone] : null,
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
        ];
    }
}
