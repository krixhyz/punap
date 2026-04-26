<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use Illuminate\Support\Facades\Route;

// Guest routes (unauthenticated users only)
Route::middleware('guest')->group(function () {
    // Authentication routes
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');

    // Registration routes
    Route::get('register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:auth-register');

    // Password reset routes
    Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset-request')
        ->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:password-reset-submit')
        ->name('password.store');
});

// Protected routes (authenticated users only)
Route::middleware('auth')->group(function () {
    // Email verification routes
    Route::get('verify-email', [EmailVerificationController::class, 'showVerificationPrompt'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Password update route (from profile settings)
    Route::put('password', [PasswordUpdateController::class, 'update'])->name('password.update');

    // Logout route
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
