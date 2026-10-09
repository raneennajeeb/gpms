<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\LocaleController;
use App\Http\Controllers\Shared\ThemeController;
use Illuminate\Support\Facades\Route;

/* ---------- Public ---------- */

Route::view('/', 'public.home')->name('public.home');

Route::get('/archive', [ArchiveController::class, 'index'])->name('public.archive.index');
Route::get('/archive/faculty/{faculty}', [ArchiveController::class, 'faculty'])->name('public.archive.faculty');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', config('app.supported_locales'))
    ->name('public.locale.update');

/* ---------- Guests only ---------- */

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/password/forgot', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

/* ---------- Logged-in users ---------- */

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::put('/preferences/theme', [ThemeController::class, 'update'])->name('shared.theme.update');

    Route::get('/password/change', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');

    // Each role only reaches its own area. Dashboards are temporary until steps 04-06.
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::view('/dashboard', 'shared.dashboard-placeholder')->name('dashboard');
    });

    Route::middleware('role:supervisor')->prefix('supervisor')->name('supervisor.')->group(function () {
        Route::view('/dashboard', 'shared.dashboard-placeholder')->name('dashboard');
    });

    Route::middleware('role:coordinator')->prefix('coordinator')->name('coordinator.')->group(function () {
        Route::view('/dashboard', 'shared.dashboard-placeholder')->name('dashboard');
    });

    Route::middleware('role:committee')->prefix('committee')->name('committee.')->group(function () {
        Route::view('/dashboard', 'shared.dashboard-placeholder')->name('dashboard');
    });
});

/* ---------- Developer-only pages (never available in production) ---------- */

if (app()->isLocal()) {
    Route::view('/dev/components', 'dev.components');
    Route::get('/dev/flash', fn () => redirect('/dev/components')->with('success', 'رسالة نجاح جاية من السيرفر'));
    Route::get('/dev/error/{code}', fn (int $code) => abort($code))->whereIn('code', ['403', '404', '419', '429', '500']);
}
