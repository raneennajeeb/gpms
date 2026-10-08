@extends('layouts.auth')

@section('title', __('auth.reset_title'))

@section('content')
    <h1 class="auth__title">{{ __('auth.reset_title') }}</h1>
    <p class="auth__subtitle">{{ __('auth.reset_subtitle') }}</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <x-password-input name="password" :label="__('auth.new_password')" autocomplete="new-password"
                          required autofocus data-password-strength-input />

        <x-password-strength />

        <x-password-input name="password_confirmation" :label="__('auth.confirm_password')" autocomplete="new-password" required />

        <x-button type="submit" class="btn-block">{{ __('auth.reset_button') }}</x-button>
    </form>
@endsection
