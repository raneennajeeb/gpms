<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@hasSection('title')@yield('title') | @endif{{ __('common.app_short') }}</title>

@guest
    <script>
        // Apply the guest's saved theme before the page paints, to avoid a white flash
        try {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.dataset.theme = 'dark';
            }
        } catch (e) {}
    </script>
@endguest

<link rel="stylesheet" href="{{ asset('assets/css/variables.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/print.css') }}">

{{-- Page-specific scripts go before app.js, which reads them on DOMContentLoaded --}}
@stack('scripts')
<script src="{{ asset('assets/js/app.js') }}" defer></script>
