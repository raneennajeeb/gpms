@extends('layouts.public')

@section('title', __('pages.about_title'))

@section('content')
    <div class="container">
        <x-breadcrumb :items="[
            ['label' => __('nav.home'), 'url' => route('public.home')],
            ['label' => __('pages.about_title')],
        ]" />

        <x-page-header :title="__('pages.about_title')" :subtitle="__('pages.about_subtitle')" />

        <div class="prose">
            <section>
                <h2>{{ __('pages.what_title') }}</h2>
                <p>{{ __('pages.what_text') }}</p>
            </section>

            <section>
                <h2>{{ __('pages.problem_title') }}</h2>
                <p>{{ __('pages.problem_text') }}</p>
                <ul class="checklist">
                    @foreach (__('pages.problem_points') as $point)
                        <li><x-icon name="x" :size="16" /> {{ $point }}</li>
                    @endforeach
                </ul>
            </section>

            <section>
                <h2>{{ __('pages.how_title') }}</h2>
                <p>{{ __('pages.how_text') }}</p>
            </section>

            <section>
                <h2>{{ __('pages.who_title') }}</h2>
                <ul class="checklist">
                    @foreach (__('pages.who_points') as $point)
                        <li><x-icon name="check-circle" :size="16" /> {{ $point }}</li>
                    @endforeach
                </ul>
            </section>

            <section>
                <h2>{{ __('pages.stages_title') }}</h2>
                <p>{{ __('pages.stages_text') }}</p>

                <ol class="journey">
                    @foreach (['idea' => 'lightbulb', 'approval' => 'check-circle', 'team' => 'users', 'development' => 'code', 'submission' => 'upload', 'defense' => 'scale', 'evaluation' => 'star', 'archive' => 'archive'] as $key => $icon)
                        <li class="journey__step">
                            <span class="journey__icon"><x-icon :name="$icon" :size="22" /></span>
                            <span class="journey__number">{{ $loop->iteration }}</span>
                            <span class="journey__label">{{ __("home.journey.$key") }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
@endsection
