@php
    $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
@endphp

<header class="site-header">
    <div class="container site-header__inner">
        <a href="{{ route('public.home') }}" class="site-header__brand">
            <x-logo />
        </a>

        <nav class="site-nav" id="site-nav" aria-label="{{ __('common.main_menu') }}">
            @foreach (config('public_nav') as $key => $routeName)
                {{-- Pages built in step 03 have no route yet, so link to # until then --}}
                <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
                   class="site-nav__link {{ request()->routeIs($routeName) ? 'is-active' : '' }}">
                    {{ __('nav.' . $key) }}
                </a>
            @endforeach
        </nav>

        <div class="site-header__actions">
            <a href="{{ Route::has('public.archive') ? route('public.archive') : '#' }}" class="btn-icon" title="{{ __('common.search') }}">
                <x-icon name="search" />
                <span class="visually-hidden">{{ __('common.search') }}</span>
            </a>

            <form method="POST" action="{{ route('public.locale.update', $otherLocale) }}">
                @csrf
                <button type="submit" class="btn-icon btn-icon--text">
                    <x-icon name="globe" />
                    <span>{{ __('common.switch_language') }}</span>
                </button>
            </form>

            <button type="button" class="btn-icon" data-theme-toggle title="{{ __('common.toggle_theme') }}"
                @auth data-save-url="{{ route('shared.theme.update') }}" @endauth>
                <x-icon name="moon" class="icon-when-light" />
                <x-icon name="sun" class="icon-when-dark" />
                <span class="visually-hidden">{{ __('common.toggle_theme') }}</span>
            </button>

            @auth
                <span class="site-header__user">
                    <x-icon name="user" />
                    {{ auth()->user()->name }}
                </span>
            @else
                <a href="{{ Route::has('login') ? route('login') : '#' }}" class="btn btn-primary">
                    <x-icon name="log-in" class="icon-flip-rtl" />
                    <span>{{ __('nav.login') }}</span>
                </a>
            @endauth

            <button type="button" class="btn-icon site-header__menu-btn" data-nav-toggle aria-controls="site-nav" aria-expanded="false">
                <x-icon name="menu" />
                <span class="visually-hidden">{{ __('common.open_menu') }}</span>
            </button>
        </div>
    </div>
</header>
