<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\LocaleController;
use App\Http\Controllers\Shared\ThemeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('public.home');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', config('app.supported_locales'))
    ->name('public.locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::put('/preferences/theme', [ThemeController::class, 'update'])->name('shared.theme.update');
});

// Developer-only pages (never available in production)
if (app()->isLocal()) {
    Route::view('/dev/components', 'dev.components');
    Route::get('/dev/flash', fn () => redirect('/dev/components')->with('success', 'رسالة نجاح جاية من السيرفر'));
}
