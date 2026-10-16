@extends('layouts.public')

@section('title', __('archive.competitions_title'))

@section('content')
    <div class="container">
        <x-breadcrumb :items="[
            ['label' => __('nav.home'), 'url' => route('public.home')],
            ['label' => __('nav.archive'), 'url' => route('public.archive.index')],
            ['label' => __('archive.competitions_title')],
        ]" />

        <x-page-header :title="__('archive.competitions_title')" :subtitle="__('archive.competitions_subtitle')" />

        <form method="GET" action="{{ route('public.archive.competitions') }}" class="filters">
            <div class="filters__row">
                <label class="filters__field">
                    <span>{{ __('archive.year') }}</span>
                    <select name="year">
                        <option value="">{{ __('archive.all') }}</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="filters__field">
                    <span>{{ __('archive.result') }}</span>
                    <select name="result">
                        <option value="">{{ __('archive.all') }}</option>
                        @foreach ($results as $result)
                            <option value="{{ $result }}" @selected(request('result') === $result)>{{ $result }}</option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="btn btn-secondary">{{ __('archive.apply') }}</button>

                @if (request()->hasAny(['year', 'result']))
                    <a href="{{ route('public.archive.competitions') }}" class="chips__clear">{{ __('archive.clear_filters') }}</a>
                @endif
            </div>
        </form>

        @if ($entries->isEmpty())
            <x-card>
                <x-empty-state icon="trophy" :title="__('archive.no_results_title')" :text="__('archive.no_results_text')" />
            </x-card>
        @else
            <div class="archive-grid">
                @foreach ($entries as $entry)
                    <x-archive-card :entry="$entry" />
                @endforeach
            </div>

            {{ $entries->links() }}
        @endif
    </div>
@endsection
