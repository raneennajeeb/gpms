@extends('layouts.auth')

@section('title', __('auth.change_password_title'))

@section('content')
    <h1 class="auth__title">{{ __('auth.change_password_title') }}</h1>
    <p class="auth__subtitle">{{ __('auth.change_password_subtitle') }}</p>

    @if (auth()->user()->must_change_password)
        <x-alert type="info" icon="info">{{ __('auth.change_password_required') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}" novalidate>
        @csrf
        @method('PUT')

        <x-password-input name="current_password" :label="__('auth.current_password')" required autofocus />

        <x-password-input name="password" :label="__('auth.new_password')" autocomplete="new-password"
                          required data-password-strength-input />

        <x-password-strength />

        <x-password-input name="password_confirmation" :label="__('auth.confirm_password')" autocomplete="new-password" required />

        <x-button type="submit" class="btn-block">{{ __('auth.save_password') }}</x-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="auth__secondary">
        @csrf
        <button type="submit" class="link-button">{{ __('auth.logout') }}</button>
    </form>
@endsection
