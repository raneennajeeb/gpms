<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateThemeRequest;
use Illuminate\Http\Response;

class ThemeController extends Controller
{
    public function update(UpdateThemeRequest $request): Response
    {
        $request->user()->update($request->validated());

        return response()->noContent();
    }
}
