<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer as CustomerArea;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Vendor as VendorArea;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');

Route::get('/about', LandingController::class)->name('landing');

Route::redirect('/vendors', '/');
Route::redirect('/marketplace', '/');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/vendor/register', [VendorArea\RegisterController::class, 'create'])->name('vendor.register');
    Route::post('/vendor/register', [VendorArea\RegisterController::class, 'store']);
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
    Route::get('/enquiries', [VendorArea\EnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}', [VendorArea\EnquiryController::class, 'show'])->name('enquiries.show');
    Route::put('/enquiries/{enquiry}', [VendorArea\EnquiryController::class, 'update'])->name('enquiries.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

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

    Route::get('/enquiries', [CustomerArea\EnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}', [CustomerArea\EnquiryController::class, 'show'])->name('enquiries.show');
    Route::post('/vendors/{vendor}/enquiries', [CustomerArea\EnquiryController::class, 'store'])->name('vendors.enquiries.store');
});
