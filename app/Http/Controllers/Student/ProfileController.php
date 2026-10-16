<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $student = $request->user()->load(['department', 'branch', 'section']);

        return view('student.profile', [
            'student' => $student,
            'team' => $student->currentTeam(),
            'locales' => config('app.supported_locales'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Name, university ID and department come from the university: read-only here
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'locale' => ['required', 'in:' . implode(',', config('app.supported_locales'))],
            'theme' => ['required', 'in:light,dark'],
        ], [], ['phone' => __('common.phone')]);

        $request->user()->update($data);

        return back()->with('success', __('student.profile_saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'current_password' => __('auth.current_password'),
            'password' => __('auth.new_password'),
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', __('auth.password_changed'));
    }

    /** The tour runs once; this records that it has been seen */
    public function finishOnboarding(Request $request): JsonResponse
    {
        $request->user()->update(['onboarding_done' => true]);

        return response()->json(['ok' => true]);
    }
}
