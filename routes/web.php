<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LocaleController;
use App\Http\Controllers\Shared\ThemeController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\IdeaBankController;
use App\Http\Controllers\Student\ProposalController;
use App\Http\Controllers\Student\TeamController;
use Illuminate\Support\Facades\Route;

/* ---------- Public ---------- */

Route::get('/', HomeController::class)->name('public.home');

Route::view('/about', 'public.about')->name('public.about');
Route::view('/guide', 'public.guide')->name('public.guide');

Route::get('/archive', [ArchiveController::class, 'index'])->name('public.archive.index');
// Before /archive/project/{entry}, so "competitions" is never read as an entry
Route::get('/archive/competitions', [ArchiveController::class, 'competitions'])->name('public.archive.competitions');
Route::get('/archive/faculty/{faculty}', [ArchiveController::class, 'faculty'])->name('public.archive.faculty');
Route::get('/archive/project/{entry}', [ArchiveController::class, 'show'])->name('public.archive.show');

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

    Route::post('/archive/project/{entry}/favorite', [ArchiveController::class, 'toggleFavorite'])
        ->name('public.archive.favorite');
    Route::put('/preferences/theme', [ThemeController::class, 'update'])->name('shared.theme.update');

    Route::get('/password/change', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');

    // Each role only reaches its own area. Dashboards are temporary until steps 04-06.
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
        Route::get('/section', [TeamController::class, 'section'])->name('section');

        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
        Route::post('/team/invitations/{member}/respond', [TeamController::class, 'respond'])->name('team.respond');
        Route::delete('/team/members/{member}', [TeamController::class, 'destroy'])->name('team.members.destroy');

        Route::get('/idea-bank', [IdeaBankController::class, 'index'])->name('idea-bank');
        Route::post('/idea-bank/{idea}/reserve', [IdeaBankController::class, 'reserve'])->name('idea-bank.reserve');

        Route::get('/proposal', [ProposalController::class, 'edit'])->name('proposal');
        Route::post('/proposal/check-similarity', [ProposalController::class, 'checkSimilarity'])->name('proposal.check');
        Route::post('/proposal', [ProposalController::class, 'store'])->name('proposal.store');

        // Batch B of step 04 fills these in
        Route::view('/project', 'shared.dashboard-placeholder')->name('project');
        Route::view('/submissions', 'shared.dashboard-placeholder')->name('submissions');
        Route::view('/meetings', 'shared.dashboard-placeholder')->name('meetings');
    });

    Route::middleware('role:supervisor')->prefix('supervisor')->name('supervisor.')->group(function () {
        Route::view('/dashboard', 'shared.dashboard-placeholder')->name('dashboard');
    });

    Route::middleware('role:coordinator')->prefix('coordinator')->name('coordinator.')->group(function () {
        Route::view('/dashboard', 'shared.dashboard-placeholder')->name('dashboard');
    });

    // System administration (IT department)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/dashboard', 'shared.dashboard-placeholder')->name('dashboard');
    });

    // No committee role: a supervisor sits on a committee through a defense session
});

/* ---------- Developer-only pages (never available in production) ---------- */

if (app()->isLocal()) {
    Route::view('/dev/components', 'dev.components');
    Route::get('/dev/flash', fn () => redirect('/dev/components')->with('success', 'رسالة نجاح جاية من السيرفر'));
    Route::get('/dev/error/{code}', fn (int $code) => abort($code))->whereIn('code', ['403', '404', '419', '429', '500']);
}
