<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="{{ auth()->user()?->theme === 'dark' ? 'dark' : 'light' }}">
<head>
    @include('layouts.partials.head')
</head>
<body>
    {{-- Kept simple on purpose: error pages must work even when other parts of the app fail --}}
    <main class="error-page">
        <div class="error-page__card">
            <span class="error-page__icon">
                <x-icon :name="$icon" :size="40" />
            </span>
            <p class="error-page__code">{{ $code }}</p>
            <h1 class="error-page__title">@yield('title')</h1>
            <p class="error-page__text">@yield('message')</p>

            <div class="error-page__actions">
                <x-button variant="secondary" :href="url()->previous()">{{ __('errors.back') }}</x-button>
                @auth
                    <x-button icon="layout-dashboard" :href="route(auth()->user()->role . '.dashboard')">{{ __('nav.dashboard') }}</x-button>
                @else
                    <x-button icon="house" :href="route('public.home')">{{ __('nav.home') }}</x-button>
                @endauth
            </div>
        </div>
    </main>
</body>
</html>
