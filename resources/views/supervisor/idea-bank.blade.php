@extends('layouts.app')

@section('title', __('supervisor.idea_bank'))

@section('content')
    <x-page-header :title="__('supervisor.idea_bank')" />

    <x-card>
        <x-slot:title>{{ __('supervisor.add_idea') }}</x-slot:title>

        <form method="POST" action="{{ route('supervisor.idea-bank.store') }}">
            @csrf
            <x-form-field name="title" :label="__('student.proposal_title')" required />
            <x-form-field name="description" type="textarea" rows="4" :label="__('student.proposal_description')" required />
            <x-tag-input name="technologies" :label="__('student.proposal_technologies')"
                         :hint="__('student.technologies_hint')" :values="old('technologies', [])" />
            <x-button type="submit" icon="lightbulb">{{ __('supervisor.add_idea') }}</x-button>
        </form>
    </x-card>

    @if ($ideas->isEmpty())
        <x-card>
            <x-empty-state icon="lightbulb" :title="__('supervisor.idea_bank_empty')" />
        </x-card>
    @else
        <div class="feature-grid">
            @foreach ($ideas as $idea)
                <x-card>
                    <x-slot:title>{{ $idea->title }}</x-slot:title>
                    <x-slot:actions>
                        <x-badge :color="$idea->status === 'reserved' ? 'warning' : 'success'">
                            {{ $idea->status === 'reserved'
                                ? __('supervisor.idea_reserved_by', ['team' => $idea->reservedByTeam?->name])
                                : __('student.available') }}
                        </x-badge>
                    </x-slot:actions>

                    <p>{{ $idea->description }}</p>

                    @if ($idea->technologies)
                        <div class="tag-list">
                            @foreach ((array) $idea->technologies as $technology)
                                <x-tag>{{ $technology }}</x-tag>
                            @endforeach
                        </div>
                    @endif

                    <x-slot:footer>
                        @if ($idea->status === 'reserved')
                            <small class="text-muted">{{ __('supervisor.cannot_delete_reserved') }}</small>
                        @else
                            <form method="POST" action="{{ route('supervisor.idea-bank.destroy', $idea) }}"
                                  data-confirm="{{ __('supervisor.idea_delete_confirm') }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">{{ __('student.task_deleted') }}</button>
                            </form>
                        @endif
                    </x-slot:footer>
                </x-card>
            @endforeach
        </div>
    @endif
@endsection
