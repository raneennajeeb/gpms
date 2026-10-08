<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        $user = User::where('email', $request->query('email'))->first();

        // Check the link before showing the form, so an expired link gets a friendly page right away
        if (! $user || ! Password::tokenExists($user, $token)) {
            return view('auth.reset-link-invalid');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse|Response
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLog::record('password_reset', $user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->view('auth.reset-link-invalid');
        }

        return redirect()->route('login')->with('status', __('auth.password_reset_done'));
    }
}
