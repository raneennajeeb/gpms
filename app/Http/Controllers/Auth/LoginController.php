<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'university_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Keyed on the ID as well as the IP, so one attacker cannot lock out
        // a whole computer lab that shares a single address
        $key = Str::lower($data['university_id']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors(['university_id' => __('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($key),
                ])])
                ->onlyInput('university_id');
        }

        $user = User::where('university_id', $data['university_id'])->first();

        // One message for every kind of failure, so we never reveal
        // whether a university ID is registered
        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return back()
                ->withErrors(['university_id' => __('auth.failed')])
                ->onlyInput('university_id');
        }

        RateLimiter::clear($key);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($user->role . '.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.home');
    }
}
