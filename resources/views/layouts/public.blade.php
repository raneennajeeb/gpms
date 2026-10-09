<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="{{ auth()->user()?->theme === 'dark' ? 'dark' : 'light' }}">
<head>
    @include('layouts.partials.head')
</head>
<body>
    @include('layouts.partials.public-header')

    <main class="public-main @yield('main_class')">
        @yield('content')
    </main>

    @include('layouts.partials.public-footer')

    <x-toast />
    <x-confirm-dialog />
</body>
</html>
