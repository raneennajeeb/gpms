<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * TEMPORARY: written to make part 6 testable.
     * Replace with the real part 5b logic (throttling, the four messages, ...).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'university_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('university_id', $data['university_id'])->first();

        if (! $user) {
            return back()->with('login_alert', __('auth.not_registered'))->onlyInput('university_id');
        }

        if (! $user->is_active) {
            return back()->with('login_alert', __('auth.account_disabled'))->onlyInput('university_id');
        }

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => __('auth.wrong_password')])->onlyInput('university_id');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route($user->role . '.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.home');
    }
}
