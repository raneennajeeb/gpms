<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="{{ auth()->user()?->theme === 'dark' ? 'dark' : 'light' }}">
<head>
    @include('layouts.partials.head')
</head>
<body>
    <div class="auth">
        <aside class="auth__panel">
            <a href="{{ route('public.home') }}">
                <x-logo :show-name="false" />
            </a>
            <h2 class="auth__university">{{ __('common.university') }}</h2>
            <p class="auth__system">{{ __('common.app_name') }}</p>
            <p class="auth__text">{{ __('auth.panel_text') }}</p>
        </aside>

        <main class="auth__main">
            <div class="auth__toolbar">
                @include('layouts.partials.preferences')
            </div>

            <div class="auth__card">
                @yield('content')
            </div>
        </main>
    </div>

    <x-toast />
</body>
</html>
