<?php

use App\Http\Controllers\Admin as AdminArea;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Customer as CustomerArea;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\InvitationAcceptanceController;
use App\Http\Controllers\InvitationPreviewController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\ReportVendorController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SiteTemplatePreviewController;
use App\Http\Controllers\Vendor as VendorArea;
use App\Http\Controllers\VendorComparisonController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

// Published invitations live on their own subdomain, e.g. ainahakim.neekah.test
Route::domain('{subdomain}.'.config('neekah.site_domain'))->group(function (): void {
    Route::get('/', PublicSiteController::class)->middleware('throttle:60,1')->name('sites.show');
    Route::post('/rsvp', [RsvpController::class, 'store'])->middleware('throttle:10,1')->name('sites.rsvp');
    Route::get('/kalendar.ics', CalendarController::class)->name('sites.calendar');
    Route::get('/preview.png', InvitationPreviewController::class)->name('sites.preview-image');
});

Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
Route::get('/compare', VendorComparisonController::class)->name('vendors.compare');
Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');

Route::get('/about', LandingController::class)->name('landing');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-vendors.xml', [SitemapController::class, 'vendors'])->name('sitemap.vendors');
Route::get('/sitemap-templates.xml', [SitemapController::class, 'templates'])->name('sitemap.templates');

Route::get('/kad-jemputan', [SiteTemplatePreviewController::class, 'index'])->name('sites.templates');
Route::get('/kad-jemputan/{template:slug}', [SiteTemplatePreviewController::class, 'show'])->name('sites.templates.show');
Route::get('/kad-jemputan/{template:slug}/preview.png', [SiteTemplatePreviewController::class, 'previewImage'])->name('sites.templates.image');

Route::get('/invitations/{invitation}', [InvitationAcceptanceController::class, 'show'])->name('invitations.show');

Route::redirect('/vendors', '/');
Route::redirect('/marketplace', '/');

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
    Route::get('/profile', [VendorArea\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [VendorArea\ProfileController::class, 'update'])->name('profile.update');
    Route::resource('packages', VendorArea\PackageController::class)->except('show');
    Route::get('/portfolio', [VendorArea\PortfolioItemController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio', [VendorArea\PortfolioItemController::class, 'store'])->name('portfolio.store');
    Route::delete('/portfolio/{item}', [VendorArea\PortfolioItemController::class, 'destroy'])->name('portfolio.destroy');
    Route::get('/availability', [VendorArea\UnavailableDateController::class, 'index'])->name('availability.index');
    Route::post('/availability', [VendorArea\UnavailableDateController::class, 'store'])->name('availability.store');
    Route::delete('/availability/{date}', [VendorArea\UnavailableDateController::class, 'destroy'])->name('availability.destroy');
    Route::get('/bookings', [VendorArea\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [VendorArea\BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [VendorArea\BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [VendorArea\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/complete', [VendorArea\BookingCompletionController::class, 'store'])->name('bookings.complete');
    Route::get('/points', [VendorArea\PointController::class, 'index'])->name('points.index');
    Route::get('/enquiries', [VendorArea\EnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}', [VendorArea\EnquiryController::class, 'show'])->name('enquiries.show');
    Route::put('/enquiries/{enquiry}', [VendorArea\EnquiryController::class, 'update'])->name('enquiries.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::put('/notifications', [NotificationController::class, 'update'])->name('notifications.read');
    Route::post('/impersonate/stop', [AdminArea\ImpersonationController::class, 'destroy'])->name('impersonate.stop');

    Route::post('/vendors/{vendor}/bookings', [BookingController::class, 'store'])->name('vendors.bookings.store');
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/payments/{payment}', [PaymentController::class, 'store'])->name('bookings.payments.store')->scopeBindings();
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

    Route::get('/checklist', [CustomerArea\WeddingTaskController::class, 'index'])->name('checklist.index');
    Route::post('/weddings/{wedding}/tasks', [CustomerArea\WeddingTaskController::class, 'store'])->name('weddings.tasks.store');
    Route::put('/weddings/{wedding}/tasks/{task}', [CustomerArea\WeddingTaskController::class, 'update'])->name('weddings.tasks.update');
    Route::delete('/weddings/{wedding}/tasks/{task}', [CustomerArea\WeddingTaskController::class, 'destroy'])->name('weddings.tasks.destroy');

    Route::get('/tetamu', [CustomerArea\WeddingGuestController::class, 'index'])->name('guests.index');
    Route::post('/weddings/{wedding}/guests', [CustomerArea\WeddingGuestController::class, 'store'])->name('weddings.guests.store');
    Route::put('/weddings/{wedding}/guests/{guest}', [CustomerArea\WeddingGuestController::class, 'update'])->name('weddings.guests.update');
    Route::delete('/weddings/{wedding}/guests/{guest}', [CustomerArea\WeddingGuestController::class, 'destroy'])->name('weddings.guests.destroy');
    Route::post('/weddings/{wedding}/guests/import', [CustomerArea\WeddingGuestImportController::class, 'store'])->name('weddings.guests.import');
    Route::post('/weddings/{wedding}/guests/{guest}/share', [CustomerArea\WeddingGuestShareController::class, 'store'])->name('weddings.guests.share');
    Route::delete('/weddings/{wedding}/guests/{guest}/share', [CustomerArea\WeddingGuestShareController::class, 'destroy'])->name('weddings.guests.share.destroy');
    Route::put('/weddings/{wedding}/rsvps/{rsvp}', [CustomerArea\WeddingRsvpController::class, 'update'])->name('weddings.rsvps.update');

    Route::get('/timeline', [CustomerArea\WeddingTimelineController::class, 'index'])->name('timeline.index');
    Route::post('/weddings/{wedding}/timeline', [CustomerArea\WeddingTimelineController::class, 'store'])->name('weddings.timeline.store');
    Route::put('/weddings/{wedding}/timeline/{item}', [CustomerArea\WeddingTimelineController::class, 'update'])->name('weddings.timeline.update');
    Route::delete('/weddings/{wedding}/timeline/{item}', [CustomerArea\WeddingTimelineController::class, 'destroy'])->name('weddings.timeline.destroy');

    Route::get('/kad', [CustomerArea\WeddingSiteController::class, 'edit'])->name('site.edit');
    Route::get('/kad/preview', [CustomerArea\WeddingSiteController::class, 'preview'])->name('site.preview');
    Route::put('/weddings/{wedding}/kad', [CustomerArea\WeddingSiteController::class, 'update'])->name('weddings.site.update');
    Route::post('/weddings/{wedding}/kad/galeri', [CustomerArea\WeddingSitePhotoController::class, 'store'])->name('weddings.site.photos.store');
    Route::delete('/weddings/{wedding}/kad/galeri/{photo}', [CustomerArea\WeddingSitePhotoController::class, 'destroy'])->name('weddings.site.photos.destroy');
    Route::put('/weddings/{wedding}/kad/publish', [CustomerArea\WeddingSiteController::class, 'publish'])->name('weddings.site.publish');

    Route::get('/budget', [CustomerArea\WeddingBudgetController::class, 'index'])->name('budget.index');
    Route::put('/weddings/{wedding}/budget', [CustomerArea\WeddingBudgetController::class, 'update'])->name('weddings.budget.update');

    Route::get('/enquiries', [CustomerArea\EnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}', [CustomerArea\EnquiryController::class, 'show'])->name('enquiries.show');
    Route::post('/vendors/{vendor}/enquiries', [CustomerArea\EnquiryController::class, 'store'])->name('vendors.enquiries.store');

    Route::get('/vendors/{vendor}/report', [ReportVendorController::class, 'create'])->name('vendors.report.create');
    Route::post('/vendors/{vendor}/report', [ReportVendorController::class, 'store'])->name('vendors.report.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminArea\DashboardController::class)->name('dashboard');
    Route::get('/analytics', AdminArea\AnalyticsController::class)->name('analytics');
    Route::get('/vendors', [AdminArea\VendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendor}', [AdminArea\VendorController::class, 'show'])->name('vendors.show');
    Route::post('/vendors/{vendor}/status', [AdminArea\VendorApprovalController::class, 'store'])->name('vendors.status');
    Route::put('/vendors/{vendor}/tier', [AdminArea\VendorTierController::class, 'update'])->name('vendors.tier');
    Route::get('/users', [AdminArea\UserController::class, 'index'])->name('users.index');
    Route::get('/categories', [AdminArea\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminArea\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminArea\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminArea\CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/bookings', [AdminArea\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [AdminArea\BookingController::class, 'show'])->name('bookings.show');
    Route::get('/transactions', [AdminArea\TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/users/{user}/impersonate', [AdminArea\ImpersonationController::class, 'store'])->name('users.impersonate');
    Route::get('/violations', [AdminArea\ViolationController::class, 'index'])->name('violations.index');
    Route::get('/violations/{violation}', [AdminArea\ViolationController::class, 'show'])->name('violations.show');
    Route::put('/violations/{violation}', [AdminArea\ViolationController::class, 'update'])->name('violations.update');
});
