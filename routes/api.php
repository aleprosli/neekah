<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
 * The Neekah Pro app: approved vendors on Neekah Pro run their calendar,
 * bookings, enquiries, boost and points from their phone. Tokens are Sanctum
 * personal access tokens; every call past login asks EnsureApiProVendor,
 * because a token outlives the plan it was issued under.
 */
Route::prefix('v1')->name('api.v1.')->middleware('api.locale')->group(function (): void {
    Route::get('/auth/config', [V1\AuthController::class, 'config'])->name('auth.config');
    Route::post('/auth/login', [V1\AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [V1\AuthController::class, 'me'])->name('me');

        Route::middleware(['api.pro', 'throttle:120,1'])->group(function (): void {
            Route::get('/dashboard', V1\DashboardController::class)->name('dashboard');

            Route::get('/bookings', [V1\BookingController::class, 'index'])->name('bookings.index');
            Route::get('/bookings/{booking}', [V1\BookingController::class, 'show'])->name('bookings.show');
            Route::post('/bookings/{booking}/complete', [V1\BookingController::class, 'complete'])->name('bookings.complete');
            Route::post('/bookings/{booking}/cancel', [V1\BookingController::class, 'cancel'])->name('bookings.cancel');
            Route::post('/bookings/{booking}/payments/{payment}/verify', [V1\BookingPaymentController::class, 'verify'])->scopeBindings()->name('bookings.payments.verify');
            Route::post('/bookings/{booking}/payments/{payment}/reject', [V1\BookingPaymentController::class, 'reject'])->scopeBindings()->name('bookings.payments.reject');
            Route::post('/bookings/{booking}/payments/{payment}/refunded', [V1\BookingPaymentController::class, 'refunded'])->scopeBindings()->name('bookings.payments.refunded');

            Route::get('/calendar', [V1\CalendarController::class, 'index'])->name('calendar.index');
            Route::post('/calendar/closed', [V1\CalendarController::class, 'close'])->name('calendar.close');
            Route::delete('/calendar/closed/{date}', [V1\CalendarController::class, 'reopen'])->name('calendar.reopen');
            Route::put('/online-booking', [V1\CalendarController::class, 'online'])->name('online-booking');

            Route::get('/enquiries', [V1\EnquiryController::class, 'index'])->name('enquiries.index');
            Route::get('/enquiries/{enquiry}', [V1\EnquiryController::class, 'show'])->name('enquiries.show');
            Route::post('/enquiries/{enquiry}/reply', [V1\EnquiryController::class, 'reply'])->name('enquiries.reply');

            Route::get('/boost', [V1\BoostController::class, 'index'])->name('boost.index');
            Route::post('/boost', [V1\BoostController::class, 'store'])->middleware('throttle:20,1')->name('boost.store');

            Route::get('/points', V1\PointController::class)->name('points');

            Route::get('/notifications', [V1\NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/read', [V1\NotificationController::class, 'read'])->name('notifications.read');
        });
    });
});
