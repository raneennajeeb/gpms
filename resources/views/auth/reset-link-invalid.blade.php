@extends('layouts.auth')

@section('title', __('auth.reset_invalid_title'))

@section('content')
    <x-empty-state icon="alert-circle" :title="__('auth.reset_invalid_title')" :text="__('auth.reset_invalid_text')">
        <x-button :href="route('password.request')">{{ __('auth.request_new_link') }}</x-button>
    </x-empty-state>

    <p class="auth__secondary">
        <a href="{{ route('login') }}">{{ __('auth.back_to_login') }}</a>
    </p>
@endsection
