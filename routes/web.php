<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VendorController::class, 'index'])->name('vendors.index');
Route::get('/vendors/{slug}', [VendorController::class, 'show'])->name('vendors.show');
Route::post('/vendors/{slug}/book', [VendorController::class, 'book'])->name('vendors.book');

Route::get('/about', LandingController::class)->name('landing');

Route::redirect('/vendors', '/');
Route::redirect('/marketplace', '/');
