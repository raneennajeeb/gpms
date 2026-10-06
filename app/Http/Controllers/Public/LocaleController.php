<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        // Guests keep their choice in a cookie; users get it too so it survives logout
        return back()->withCookie(cookie()->forever('locale', $locale));
    }
}
