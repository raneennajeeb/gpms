@extends('layouts.app')

@section('title', __('student.meetings'))

@section('content')
    <x-page-header :title="__('student.meetings')" />

    <div class="dash-grid">
        <div class="dash-grid__main">
            <x-card>
                <x-slot:title>{{ __('student.upcoming_meetings') }}</x-slot:title>

                @forelse ($upcoming as $meeting)
                    @include('student.partials.meeting', ['meeting' => $meeting])
                @empty
                    <p class="text-muted">{{ __('student.no_upcoming') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.past_meetings') }}</x-slot:title>

                @forelse ($past as $meeting)
                    @include('student.partials.meeting', ['meeting' => $meeting])
                @empty
                    <p class="text-muted">{{ __('student.no_past') }}</p>
                @endforelse
            </x-card>
        </div>

        <aside class="dash-grid__side">
            <x-card>
                <x-slot:title>{{ __('student.request_meeting') }}</x-slot:title>

                <form method="POST" action="{{ route('student.meetings.request') }}">
                    @csrf
                    <x-form-field name="date" type="date" :label="__('student.meeting_date')" required />
                    <x-form-field name="time" type="time" :label="__('student.meeting_time')" required />
                    <x-form-field name="reason" type="textarea" rows="3" :label="__('student.meeting_reason')" required />
                    <x-button type="submit" icon="calendar">{{ __('student.request_meeting') }}</x-button>
                </form>
            </x-card>
        </aside>
    </div>
@endsection
