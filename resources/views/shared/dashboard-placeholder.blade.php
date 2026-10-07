@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('content')
    <x-breadcrumb :items="[['label' => __('nav.dashboard')]]" />

    <x-page-header :title="__('dashboard.welcome', ['name' => auth()->user()->name])"
                   :subtitle="__('dashboard.temporary_note')" />

    <x-card>
        {{ __('dashboard.your_role') }}:
        <x-badge color="info">{{ __('roles.' . auth()->user()->role) }}</x-badge>
    </x-card>
@endsection
