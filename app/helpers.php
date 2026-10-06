<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('settings', fn () => Setting::pluck('value', 'key')->all());

        return $settings[$key] ?? $default;
    }
}
