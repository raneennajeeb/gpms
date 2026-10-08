@extends('layouts.auth')

@section('title', __('auth.forgot_title'))

@section('content')
    <h1 class="auth__title">{{ __('auth.forgot_title') }}</h1>
    <p class="auth__subtitle">{{ __('auth.forgot_subtitle') }}</p>

    @if (session('status'))
        <x-alert type="success" icon="info">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <x-form-field name="university_id" :label="__('auth.university_id')"
                      inputmode="numeric" pattern="[0-9]*" autocomplete="username" autofocus required />

        <x-button type="submit" class="btn-block">{{ __('auth.send_link') }}</x-button>
    </form>

    <p class="auth__secondary">
        <a href="{{ route('login') }}">{{ __('auth.back_to_login') }}</a>
    </p>
@endsection
