@extends('layouts.app')

@section('title', __('supervisor.ideas_queue'))

@section('content')
    <x-page-header :title="__('supervisor.ideas_queue')" />

    @if ($projects->isEmpty())
        <x-card>
            <x-empty-state icon="lightbulb" :title="__('supervisor.queue_empty')" />
        </x-card>
    @else
        @foreach ($projects as $project)
            <x-card class="proposal">
                <x-slot:title>{{ $project->title }}</x-slot:title>
                <x-slot:actions>
                    <x-badge color="neutral">{{ __('student.categories.' . $project->category) }}</x-badge>
                </x-slot:actions>

                <p class="text-muted">
                    {{ __('archive.students') }}:
                    {{ $project->team->members->where('status', 'accepted')->pluck('user.name')->join('، ') }}
                    <span aria-hidden="true">•</span>
                    {{ __('nav.section') }} {{ $project->team->section->number }}
                </p>

                <p>{{ $project->description }}</p>

                @if ($project->keywords)
                    <div class="tag-list">
                        @foreach ($project->keywords as $keyword)
                            <x-tag>{{ $keyword }}</x-tag>
                        @endforeach
                    </div>
                @endif

                @if ($project->technologies->isNotEmpty())
                    <div class="tag-list">
                        @foreach ($project->technologies as $technology)
                            <x-tag>{{ $technology->name }}</x-tag>
                        @endforeach
                    </div>
                @endif

                {{-- The similarity figure and the matches the student already saw --}}
                @if ($project->similarity_score !== null)
                    <div class="similarity">
                        <div>
                            <span class="similarity__score {{ $project->similarity_score >= $threshold ? 'tone-danger' : 'tone-success' }}">
                                {{ (int) $project->similarity_score }}%
                            </span>
                            <p class="text-muted">{{ __('supervisor.similarity') }}</p>
                        </div>

                        <div class="similarity__text">
                            <h3>{{ __('supervisor.closest_matches') }}</h3>
                            <ul class="match-list">
                                @forelse ($project->similarityResults->sortByDesc('score')->take(5) as $match)
                                    <li>
                                        <strong>{{ (int) $match->score }}%</strong>
                                        <span>{{ $match->archiveEntry?->title }}</span>
                                        <small>{{ $match->archiveEntry?->year }}</small>
                                    </li>
                                @empty
                                    <li class="text-muted">—</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                @endif

                <x-slot:footer>
                    <div class="decision">
                        <form method="POST" action="{{ route('supervisor.projects.approve', $project) }}">
                            @csrf
                            <x-button type="submit" icon="check-circle">{{ __('supervisor.approve') }}</x-button>
                        </form>

                        {{-- Both of the other two need a written reason --}}
                        <form method="POST" action="{{ route('supervisor.projects.request-changes', $project) }}" class="decision__form">
                            @csrf
                            <x-form-field name="review_note" type="textarea" rows="2"
                                          :label="__('supervisor.reason')" :hint="__('supervisor.reason_hint')" />
                            <div class="decision__buttons">
                                <x-button type="submit" variant="secondary" icon="refresh">{{ __('supervisor.request_changes') }}</x-button>
                                <button type="submit" class="btn btn-danger"
                                        formaction="{{ route('supervisor.projects.reject', $project) }}">
                                    {{ __('supervisor.reject') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </x-slot:footer>
            </x-card>
        @endforeach
    @endif
@endsection
