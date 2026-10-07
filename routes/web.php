<?php

use App\Http\Controllers\Public\LocaleController;
use App\Http\Controllers\Shared\ThemeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('public.home');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', config('app.supported_locales'))
    ->name('public.locale.update');

Route::middleware('auth')->group(function () {
    Route::put('/preferences/theme', [ThemeController::class, 'update'])->name('shared.theme.update');
});

// Developer-only preview of the UI components (never available in production)
if (app()->isLocal()) {
    Route::view('/dev/components', 'dev.components');
}
