@extends('layouts.public')

@section('title', __('archive.title'))

@section('content')
    <div class="container">
        <x-page-header :title="__('archive.title')" :subtitle="__('archive.subtitle')" />

        <section class="archive-section">
            <h2 class="archive-section__title">{{ __('archive.faculties') }}</h2>

            <div class="faculty-grid">
                @foreach ($faculties as $faculty)
                    <a href="{{ route('public.archive.faculty', $faculty) }}" class="faculty-card"
                       style="--faculty-color: {{ $faculty->color ?? '#1E4785' }}">
                        <span class="faculty-card__icon">
                            <x-icon :name="$faculty->icon ?? 'folder'" :size="28" />
                        </span>
                        <span class="faculty-card__name">{{ $faculty->localizedName() }}</span>
                        <span class="faculty-card__stats">
                            <span><strong>{{ $faculty->archive_entries_count }}</strong> {{ __('archive.projects') }}</span>
                            <span><strong>{{ $faculty->departments_count }}</strong> {{ __('archive.specializations') }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        @include('public.archive.partials.row', [
            'title' => __('archive.featured'),
            'entries' => $featured,
            'url' => null,
        ])

        @include('public.archive.partials.row', [
            'title' => __('archive.best_of_year', ['year' => $bestYear]),
            'entries' => $best,
            'url' => null,
        ])

        {{-- The competitions page comes in part 8 --}}
        @include('public.archive.partials.row', [
            'title' => __('archive.competitions_title'),
            'entries' => $competitions,
            'url' => Route::has('public.archive.competitions') ? route('public.archive.competitions') : null,
        ])
    </div>
@endsection
