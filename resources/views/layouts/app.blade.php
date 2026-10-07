<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="{{ auth()->user()?->theme === 'dark' ? 'dark' : 'light' }}">
<head>
    @include('layouts.partials.head')
</head>
<body>
    <div class="app">
        @include('layouts.partials.sidebar')

        {{-- Dark overlay behind the sidebar drawer on small screens --}}
        <div class="app__overlay" data-sidebar-close></div>

        <div class="app__main">
            @include('layouts.partials.topbar')

            <main class="app__content">
                @yield('content')
            </main>
        </div>
    </div>

    <x-toast />
    <x-confirm-dialog />
</body>
</html>
