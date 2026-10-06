@extends('layouts.public')

@section('title', __('nav.home'))

@section('content')
    <div class="container">
        <h1>{{ __('common.app_name') }}</h1>
        <p class="text-muted">{{ __('common.app_name_en') }}</p>
    </div>
@endsection
