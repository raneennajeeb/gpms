<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route is already behind the auth middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::in(['light', 'dark'])],
        ];
    }
}
