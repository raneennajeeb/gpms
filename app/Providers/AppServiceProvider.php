<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // One password policy for the whole system: change password, reset password, imports...
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
