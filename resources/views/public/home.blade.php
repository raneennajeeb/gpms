@extends('layouts.public')

@section('title', __('nav.home'))
@section('main_class', 'public-main--flush')

@section('content')
    @php
        $features = ['team' => 'users', 'idea' => 'lightbulb', 'similarity' => 'search', 'stages' => 'layout-dashboard', 'submissions' => 'upload', 'defense' => 'calendar'];
        $journey = ['idea' => 'lightbulb', 'approval' => 'check-circle', 'team' => 'users', 'development' => 'code', 'submission' => 'upload', 'defense' => 'scale', 'evaluation' => 'star', 'archive' => 'archive'];
        $roles = ['student' => 'graduation-cap', 'supervisor' => 'user', 'department' => 'building', 'committee' => 'scale'];
    @endphp

    {{-- Hero --}}
    <section class="hero">
        <div class="container hero__inner">
            <div class="hero__text">
                <span class="hero__eyebrow">{{ __('common.university') }}</span>
                <h1 class="hero__title">{{ __('home.hero_title') }}</h1>
                <p class="hero__lead">{{ __('home.hero_text') }}</p>

                <div class="hero__actions">
                    @auth
                        <x-button :href="route(auth()->user()->role . '.dashboard')" icon="layout-dashboard">{{ __('home.cta_dashboard') }}</x-button>
                    @else
                        <x-button :href="route('login')" icon="log-in">{{ __('home.cta_login') }}</x-button>
                    @endauth
                    <x-button :href="route('public.archive.index')" variant="secondary" icon="archive">{{ __('home.cta_archive') }}</x-button>
                </div>
            </div>

            {{-- Decorative sample card; static on purpose (allowed in the hero only) --}}
            <div class="hero__card" aria-hidden="true">
                <x-badge color="info">{{ __('home.sample_status') }}</x-badge>
                <h3 class="hero__card-title">{{ __('home.sample_title') }}</h3>
                <p class="text-muted">{{ __('home.sample_stage') }}</p>
                <x-progress-ring :value="65" :label="__('home.sample_progress')" />
            </div>
        </div>
    </section>

    {{-- Live statistics --}}
    <section class="stats-strip">
        <div class="container stats-strip__grid">
            @foreach ($stats as $key => $value)
                @php $suffix = $key === 'completion' ? '%' : ''; @endphp
                <div class="stats-strip__item">
                    <span class="stats-strip__value" data-count-up="{{ $value }}" data-suffix="{{ $suffix }}">{{ $value }}{{ $suffix }}</span>
                    <span class="stats-strip__label">{{ __('home.stats.' . $key) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section class="home-section">
        <div class="container">
            <div class="section-heading">
                <h2>{{ __('home.features_title') }}</h2>
                <p>{{ __('home.features_text') }}</p>
            </div>

            <div class="feature-grid">
                @foreach ($features as $key => $icon)
                    <div class="feature-card">
                        <span class="feature-card__icon"><x-icon :name="$icon" :size="24" /></span>
                        <h3>{{ __("home.features.$key.title") }}</h3>
                        <p>{{ __("home.features.$key.text") }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="home-section home-section--alt">
        <div class="container">
            <div class="section-heading">
                <h2>{{ __('home.journey_title') }}</h2>
            </div>

            <ol class="journey">
                @foreach ($journey as $key => $icon)
                    <li class="journey__step">
                        <span class="journey__icon"><x-icon :name="$icon" :size="22" /></span>
                        <span class="journey__number">{{ $loop->iteration }}</span>
                        <span class="journey__label">{{ __("home.journey.$key") }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Roles --}}
    <section class="home-section">
        <div class="container">
            <div class="section-heading">
                <h2>{{ __('home.roles_title') }}</h2>
            </div>

            <div class="feature-grid">
                @foreach ($roles as $key => $icon)
                    <div class="feature-card">
                        <span class="feature-card__icon"><x-icon :name="$icon" :size="24" /></span>
                        <h3>{{ __("home.roles.$key.title") }}</h3>
                        <ul class="role-points">
                            @foreach (__("home.roles.$key.points") as $point)
                                <li><x-icon name="check-circle" :size="16" /> {{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Featured archive preview --}}
    @if ($featured->isNotEmpty())
        <section class="home-section home-section--alt">
            <div class="container">
                <div class="archive-section__head">
                    <h2 class="archive-section__title">{{ __('home.featured_title') }}</h2>
                    <a href="{{ route('public.archive.index') }}">{{ __('archive.view_all') }}</a>
                </div>

                <div class="archive-grid">
                    @foreach ($featured as $entry)
                        <x-archive-card :entry="$entry" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Call to action --}}
    @guest
        <section class="cta">
            <div class="container cta__inner">
                <h2>{{ __('home.cta_title') }}</h2>
                <p>{{ __('home.cta_text') }}</p>
                <x-button :href="route('login')" icon="log-in">{{ __('nav.login') }}</x-button>
            </div>
        </section>
    @endguest
@endsection
