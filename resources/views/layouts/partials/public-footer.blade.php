<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>
            <x-logo />
            <p class="site-footer__about">{{ __('common.footer_about') }}</p>
        </div>

        <div>
            <h4 class="site-footer__title">{{ __('common.quick_links') }}</h4>
            <ul class="site-footer__links">
                @foreach (config('public_nav') as $key => $routeName)
                    <li>
                        <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}">{{ __('nav.' . $key) }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="site-footer__bottom">
        <div class="container">
            © {{ date('Y') }} {{ __('common.app_name') }} · {{ __('common.university') }}
        </div>
    </div>
</footer>
