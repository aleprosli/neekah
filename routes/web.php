<?php

use App\Http\Controllers\Admin as AdminArea;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PhoneNumberController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CameraGuestController;
use App\Http\Controllers\Customer as CustomerArea;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\HerepayBookingWebhookController;
use App\Http\Controllers\HerepayBoostWebhookController;
use App\Http\Controllers\HerepayCameraWebhookController;
use App\Http\Controllers\HerepayWebhookController;
use App\Http\Controllers\InvitationAcceptanceController;
use App\Http\Controllers\InvitationPreviewController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\NfcCardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\ReportVendorController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SiteTemplatePreviewController;
use App\Http\Controllers\Vendor as VendorArea;
use App\Http\Controllers\VendorAvailabilityController;
use App\Http\Controllers\VendorComparisonController;
use App\Http\Controllers\VendorContactController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorReviewController;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Published invitations live on their own subdomain, e.g. ainahakim.neekah.test
Route::domain('{subdomain}.'.config('neekah.site_domain'))->middleware('locale')->group(function (): void {
    Route::get('/', PublicSiteController::class)->middleware('throttle:60,1')->name('sites.show');
    Route::post('/rsvp', [RsvpController::class, 'store'])->middleware('throttle:10,1')->name('sites.rsvp');
    Route::get('/kalendar.ics', CalendarController::class)->name('sites.calendar');
    Route::get('/preview.png', InvitationPreviewController::class)->name('sites.preview-image');
});

// A physical NFC card or printed QR. Registered once, outside the per-language
// sets: it is a redirect to a card, and cards are Malay only.
Route::get('/n/{uid}', NfcCardController::class)
    ->where('uid', '[A-Za-z0-9\-]+')
    ->middleware('throttle:60,1')
    ->name('nfc.tap');

/*
|--------------------------------------------------------------------------
| The site, once per language
|--------------------------------------------------------------------------
|
| Malay is served at the root and English under /en, as two sets of pages
| rather than one page that changes words, so both can be indexed and pointed
| at each other with hreflang. Every URL Google already holds is a Malay one,
| and those stay exactly where they are.
|
| The routes themselves are written once. The English set carries its code as a
| name prefix, which App\Routing\LocalisedUrlGenerator resolves, so route()
| keeps answering with the page in the language being served. Code comparing
| route names must go through Locales::baseRouteName — see the nav helpers.
|
*/

$site = function (): void {

    Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
    Route::get('/compare', VendorComparisonController::class)->name('vendors.compare');
    Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
    // Kamera Majlis: the guest page the QR opens. No account needed; the
    // unguessable token is the key.
    Route::get('/k/{album}', [CameraGuestController::class, 'show'])->middleware('throttle:60,1')->name('camera.show');
    Route::post('/k/{album}/masuk', [CameraGuestController::class, 'enter'])->middleware('throttle:20,1')->name('camera.enter');
    Route::post('/k/{album}/nama', [CameraGuestController::class, 'name'])->middleware('throttle:20,1')->name('camera.name');
    Route::post('/k/{album}/muat-naik', [CameraGuestController::class, 'reserve'])->middleware('throttle:120,1')->name('camera.upload.reserve');
    Route::put('/k/{album}/muat-naik/{media}/fail', [CameraGuestController::class, 'receive'])->middleware(['signed', 'throttle:120,1'])->name('camera.upload.file');
    Route::post('/k/{album}/muat-naik/{media}/selesai', [CameraGuestController::class, 'complete'])->middleware('throttle:120,1')->name('camera.upload.complete');
    Route::post('/k/{album}/muat-naik-biasa', [CameraGuestController::class, 'fallbackUpload'])->middleware('throttle:30,1')->name('camera.upload.fallback');
    Route::get('/k/{album}/galeri', [CameraGuestController::class, 'gallery'])->middleware('throttle:120,1')->name('camera.gallery');
    Route::delete('/k/{album}/media/{media}', [CameraGuestController::class, 'destroy'])->middleware('throttle:60,1')->name('camera.media.destroy');
    Route::post('/k/{album}/media/{media}/lapor', [CameraGuestController::class, 'report'])->middleware('throttle:10,60')->name('camera.media.report');
    Route::get('/vendors/{vendor}/ketersediaan', VendorAvailabilityController::class)->middleware('throttle:60,1')->name('vendors.availability');
    // Open to everyone, signed in or not, so the throttle is what stands between
    // a profile and someone with a script.
    Route::post('/vendors/{vendor}/reviews', [VendorReviewController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('vendors.reviews.store');

    Route::get('/about', LandingController::class)->name('landing');

    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

    Route::get('/kad-jemputan', [SiteTemplatePreviewController::class, 'index'])->name('sites.templates');
    Route::get('/kad-jemputan/{template:slug}', [SiteTemplatePreviewController::class, 'show'])->name('sites.templates.show');
    Route::get('/kad-jemputan/{template:slug}/preview.png', [SiteTemplatePreviewController::class, 'previewImage'])->name('sites.templates.image');

    Route::get('/invitations/{invitation}', [InvitationAcceptanceController::class, 'show'])->name('invitations.show');

    // The vendor list lives at the root. Old /vendors and /marketplace links move
    // there for good, keeping their filters (?category=…&state=…).
    Route::get('/vendors', fn (Request $request): RedirectResponse => redirect()->route('vendors.index', $request->query(), 301));
    Route::get('/marketplace', fn (Request $request): RedirectResponse => redirect()->route('vendors.index', $request->query(), 301));

    Route::middleware('guest')->group(function (): void {
        Route::get('/register', [RegisterController::class, 'create'])->name('register');
        Route::post('/register', [RegisterController::class, 'store']);
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
        Route::get('/vendor/register', [VendorArea\RegisterController::class, 'create'])->name('vendor.register');
        Route::post('/vendor/register', [VendorArea\RegisterController::class, 'store']);

        Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');

        Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
        Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
    });

    Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function (): void {
        Route::get('/', VendorArea\DashboardController::class)->name('dashboard');
        Route::put('/persediaan/profil', [VendorArea\SetupController::class, 'profile'])->name('setup.profile');
        Route::post('/persediaan/gambar-utama', [VendorArea\SetupController::class, 'cover'])->name('setup.cover');

        // Open to a vendor still waiting for approval too: the setup page uses
        // them. EnsureVendorHasFeature lets a pending vendor through.
        Route::middleware('vendor.feature:packages')->group(function (): void {
            Route::post('/packages', [VendorArea\PackageController::class, 'store'])->name('packages.store');
            Route::delete('/packages/{package}', [VendorArea\PackageController::class, 'destroy'])->name('packages.destroy');
        });
        Route::middleware('vendor.feature:portfolio')->group(function (): void {
            Route::post('/portfolio', [VendorArea\PortfolioItemController::class, 'store'])->name('portfolio.store');
            Route::delete('/portfolio/{item}', [VendorArea\PortfolioItemController::class, 'destroy'])->name('portfolio.destroy');
        });

        Route::middleware('vendor.approved')->group(function (): void {
            // Always open, whatever the plan: the profile, and the Pro page
            // where a closed feature is bought.
            Route::get('/profile', [VendorArea\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [VendorArea\ProfileController::class, 'update'])->name('profile.update');
            Route::get('/pro', [VendorArea\ProController::class, 'index'])->name('pro.index');
            Route::post('/pro/checkout', [VendorArea\ProController::class, 'checkout'])->middleware('throttle:10,1')->name('pro.checkout');
            Route::get('/pro/selesai', [VendorArea\ProController::class, 'done'])->name('pro.done');

            // Each feature is opened per plan under Admin → Ciri vendor, and
            // per vendor on the vendor's admin page (VendorFeature).
            Route::middleware('vendor.feature:packages')->group(function (): void {
                Route::resource('packages', VendorArea\PackageController::class)->except('show', 'store', 'destroy');
            });
            Route::middleware('vendor.feature:portfolio')->group(function (): void {
                Route::get('/portfolio', [VendorArea\PortfolioItemController::class, 'index'])->name('portfolio.index');
                Route::put('/portfolio/order', [VendorArea\PortfolioItemController::class, 'reorder'])->name('portfolio.reorder');
            });
            Route::middleware('vendor.feature:calendar')->group(function (): void {
                Route::get('/availability', [VendorArea\UnavailableDateController::class, 'index'])->name('availability.index');
                Route::post('/availability', [VendorArea\UnavailableDateController::class, 'store'])->name('availability.store');
                Route::delete('/availability/{date}', [VendorArea\UnavailableDateController::class, 'destroy'])->name('availability.destroy');
            });
            Route::middleware('vendor.feature:bookings')->group(function (): void {
                Route::get('/bookings', [VendorArea\BookingController::class, 'index'])->name('bookings.index');
                Route::get('/bookings/data', [VendorArea\BookingController::class, 'data'])->name('bookings.data');
                Route::get('/bookings/create', [VendorArea\BookingController::class, 'create'])->name('bookings.create');
                Route::post('/bookings', [VendorArea\BookingController::class, 'store'])->name('bookings.store');
                Route::get('/bookings/{booking}', [VendorArea\BookingController::class, 'show'])->name('bookings.show');
                Route::post('/bookings/{booking}/complete', [VendorArea\BookingCompletionController::class, 'store'])->name('bookings.complete');
                Route::post('/bookings/{booking}/batal', [VendorArea\BookingCancellationController::class, 'store'])->name('bookings.cancel');
                Route::post('/bookings/{booking}/payments/{payment}/dipulangkan', [VendorArea\BookingCancellationController::class, 'refunded'])->name('bookings.payments.refunded')->scopeBindings();
                Route::post('/bookings/{booking}/payments/{payment}/verify', [VendorArea\PaymentVerificationController::class, 'store'])->name('bookings.payments.verify')->scopeBindings();
                Route::delete('/bookings/{booking}/payments/{payment}/verify', [VendorArea\PaymentVerificationController::class, 'destroy'])->name('bookings.payments.reject')->scopeBindings();
            });
            Route::middleware('vendor.feature:reviews')->group(function (): void {
                Route::get('/reviews', [VendorArea\ReviewController::class, 'index'])->name('reviews.index');
                Route::post('/reviews', [VendorArea\ReviewController::class, 'store'])->name('reviews.store');
                Route::delete('/reviews/{review}', [VendorArea\ReviewController::class, 'destroy'])->name('reviews.destroy');
                Route::post('/reviews/{review}/reply', [VendorArea\ReviewController::class, 'reply'])->name('reviews.reply');
                Route::post('/reviews/{review}/report', [VendorArea\ReviewController::class, 'report'])->name('reviews.report');
            });
            Route::middleware('vendor.feature:points')->group(function (): void {
                Route::get('/points', [VendorArea\PointController::class, 'index'])->name('points.index');
            });
            Route::middleware('vendor.feature:boost')->group(function (): void {
                Route::get('/boost', [VendorArea\BoostController::class, 'index'])->name('boost.index');
                Route::post('/boost', [VendorArea\BoostController::class, 'store'])->middleware('throttle:20,1')->name('boost.store');
                Route::post('/boost/beli', [VendorArea\BoostController::class, 'checkout'])->middleware('throttle:10,1')->name('boost.checkout');
                Route::get('/boost/selesai', [VendorArea\BoostController::class, 'done'])->name('boost.done');
            });
            Route::middleware('vendor.feature:online_booking')->group(function (): void {
                Route::get('/tempahan-online', [VendorArea\BookingSettingsController::class, 'edit'])->name('booking-settings.edit');
                Route::put('/tempahan-online', [VendorArea\BookingSettingsController::class, 'update'])->name('booking-settings.update');
                Route::put('/tempahan-online/herepay', [VendorArea\BookingSettingsController::class, 'connect'])->middleware('throttle:5,1')->name('booking-settings.herepay.connect');
                Route::delete('/tempahan-online/herepay', [VendorArea\BookingSettingsController::class, 'disconnect'])->name('booking-settings.herepay.disconnect');
                Route::post('/tempahan-online/kalendar', [VendorArea\BookingSettingsController::class, 'confirmCalendar'])->name('booking-settings.calendar');
                Route::put('/tempahan-online/ical', [VendorArea\BookingSettingsController::class, 'connectIcal'])->middleware('throttle:10,1')->name('booking-settings.ical.connect');
                Route::post('/tempahan-online/ical/segerak', [VendorArea\BookingSettingsController::class, 'syncIcal'])->middleware('throttle:10,1')->name('booking-settings.ical.sync');
                Route::delete('/tempahan-online/ical', [VendorArea\BookingSettingsController::class, 'disconnectIcal'])->name('booking-settings.ical.disconnect');
            });
            Route::middleware('vendor.feature:enquiries')->group(function (): void {
                Route::get('/enquiries', [VendorArea\EnquiryController::class, 'index'])->name('enquiries.index');
                Route::get('/enquiries/{enquiry}', [VendorArea\EnquiryController::class, 'show'])->name('enquiries.show');
                Route::put('/enquiries/{enquiry}', [VendorArea\EnquiryController::class, 'update'])->name('enquiries.update');
            });
        });
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/akaun', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/akaun', [AccountController::class, 'update'])->name('account.update');
        Route::put('/akaun/kata-laluan', [AccountController::class, 'updatePassword'])->name('account.password');
        Route::get('/telefon', [PhoneNumberController::class, 'create'])->name('phone.create');
        Route::post('/telefon', [PhoneNumberController::class, 'store'])->name('phone.store');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::put('/notifications', [NotificationController::class, 'update'])->name('notifications.read');
        Route::get('/vendor/tukar-akaun', [VendorArea\AccountConversionController::class, 'create'])->name('vendor.convert');
        Route::post('/vendor/tukar-akaun', [VendorArea\AccountConversionController::class, 'store']);
        Route::post('/impersonate/stop', [AdminArea\ImpersonationController::class, 'destroy'])->name('impersonate.stop');

        // Shared: a vendor opening a booking link is sent on to their own view of it.
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

        // The couple's side of the app. A vendor or an admin typing one of
        // these addresses is sent to their own dashboard (EnsureUserIsCouple).
        Route::middleware('couple')->group(function (): void {
            Route::post('/vendors/{vendor}/bookings', [BookingController::class, 'store'])->name('vendors.bookings.store');
            Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
            Route::post('/bookings/{booking}/cancel', [CustomerArea\BookingCancellationController::class, 'store'])->name('bookings.cancel');
            Route::post('/bookings/{booking}/deposit', [BookingController::class, 'payDeposit'])->middleware('throttle:10,1')->name('bookings.deposit.pay');
            Route::get('/tempahan/{booking}/bayaran-selesai', [BookingController::class, 'paymentDone'])->name('bookings.payment.done');
            Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store'])->name('bookings.payments.store');
            Route::delete('/bookings/{booking}/payments/{payment}', [PaymentController::class, 'destroy'])->name('bookings.payments.destroy')->scopeBindings();
            Route::post('/bookings/{booking}/review', [CustomerArea\ReviewController::class, 'store'])->name('bookings.review.store');

            Route::get('/dashboard', CustomerArea\DashboardController::class)->name('dashboard');
            Route::get('/weddings/create', [CustomerArea\WeddingController::class, 'create'])->name('weddings.create');
            Route::post('/weddings', [CustomerArea\WeddingController::class, 'store'])->name('weddings.store');
            Route::get('/weddings/{wedding}/edit', [CustomerArea\WeddingController::class, 'edit'])->name('weddings.edit');
            Route::put('/weddings/{wedding}', [CustomerArea\WeddingController::class, 'update'])->name('weddings.update');
            Route::post('/weddings/{wedding}/invitations', [CustomerArea\WeddingInvitationController::class, 'store'])->name('weddings.invitations.store');
            Route::delete('/weddings/{wedding}/invitations/{invitation}', [CustomerArea\WeddingInvitationController::class, 'destroy'])->name('weddings.invitations.destroy');
            Route::delete('/weddings/{wedding}/members/{member}', [CustomerArea\WeddingMemberController::class, 'destroy'])->name('weddings.members.destroy');
            Route::post('/invitations/{invitation}', [InvitationAcceptanceController::class, 'store'])->name('invitations.accept');

            Route::get('/checklist', [CustomerArea\WeddingTaskController::class, 'index'])->middleware('wedding')->name('checklist.index');
            Route::post('/weddings/{wedding}/tasks', [CustomerArea\WeddingTaskController::class, 'store'])->name('weddings.tasks.store');
            Route::put('/weddings/{wedding}/tasks', [CustomerArea\WeddingTaskController::class, 'update'])->name('weddings.tasks.update');
            Route::delete('/weddings/{wedding}/tasks/{task}', [CustomerArea\WeddingTaskController::class, 'destroy'])->name('weddings.tasks.destroy');

            Route::get('/tetamu', [CustomerArea\WeddingGuestController::class, 'index'])->middleware('wedding')->name('guests.index');
            Route::post('/weddings/{wedding}/guests', [CustomerArea\WeddingGuestController::class, 'store'])->name('weddings.guests.store');
            Route::put('/weddings/{wedding}/guests/{guest}', [CustomerArea\WeddingGuestController::class, 'update'])->name('weddings.guests.update');
            Route::delete('/weddings/{wedding}/guests/{guest}', [CustomerArea\WeddingGuestController::class, 'destroy'])->name('weddings.guests.destroy');
            Route::post('/weddings/{wedding}/guests/import', [CustomerArea\WeddingGuestImportController::class, 'store'])->name('weddings.guests.import');
            Route::post('/weddings/{wedding}/guests/{guest}/share', [CustomerArea\WeddingGuestShareController::class, 'store'])->name('weddings.guests.share');
            Route::delete('/weddings/{wedding}/guests/{guest}/share', [CustomerArea\WeddingGuestShareController::class, 'destroy'])->name('weddings.guests.share.destroy');
            Route::put('/weddings/{wedding}/rsvps/{rsvp}', [CustomerArea\WeddingRsvpController::class, 'update'])->name('weddings.rsvps.update');

            Route::get('/timeline', [CustomerArea\WeddingTimelineController::class, 'index'])->middleware('wedding')->name('timeline.index');
            Route::post('/weddings/{wedding}/timeline', [CustomerArea\WeddingTimelineController::class, 'store'])->name('weddings.timeline.store');
            Route::put('/weddings/{wedding}/timeline/{item}', [CustomerArea\WeddingTimelineController::class, 'update'])->name('weddings.timeline.update');
            Route::delete('/weddings/{wedding}/timeline/{item}', [CustomerArea\WeddingTimelineController::class, 'destroy'])->name('weddings.timeline.destroy');

            Route::get('/kad', [CustomerArea\WeddingSiteController::class, 'edit'])->middleware('wedding')->name('site.edit');
            Route::get('/kad/preview', [CustomerArea\WeddingSiteController::class, 'preview'])->middleware('wedding')->name('site.preview');
            Route::get('/kad/alamat', [CustomerArea\WeddingSiteController::class, 'checkSubdomain'])->middleware(['wedding', 'throttle:60,1'])->name('site.subdomain');
            Route::get('/kad/reka-bentuk', [CustomerArea\WeddingSiteController::class, 'designs'])->middleware(['wedding', 'throttle:120,1'])->name('site.designs');
            Route::get('/kad/statistik', CustomerArea\WeddingSiteInsightsController::class)->middleware('wedding')->name('site.insights');
            Route::put('/weddings/{wedding}/kad', [CustomerArea\WeddingSiteController::class, 'update'])->name('weddings.site.update');
            Route::post('/weddings/{wedding}/kad/galeri', [CustomerArea\WeddingSitePhotoController::class, 'store'])->name('weddings.site.photos.store');
            Route::delete('/weddings/{wedding}/kad/galeri/{photo}', [CustomerArea\WeddingSitePhotoController::class, 'destroy'])->name('weddings.site.photos.destroy');
            Route::put('/weddings/{wedding}/kad/publish', [CustomerArea\WeddingSiteController::class, 'publish'])->name('weddings.site.publish');

            Route::get('/budget', [CustomerArea\WeddingBudgetController::class, 'index'])->middleware('wedding')->name('budget.index');
            Route::get('/kamera', [CustomerArea\CameraController::class, 'index'])->middleware('wedding')->name('camera.index');
            Route::post('/weddings/{wedding}/kamera/checkout', [CustomerArea\CameraController::class, 'checkout'])->middleware('throttle:10,1')->name('camera.checkout');
            Route::get('/kamera/bayaran-selesai', [CustomerArea\CameraController::class, 'done'])->name('camera.done');
            Route::put('/weddings/{wedding}/kamera', [CustomerArea\CameraController::class, 'update'])->name('camera.update');
            Route::post('/weddings/{wedding}/kamera/pautan', [CustomerArea\CameraController::class, 'rotate'])->name('camera.rotate');
            Route::put('/weddings/{wedding}/kamera/reka-bentuk', [CustomerArea\CameraController::class, 'design'])->name('camera.design');
            Route::get('/weddings/{wedding}/kamera/media', [CustomerArea\CameraController::class, 'media'])->name('camera.media');
            Route::post('/weddings/{wedding}/kamera/media/padam', [CustomerArea\CameraController::class, 'destroyMedia'])->name('camera.media.bulk');
            Route::post('/weddings/{wedding}/kamera/zip', [CustomerArea\CameraController::class, 'export'])->middleware('throttle:5,1')->name('camera.export');
            Route::get('/weddings/{wedding}/kamera/zip/{part}', [CustomerArea\CameraController::class, 'downloadExport'])->whereNumber('part')->name('camera.export.download');
            Route::put('/weddings/{wedding}/budget', [CustomerArea\WeddingBudgetController::class, 'update'])->name('weddings.budget.update');

            Route::get('/enquiries', [CustomerArea\EnquiryController::class, 'index'])->name('enquiries.index');
            Route::get('/enquiries/{enquiry}', [CustomerArea\EnquiryController::class, 'show'])->name('enquiries.show');
            Route::post('/vendors/{vendor}/enquiries', [CustomerArea\EnquiryController::class, 'store'])->name('vendors.enquiries.store');
        });

        // Signed-in only, like the number itself. WhatsApp goes through a
        // redirect that counts the tap; a tel: link cannot, so the page reports
        // that one with a beacon.
        Route::get('/vendors/{vendor}/whatsapp', [VendorContactController::class, 'whatsapp'])
            ->middleware('throttle:30,1')
            ->name('vendors.contact.whatsapp');
        Route::post('/vendors/{vendor}/telefon', [VendorContactController::class, 'phone'])
            ->middleware('throttle:30,1')
            ->name('vendors.contact.phone');

        Route::get('/vendors/{vendor}/report', [ReportVendorController::class, 'create'])->name('vendors.report.create');
        Route::post('/vendors/{vendor}/report', [ReportVendorController::class, 'store'])->name('vendors.report.store');
    });

    Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', AdminArea\DashboardController::class)->name('dashboard');
        Route::get('/analytics', AdminArea\AnalyticsController::class)->name('analytics');
        Route::get('/vendor-features', [AdminArea\VendorFeatureController::class, 'index'])->name('vendor-features.index');
        Route::put('/vendor-features', [AdminArea\VendorFeatureController::class, 'update'])->name('vendor-features.update');
        Route::get('/vendors', [AdminArea\VendorController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/data', [AdminArea\VendorController::class, 'data'])->name('vendors.data');
        Route::get('/vendors/export', [AdminArea\VendorController::class, 'export'])->name('vendors.export');
        Route::get('/vendors/{vendor}', [AdminArea\VendorController::class, 'show'])->name('vendors.show');
        Route::post('/vendors/status', [AdminArea\VendorApprovalController::class, 'bulk'])->name('vendors.bulk-status');
        Route::post('/vendors/{vendor}/status', [AdminArea\VendorApprovalController::class, 'store'])->name('vendors.status');
        Route::put('/vendors/{vendor}/tier', [AdminArea\VendorTierController::class, 'update'])->name('vendors.tier');
        Route::post('/vendors/{vendor}/pro', [AdminArea\VendorProController::class, 'store'])->name('vendors.pro');
        Route::post('/vendors/{vendor}/boost', [AdminArea\VendorBoostController::class, 'store'])->name('vendors.boost');
        Route::put('/vendors/{vendor}/features', [AdminArea\VendorFeatureOverrideController::class, 'update'])->name('vendors.features');
        Route::put('/users/{user}/kamera', [AdminArea\CameraController::class, 'updateForUser'])->name('users.camera');
        Route::get('/kamera', [AdminArea\CameraController::class, 'index'])->name('camera.index');
        Route::get('/kamera/data', [AdminArea\CameraController::class, 'data'])->name('camera.data');
        Route::post('/kamera', [AdminArea\CameraController::class, 'store'])->name('camera.store');
        Route::delete('/kamera/media/{media}', [AdminArea\CameraController::class, 'destroyMedia'])->name('camera.media.destroy');
        Route::post('/kamera/media/{media}/abaikan', [AdminArea\CameraController::class, 'dismissReport'])->name('camera.media.dismiss');
        Route::delete('/kamera/{album}', [AdminArea\CameraController::class, 'destroy'])->name('camera.destroy');
        Route::get('/reviews', [AdminArea\ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/data', [AdminArea\ReviewController::class, 'data'])->name('reviews.data');
        Route::post('/reviews', [AdminArea\ReviewController::class, 'store'])->name('reviews.store');
        Route::post('/reviews/{review}/hide', [AdminArea\ReviewController::class, 'hide'])->name('reviews.hide');
        Route::post('/reviews/{review}/restore', [AdminArea\ReviewController::class, 'restore'])->name('reviews.restore');
        Route::delete('/reviews/{review}', [AdminArea\ReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::get('/users', [AdminArea\UserController::class, 'index'])->name('users.index');
        Route::get('/users/data', [AdminArea\UserController::class, 'data'])->name('users.data');
        Route::get('/users/{user}', [AdminArea\UserController::class, 'show'])->name('users.show');
        Route::delete('/users/{user}', [AdminArea\UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/vendor', [AdminArea\UserVendorController::class, 'store'])->name('users.vendor.store');
        Route::delete('/users/{user}/vendor', [AdminArea\UserVendorController::class, 'destroy'])->name('users.vendor.destroy');
        Route::post('/users/{user}/deactivation', [AdminArea\UserDeactivationController::class, 'store'])->name('users.deactivate');
        Route::delete('/users/{user}/deactivation', [AdminArea\UserDeactivationController::class, 'destroy'])->name('users.reactivate');
        Route::get('/categories', [AdminArea\CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminArea\CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/order', [AdminArea\CategoryOrderController::class, 'update'])->name('categories.order');
        Route::put('/categories/{category}', [AdminArea\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminArea\CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/checklist', [AdminArea\ChecklistSectionController::class, 'index'])->name('checklist.index');
        Route::put('/checklist/order', [AdminArea\ChecklistOrderController::class, 'update'])->name('checklist.order');
        Route::post('/checklist/sections', [AdminArea\ChecklistSectionController::class, 'store'])->name('checklist.sections.store');
        Route::put('/checklist/sections/{section}', [AdminArea\ChecklistSectionController::class, 'update'])->name('checklist.sections.update');
        Route::delete('/checklist/sections/{section}', [AdminArea\ChecklistSectionController::class, 'destroy'])->name('checklist.sections.destroy');
        Route::post('/checklist/items', [AdminArea\ChecklistItemController::class, 'store'])->name('checklist.items.store');
        Route::put('/checklist/items/{item}', [AdminArea\ChecklistItemController::class, 'update'])->name('checklist.items.update');
        Route::delete('/checklist/items/{item}', [AdminArea\ChecklistItemController::class, 'destroy'])->name('checklist.items.destroy');
        Route::get('/muzik-kad', [AdminArea\CardMusicController::class, 'index'])->name('card-music.index');
        Route::post('/muzik-kad', [AdminArea\CardMusicController::class, 'store'])->name('card-music.store');
        Route::put('/muzik-kad/{track}', [AdminArea\CardMusicController::class, 'update'])->name('card-music.update');
        Route::delete('/muzik-kad/{track}', [AdminArea\CardMusicController::class, 'destroy'])->name('card-music.destroy');
        Route::get('/kad-nfc', [AdminArea\CardNfcController::class, 'index'])->name('card-nfc.index');
        Route::post('/kad-nfc', [AdminArea\CardNfcController::class, 'store'])->name('card-nfc.store');
        Route::put('/kad-nfc/{card}', [AdminArea\CardNfcController::class, 'update'])->name('card-nfc.update');
        Route::delete('/kad-nfc/{card}', [AdminArea\CardNfcController::class, 'destroy'])->name('card-nfc.destroy');
        Route::get('/announcements', [AdminArea\AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AdminArea\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::post('/announcements/test', [AdminArea\AnnouncementController::class, 'test'])->name('announcements.test');
        Route::get('/announcements/recipients', [AdminArea\AnnouncementController::class, 'recipients'])->name('announcements.recipients');
        Route::get('/announcements/{announcement}', [AdminArea\AnnouncementController::class, 'show'])->name('announcements.show');
        Route::get('/bookings', [AdminArea\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/data', [AdminArea\BookingController::class, 'data'])->name('bookings.data');
        Route::get('/bookings/{booking}', [AdminArea\BookingController::class, 'show'])->name('bookings.show');
        Route::get('/transactions', [AdminArea\TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/data', [AdminArea\TransactionController::class, 'data'])->name('transactions.data');
        Route::post('/users/{user}/impersonate', [AdminArea\ImpersonationController::class, 'store'])->name('users.impersonate');
        Route::get('/violations', [AdminArea\ViolationController::class, 'index'])->name('violations.index');
        Route::get('/violations/data', [AdminArea\ViolationController::class, 'data'])->name('violations.data');
        Route::get('/violations/{violation}', [AdminArea\ViolationController::class, 'show'])->name('violations.show');
        Route::put('/violations/{violation}', [AdminArea\ViolationController::class, 'update'])->name('violations.update');
        Route::resource('posts', AdminArea\PostController::class)->except('show');
        Route::post('/posts/images', AdminArea\PostImageController::class)->middleware('throttle:30,1')->name('posts.images.store');
        Route::get('/settings/{section?}', [AdminArea\SettingController::class, 'edit'])
            ->whereIn('section', AdminArea\SettingController::SECTIONS)
            ->name('settings.edit');
        Route::put('/settings', [AdminArea\SettingController::class, 'update'])->name('settings.update');
        Route::put('/settings/contact', [AdminArea\SettingController::class, 'updateContact'])->name('settings.contact');
        Route::put('/settings/seo', [AdminArea\SettingController::class, 'updateSeo'])->name('settings.seo');
        Route::put('/settings/telegram', [AdminArea\SettingController::class, 'updateTelegram'])->name('settings.telegram');
        Route::put('/settings/turnstile', [AdminArea\SettingController::class, 'updateTurnstile'])->name('settings.turnstile');
        Route::put('/settings/payments', [AdminArea\SettingController::class, 'updatePayments'])->name('settings.payments');
        Route::put('/settings/pro', [AdminArea\SettingController::class, 'updatePro'])->name('settings.pro');
        Route::put('/settings/herepay', [AdminArea\SettingController::class, 'updateHerepay'])->name('settings.herepay');
        Route::put('/settings/tempahan-online', [AdminArea\SettingController::class, 'updateOnlineBooking'])->name('settings.online-booking');
        Route::put('/settings/kamera', [AdminArea\SettingController::class, 'updateCamera'])->name('settings.camera');
        Route::put('/settings/boost', [AdminArea\SettingController::class, 'updateBoost'])->name('settings.boost');
    });

};

foreach (Locales::codes() as $locale) {
    Route::prefix(Locales::prefix($locale))
        ->name(Locales::routeName('', $locale))
        ->middleware('locale:'.$locale)
        ->group($site);
}

// Herepay calls this server to server. Once, outside the language sets, and
// outside CSRF (bootstrap/app.php): the signature is what it is checked by.
Route::post('/webhooks/herepay', HerepayWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.herepay');

// A booking deposit, paid on the vendor's own Herepay account. Its own route,
// so the route name in the signed callback URL says which kind it is.
// A Kamera Majlis purchase, on Neekah's own Herepay account.
Route::post('/webhooks/herepay/kamera', HerepayCameraWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.herepay.camera');

// A boost token pack, on Neekah's own Herepay account.
Route::post('/webhooks/herepay/boost', HerepayBoostWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.herepay.boost');

Route::post('/webhooks/herepay/tempahan', HerepayBookingWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.herepay.booking');

// One sitemap for the whole site, which lists both languages itself.
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->middleware('locale')->name('sitemap.index');
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog'])->middleware('locale')->name('sitemap.blog');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->middleware('locale')->name('sitemap.pages');
Route::get('/sitemap-vendors.xml', [SitemapController::class, 'vendors'])->middleware('locale')->name('sitemap.vendors');
Route::get('/sitemap-templates.xml', [SitemapController::class, 'templates'])->middleware('locale')->name('sitemap.templates');
