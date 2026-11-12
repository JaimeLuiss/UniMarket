<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Marketplace\ListingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/dashboard', DashboardController::class)->middleware('verified')->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/marketplace', [ListingController::class, 'index'])->name('marketplace.index');
    Route::get('/marketplace/create', [ListingController::class, 'create'])->name('marketplace.create');
    Route::post('/marketplace', [ListingController::class, 'store'])->name('marketplace.store');
    Route::get('/marketplace/{listing}/edit', [ListingController::class, 'edit'])->name('marketplace.edit');
    Route::put('/marketplace/{listing}', [ListingController::class, 'update'])->name('marketplace.update');
    Route::delete('/marketplace/{listing}', [ListingController::class, 'destroy'])->name('marketplace.destroy');
    Route::patch('/marketplace/{listing}/availability', [ListingController::class, 'updateAvailability'])->name('marketplace.availability');
    Route::delete('/marketplace/{listing}/images/{image}', [ListingController::class, 'destroyImage'])->whereNumber('image')->name('marketplace.images.destroy');
    Route::get('/marketplace/{listing}', [ListingController::class, 'show'])->name('marketplace.show');
    Route::get('/my-listings', [ListingController::class, 'mine'])->name('my-listings');
});

Route::get('/admin', AdminDashboardController::class)
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.dashboard');
