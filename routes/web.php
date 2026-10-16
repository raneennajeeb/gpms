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
use App\Http\Controllers\Student\InquiryController;
use App\Http\Controllers\Student\MeetingController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ProjectController as StudentProjectController;
use App\Http\Controllers\Student\ProposalController;
use App\Http\Controllers\Student\SubmissionController;
use App\Http\Controllers\Student\TaskController;
use App\Http\Controllers\Student\TeamChatController;
use App\Http\Controllers\Student\TeamController;
use App\Http\Controllers\Supervisor\ArchiveController as SupervisorArchiveController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Supervisor\DefenseSessionController;
use App\Http\Controllers\Supervisor\IdeaBankController as SupervisorIdeaBankController;
use App\Http\Controllers\Supervisor\IdeaQueueController;
use App\Http\Controllers\Supervisor\MeetingController as SupervisorMeetingController;
use App\Http\Controllers\Supervisor\ProjectReviewController;
use App\Http\Controllers\Supervisor\StudentController as SupervisorStudentController;
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

        Route::get('/project', [StudentProjectController::class, 'show'])->name('project');
        Route::put('/project/github', [StudentProjectController::class, 'updateGithub'])->name('project.github');

        Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions');
        Route::post('/submissions/{submission}/upload', [SubmissionController::class, 'upload'])->name('submissions.upload');
        Route::get('/submissions/versions/{version}/download', [SubmissionController::class, 'download'])->name('submissions.download');

        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');

        Route::get('/team-chat', [TeamChatController::class, 'index'])->name('team-chat');
        Route::post('/team-chat', [TeamChatController::class, 'store'])->name('team-chat.store');

        Route::get('/contact-supervisor', [InquiryController::class, 'index'])->name('contact-supervisor');
        Route::post('/contact-supervisor', [InquiryController::class, 'store'])->name('contact-supervisor.store');
        Route::get('/attachments/{attachment}/download', [InquiryController::class, 'download'])->name('attachments.download');

        Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings');
        Route::post('/meetings/request', [MeetingController::class, 'request'])->name('meetings.request');

        Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [StudentNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{id}/read', [StudentNotificationController::class, 'read'])->name('notifications.read');

        Route::get('/profile', [StudentProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [StudentProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/onboarding/done', [StudentProfileController::class, 'finishOnboarding'])->name('onboarding.done');
    });

    Route::middleware('role:supervisor')->prefix('supervisor')->name('supervisor.')->group(function () {
        Route::get('/dashboard', SupervisorDashboardController::class)->name('dashboard');

        Route::get('/students', [SupervisorStudentController::class, 'index'])->name('students');
        Route::post('/students', [SupervisorStudentController::class, 'store'])->name('students.store');
        Route::get('/students/import', [SupervisorStudentController::class, 'importForm'])->name('students.import');
        Route::get('/students/import/template', [SupervisorStudentController::class, 'template'])->name('students.import.template');
        Route::post('/students/import/preview', [SupervisorStudentController::class, 'importPreview'])->name('students.import.preview');
        Route::post('/students/import/confirm', [SupervisorStudentController::class, 'importConfirm'])->name('students.import.confirm');
        Route::get('/students/import/passwords', [SupervisorStudentController::class, 'downloadPasswords'])->name('students.import.passwords');

        Route::get('/ideas-queue', [IdeaQueueController::class, 'index'])->name('ideas-queue');
        Route::post('/projects/{project}/approve', [IdeaQueueController::class, 'approve'])->name('projects.approve');
        Route::post('/projects/{project}/request-changes', [IdeaQueueController::class, 'requestChanges'])->name('projects.request-changes');
        Route::post('/projects/{project}/reject', [IdeaQueueController::class, 'reject'])->name('projects.reject');

        Route::get('/idea-bank', [SupervisorIdeaBankController::class, 'index'])->name('idea-bank');
        Route::post('/idea-bank', [SupervisorIdeaBankController::class, 'store'])->name('idea-bank.store');
        Route::put('/idea-bank/{idea}', [SupervisorIdeaBankController::class, 'update'])->name('idea-bank.update');
        Route::delete('/idea-bank/{idea}', [SupervisorIdeaBankController::class, 'destroy'])->name('idea-bank.destroy');

        Route::get('/projects/{project}', [ProjectReviewController::class, 'show'])->name('projects.show');
        Route::post('/projects/{project}/comments', [ProjectReviewController::class, 'comment'])->name('projects.comment');
        Route::post('/projects/{project}/evaluation', [ProjectReviewController::class, 'evaluate'])->name('projects.evaluate');
        Route::post('/stages/{stage}/complete', [ProjectReviewController::class, 'completeStage'])->name('stages.complete');
        Route::post('/submissions/{submission}/review', [ProjectReviewController::class, 'reviewSubmission'])->name('submissions.review');
        Route::get('/versions/{version}/download', [ProjectReviewController::class, 'downloadVersion'])->name('versions.download');
        Route::get('/attachments/{attachment}/download', [ProjectReviewController::class, 'downloadAttachment'])->name('attachments.download');

        Route::get('/meetings', [SupervisorMeetingController::class, 'index'])->name('meetings');
        Route::post('/meetings', [SupervisorMeetingController::class, 'store'])->name('meetings.store');
        Route::post('/meetings/{meeting}/confirm', [SupervisorMeetingController::class, 'confirm'])->name('meetings.confirm');
        Route::post('/meetings/{meeting}/cancel', [SupervisorMeetingController::class, 'cancel'])->name('meetings.cancel');

        Route::get('/inquiries', [SupervisorMeetingController::class, 'inquiries'])->name('inquiries');
        Route::post('/inquiries/{inquiry}/reply', [SupervisorMeetingController::class, 'reply'])->name('inquiries.reply');

        Route::get('/defense-sessions', [DefenseSessionController::class, 'index'])->name('defense-sessions');
        Route::get('/defense-sessions/create', [DefenseSessionController::class, 'create'])->name('defense-sessions.create');
        Route::post('/defense-sessions', [DefenseSessionController::class, 'store'])->name('defense-sessions.store');
        Route::get('/defense-sessions/availability', [DefenseSessionController::class, 'availability'])->name('defense-sessions.availability');
        Route::get('/defense-sessions/{session}/edit', [DefenseSessionController::class, 'edit'])->name('defense-sessions.edit');
        Route::put('/defense-sessions/{session}', [DefenseSessionController::class, 'update'])->name('defense-sessions.update');
        Route::delete('/defense-sessions/{session}', [DefenseSessionController::class, 'destroy'])->name('defense-sessions.destroy');

        Route::get('/archive', [SupervisorArchiveController::class, 'mine'])->name('archive');
        Route::get('/archive/create', [SupervisorArchiveController::class, 'create'])->name('archive.create');
        Route::get('/archive/from-project/{project}', [SupervisorArchiveController::class, 'fromProject'])->name('archive.from-project');
        Route::post('/archive', [SupervisorArchiveController::class, 'store'])->name('archive.store');
        Route::get('/archive/{entry}/edit', [SupervisorArchiveController::class, 'edit'])->name('archive.edit');
        Route::put('/archive/{entry}', [SupervisorArchiveController::class, 'update'])->name('archive.update');
        Route::delete('/archive/{entry}', [SupervisorArchiveController::class, 'destroy'])->name('archive.destroy');
        Route::post('/archive/{entry}/featured', [SupervisorArchiveController::class, 'toggleFeatured'])->name('archive.featured');
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
