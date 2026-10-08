@extends('layouts.auth')

@section('title', __('auth.login_title'))

@section('content')
    <h1 class="auth__title">{{ __('auth.login_title') }}</h1>
    <p class="auth__subtitle">{{ __('auth.login_subtitle') }}</p>

    @if (session('login_notice'))
        <x-alert type="info" icon="info">{{ session('login_notice') }}</x-alert>
    @endif

    @if (session('login_alert'))
        <x-alert>{{ session('login_alert') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <x-form-field name="university_id" :label="__('auth.university_id')" :hint="__('auth.university_id_hint')"
                      inputmode="numeric" pattern="[0-9]*" autocomplete="username" autofocus required />

        <x-password-input name="password" :label="__('auth.password_label')" required />

        <div class="auth__row">
            <label class="checkbox">
                <input type="checkbox" name="remember" value="1">
                {{ __('auth.remember_me') }}
            </label>
            {{-- The forgot-password page comes in part 8 --}}
            <a href="{{ Route::has('password.request') ? route('password.request') : '#' }}">{{ __('auth.forgot_password') }}</a>
        </div>

        <x-button type="submit" class="btn-block">{{ __('auth.login_button') }}</x-button>
    </form>
@endsection
