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

    {{-- The welcome tour runs once, then users.onboarding_done is set --}}
    @auth
        @if (auth()->user()->role === 'student' && ! auth()->user()->onboarding_done)
            @php
                $tourSteps = collect(__('student.tour'))
                    ->zip(['.sidebar', '.sidebar', '.stat-grid', '.dash-grid__main'])
                    ->map(fn ($pair) => $pair[0] + ['target' => $pair[1]]);
            @endphp

            <script type="application/json" id="tour-data"
                    data-url="{{ route('student.onboarding.done') }}"
                    data-labels="{{ json_encode([
                        'skip' => __('student.tour_skip'),
                        'next' => __('student.tour_next'),
                        'done' => __('student.tour_done'),
                    ], JSON_UNESCAPED_UNICODE) }}">{!! $tourSteps->toJson(JSON_UNESCAPED_UNICODE) !!}</script>
        @endif
    @endauth
</body>
</html>
