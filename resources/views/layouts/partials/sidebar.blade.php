@php
    $user = auth()->user();
    $links = collect(config('sidebar'))->filter(fn ($link) => in_array($user->role, $link['roles'], true));
@endphp

<aside class="sidebar" id="sidebar" aria-label="{{ __('common.main_menu') }}">
    <div class="sidebar__brand">
        <x-logo />
        <button type="button" class="btn-icon sidebar__close" data-sidebar-close>
            <x-icon name="x" />
            <span class="visually-hidden">{{ __('common.close_menu') }}</span>
        </button>
    </div>

    <nav class="sidebar__nav">
        @foreach ($links as $link)
            @php
                $routeName = str_replace(':role', $user->role, $link['route']);
                // A link stays active on its sub-pages too (student.project.edit...)
                $isActive = request()->routeIs($routeName, $routeName . '.*');
            @endphp
            <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
               class="sidebar__link {{ $isActive ? 'is-active' : '' }}"
               @if ($isActive) aria-current="page" @endif>
                <x-icon :name="$link['icon']" />
                <span>{{ __('nav.' . $link['key']) }}</span>
            </a>
        @endforeach
    </nav>
</aside>
