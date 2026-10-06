<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="{{ auth()->user()?->theme === 'dark' ? 'dark' : 'light' }}">
<head>
    @include('layouts.partials.head')
</head>
<body>
    @include('layouts.partials.public-header')

    <main class="public-main">
        @yield('content')
    </main>

    @include('layouts.partials.public-footer')
</body>
</html>
