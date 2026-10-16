@extends('layouts.app')

@section('title', __('student.proposal'))

@section('content')
    <x-page-header :title="__('student.proposal')" />

    @if (! $isLeader)
        <x-alert type="info" icon="info">{{ __('student.proposal_leader_only') }}</x-alert>
    @endif

    @if ($project && $project->review_note && in_array($project->status, ['changes_requested', 'rejected'], true))
        <x-alert type="warning" icon="alert-circle">
            <strong>{{ __('student.review_note') }}:</strong> {{ $project->review_note }}
        </x-alert>
    @endif

    @if ($readOnly)
        <x-alert type="info" icon="info">{{ __('student.proposal_readonly') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('student.proposal.store') }}" id="proposal-form"
          data-check-url="{{ route('student.proposal.check') }}"
          data-threshold="{{ $threshold }}"
          data-safe="{{ __('student.similarity_safe') }}"
          data-warning="{{ __('student.similarity_warning', ['threshold' => $threshold]) }}"
          data-failed="{{ __('errors.generic') }}">
        @csrf

        @php
            $idea = $reservedIdea;
            $value = fn (string $field, $fallback = '') => old($field, $project->{$field} ?? $fallback);
        @endphp

        <x-card>
            <x-form-field name="title" :label="__('student.proposal_title')"
                          :value="$value('title', $idea->title ?? '')"
                          :disabled="$readOnly || ! $isLeader" required />

            <x-form-field name="description" type="textarea" rows="8"
                          :label="__('student.proposal_description')"
                          :value="$value('description', $idea->description ?? '')"
                          :disabled="$readOnly || ! $isLeader" required />

            <label class="form-field">
                <span class="form-field__label">{{ __('student.proposal_category') }}</span>
                <select name="category" @disabled($readOnly || ! $isLeader) required>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($value('category') === $category)>
                            {{ __('student.categories.' . $category) }}
                        </option>
                    @endforeach
                </select>
            </label>
            @error('category') <p class="form-field__error">{{ $message }}</p> @enderror

            {{-- Tag inputs: the hidden field carries the real value to the server --}}
            <x-tag-input name="keywords" :label="__('student.proposal_keywords')"
                         :hint="__('student.keywords_hint')"
                         :values="old('keywords', $project?->keywords ?? [])"
                         :disabled="$readOnly || ! $isLeader" />

            <x-tag-input name="technologies" :label="__('student.proposal_technologies')"
                         :hint="__('student.technologies_hint')"
                         :values="old('technologies', $project?->technologies->pluck('name')->all() ?? ($idea->technologies ?? []))"
                         :suggestions="$technologies"
                         :disabled="$readOnly || ! $isLeader" />
        </x-card>

        @if (! $readOnly && $isLeader)
            <div class="proposal-actions">
                <x-button type="button" variant="secondary" icon="search" id="check-similarity">
                    {{ __('student.check_similarity') }}
                </x-button>

                <x-button type="submit" icon="upload">{{ __('student.submit_proposal') }}</x-button>
            </div>
        @endif
    </form>

    {{-- Filled by the similarity check, and before the form is finally sent --}}
    <x-card id="similarity-card" hidden>
        <x-slot:title>{{ __('student.similarity_result') }}</x-slot:title>

        <div class="similarity">
            <div id="similarity-ring"></div>

            <div class="similarity__text">
                <p id="similarity-message"></p>
                <h3>{{ __('student.top_matches') }}</h3>
                <ul class="match-list" id="similarity-matches"></ul>
            </div>
        </div>
    </x-card>

    @if ($project && $project->similarity_score !== null && $readOnly)
        <x-card>
            <x-slot:title>{{ __('student.similarity_result') }}</x-slot:title>
            <x-progress-ring :value="(int) $project->similarity_score"
                             :color="$project->similarity_score >= $threshold ? 'danger' : 'success'"
                             :label="__('student.similarity_result')" />
        </x-card>
    @endif
@endsection
