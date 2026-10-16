@extends('layouts.app')

@section('title', __('supervisor.meetings'))

@section('content')
    <x-page-header :title="__('supervisor.meetings')" />

    <div class="dash-grid">
        <div class="dash-grid__main">
            <x-card>
                <x-slot:title>{{ __('student.upcoming_meetings') }}</x-slot:title>

                @forelse ($upcoming as $meeting)
                    @include('supervisor.partials.meeting', ['meeting' => $meeting])
                @empty
                    <p class="text-muted">{{ __('student.no_upcoming') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.past_meetings') }}</x-slot:title>

                @forelse ($past as $meeting)
                    @include('supervisor.partials.meeting', ['meeting' => $meeting])
                @empty
                    <p class="text-muted">{{ __('student.no_past') }}</p>
                @endforelse
            </x-card>
        </div>

        <aside class="dash-grid__side">
            <x-card>
                <x-slot:title>{{ __('supervisor.new_meeting') }}</x-slot:title>

                <form method="POST" action="{{ route('supervisor.meetings.store') }}">
                    @csrf

                    <label class="form-field">
                        <span class="form-field__label">{{ __('student.project') }}</span>
                        <select name="project_id" required>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <x-form-field name="title" :label="__('supervisor.meeting_title')" required />
                    <x-form-field name="date" type="date" :label="__('student.meeting_date')" required />
                    <x-form-field name="time" type="time" :label="__('student.meeting_time')" required />

                    <label class="form-field">
                        <span class="form-field__label">{{ __('supervisor.room') }}</span>
                        <select name="room_id">
                            <option value="">—</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <x-form-field name="location_note" :label="__('student.location')" />

                    <x-button type="submit" icon="calendar">{{ __('supervisor.new_meeting') }}</x-button>
                </form>
            </x-card>
        </aside>
    </div>
@endsection
