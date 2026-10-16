@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
@endphp

<header class="topbar">
    <button type="button" class="btn-icon topbar__menu" data-sidebar-open aria-controls="sidebar">
        <x-icon name="menu" />
        <span class="visually-hidden">{{ __('common.open_menu') }}</span>
    </button>

    {{-- Global search is deferred: put this back when the search is built in step 07
    <button type="button" class="topbar__search" data-search-open>
        <x-icon name="search" />
        <span>{{ __('common.search_placeholder') }}</span>
        <kbd>Ctrl K</kbd>
    </button>
    --}}

    <div class="topbar__actions">
        <a href="{{ Route::has('shared.notifications') ? route('shared.notifications') : '#' }}"
           class="btn-icon topbar__bell" title="{{ __('nav.notifications') }}">
            <x-icon name="bell" />
            @if ($unread > 0)
                <span class="topbar__count">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
            <span class="visually-hidden">{{ __('nav.notifications') }}</span>
        </a>

        @include('layouts.partials.preferences')

        <div class="user-menu">
            <button type="button" class="user-menu__button" data-dropdown-toggle aria-expanded="false" aria-label="{{ __('common.user_menu') }}">
                <span class="user-menu__avatar"><x-icon name="user" /></span>
                <span class="user-menu__name">
                    {{ $user->name }}
                    <small>{{ __('roles.' . $user->role) }}</small>
                </span>
                <x-icon name="chevron-down" :size="16" />
            </button>

            <div class="user-menu__dropdown" data-dropdown>
                <a href="{{ Route::has('shared.profile') ? route('shared.profile') : '#' }}">
                    <x-icon name="user" />
                    {{ __('nav.profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">
                        <x-icon name="log-out" class="icon-flip-rtl" />
                        {{ __('auth.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
