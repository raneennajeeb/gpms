@extends('layouts.public')

@section('title', __('pages.guide_title'))

@section('content')
    <div class="container">
        <x-breadcrumb :items="[
            ['label' => __('nav.home'), 'url' => route('public.home')],
            ['label' => __('pages.guide_title')],
        ]" />

        <x-page-header :title="__('pages.guide_title')" :subtitle="__('pages.guide_subtitle')" />

        <div class="prose">
            <section>
                <h2>{{ __('pages.browse_title') }}</h2>
                <ol class="numbered">
                    @foreach (__('pages.browse_points') as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ol>
                <x-button :href="route('public.archive.index')" icon="archive">{{ __('archive.title') }}</x-button>
            </section>

            <section>
                <h2>{{ __('pages.login_title') }}</h2>
                <ol class="numbered">
                    @foreach (__('pages.login_points') as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ol>
                @guest
                    <x-button :href="route('login')" variant="secondary" icon="log-in">{{ __('nav.login') }}</x-button>
                @endguest
            </section>

            <section>
                <h2>{{ __('pages.privacy_title') }}</h2>
                <p>{{ __('pages.privacy_text') }}</p>
            </section>

            <section>
                <h2>{{ __('pages.help_title') }}</h2>
                <p>{{ __('pages.help_text') }}</p>
            </section>
        </div>
    </div>
@endsection
