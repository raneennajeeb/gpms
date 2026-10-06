@props(['showName' => true])

<span class="logo">
    <img src="{{ asset('assets/img/logo.png') }}" alt="{{ __('common.university') }}" class="logo__img" width="48" height="48">
    @if ($showName)
        <span class="logo__text">
            <span class="logo__name">{{ __('common.university') }}</span>
            <span class="logo__sub">{{ __('common.app_short') }}</span>
        </span>
    @endif
</span>
