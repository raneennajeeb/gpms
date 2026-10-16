@extends('layouts.app')

@section('title', __('student.idea_bank'))

@section('content')
    <x-page-header :title="__('student.idea_bank')"
                   :subtitle="$supervisor ? __('student.idea_bank_subtitle', ['name' => $supervisor->name]) : null" />

    @if (! $team)
        <x-alert type="info" icon="info">{{ __('student.reserve_needs_team') }}</x-alert>
    @elseif (! $isLeader)
        <x-alert type="info" icon="info">{{ __('student.reserve_leader_only') }}</x-alert>
    @elseif (! $meetsMinimum)
        <x-alert type="info" icon="info">{{ __('student.reserve_min_size', ['count' => $minSize]) }}</x-alert>
    @endif

    @if ($ideas->isEmpty())
        <x-card>
            <x-empty-state icon="lightbulb" :title="__('student.idea_bank_empty')" />
        </x-card>
    @else
        <div class="feature-grid">
            @foreach ($ideas as $idea)
                <x-card>
                    <x-slot:title>{{ $idea->title }}</x-slot:title>

                    <p>{{ $idea->description }}</p>

                    @if ($idea->technologies)
                        <p class="text-muted">{{ __('student.suggested_technologies') }}</p>
                        <div class="tag-list">
                            @foreach ((array) $idea->technologies as $technology)
                                <x-tag>{{ $technology }}</x-tag>
                            @endforeach
                        </div>
                    @endif

                    <x-slot:footer>
                        @if ($isLeader && $meetsMinimum && ! $hasProject)
                            <form method="POST" action="{{ route('student.idea-bank.reserve', $idea) }}"
                                  data-confirm="{{ __('student.reserve_confirm') }}">
                                @csrf
                                <x-button type="submit" icon="check-circle">{{ __('student.reserve') }}</x-button>
                            </form>
                        @endif
                    </x-slot:footer>
                </x-card>
            @endforeach
        </div>
    @endif
@endsection
