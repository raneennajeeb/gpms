<?php

use App\Http\Controllers\Public\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', config('app.supported_locales'))
    ->name('public.locale.update');

// Temporary page to preview the design system (removed in part 3)
Route::view('/design-test', 'design-test');
