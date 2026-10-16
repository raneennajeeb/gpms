@extends('layouts.app')

@section('title', $session ? __('supervisor.edit_session') : __('supervisor.book_session'))

@section('content')
    <x-page-header :title="$session ? __('supervisor.edit_session') : __('supervisor.book_session')" />

    @if ($pool->isEmpty())
        <x-alert type="warning" icon="alert-circle">{{ __('supervisor.no_committee_pool') }}</x-alert>
    @endif

    @php
        $chosen = $session?->committee->pluck('id')->all() ?? [];
        $chair = $session?->committee->firstWhere('pivot.role', 'chair')?->id;
        $slots = $session?->sessionProjects->keyBy('project_id') ?? collect();
    @endphp

    <form method="POST"
          action="{{ $session ? route('supervisor.defense-sessions.update', $session) : route('supervisor.defense-sessions.store') }}"
          id="session-form"
          data-availability="{{ route('supervisor.defense-sessions.availability') }}"
          data-session="{{ $session?->id }}"
          data-checking="{{ __('supervisor.checking') }}">
        @csrf
        @if ($session) @method('PUT') @endif

        <x-card>
            <x-slot:title>{{ __('supervisor.session_time') }}</x-slot:title>

            <div class="filters__row">
                <label class="filters__field">
                    <span>{{ __('supervisor.section') }}</span>
                    <select name="section_id" required>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected(old('section_id', $session?->section_id) == $section->id)>
                                {{ $section->number }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filters__field">
                    <span>{{ __('supervisor.session_date') }}</span>
                    <input type="date" name="session_date" required
                           value="{{ old('session_date', $session?->session_date?->toDateString()) }}">
                </label>

                <label class="filters__field">
                    <span>{{ __('supervisor.start_time') }}</span>
                    <input type="time" name="start_time" step="300" required
                           min="{{ $dayStart }}" max="{{ $dayEnd }}"
                           value="{{ old('start_time', $session ? substr($session->start_time, 0, 5) : '09:00') }}">
                </label>

                <label class="filters__field">
                    <span>{{ __('supervisor.end_time') }}</span>
                    <input type="time" name="end_time" step="300" required
                           min="{{ $dayStart }}" max="{{ $dayEnd }}"
                           value="{{ old('end_time', $session ? substr($session->end_time, 0, 5) : '11:00') }}">
                </label>

                <label class="filters__field">
                    <span>{{ __('supervisor.room') }}</span>
                    <select name="room_id" required>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id', $session?->room_id) == $room->id)>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            {{-- The page asks the server the same rules before anything is saved --}}
            <div class="availability">
                <x-button type="button" variant="secondary" icon="search" id="check-availability">
                    {{ __('supervisor.check_availability') }}
                </x-button>
                <span id="availability-message" class="text-muted"></span>
            </div>

            @error('session_date') <p class="form-field__error">{{ $message }}</p> @enderror
            @error('start_time') <p class="form-field__error">{{ $message }}</p> @enderror
            @error('end_time') <p class="form-field__error">{{ $message }}</p> @enderror
            @error('room_id') <p class="form-field__error">{{ $message }}</p> @enderror
        </x-card>

        <x-card>
            <x-slot:title>{{ __('supervisor.committee') }}</x-slot:title>

            <p class="text-muted">{{ __('supervisor.committee_hint') }}</p>

            <div class="pool">
                @foreach ($pool as $doctor)
                    <label class="pool__item">
                        <input type="checkbox" name="committee[]" value="{{ $doctor->id }}"
                               @checked(in_array($doctor->id, old('committee', $chosen)))>
                        <span>{{ $doctor->name }}</span>
                        <label class="pool__chair">
                            <input type="radio" name="chair" value="{{ $doctor->id }}"
                                   @checked(old('chair', $chair) == $doctor->id)>
                            <span>{{ __('supervisor.chair') }}</span>
                        </label>
                    </label>
                @endforeach
            </div>

            @error('committee') <p class="form-field__error">{{ $message }}</p> @enderror
            @error('chair') <p class="form-field__error">{{ $message }}</p> @enderror
        </x-card>

        <x-card>
            <x-slot:title>{{ __('supervisor.projects_in_session') }}</x-slot:title>

            <p class="text-muted">{{ __('supervisor.projects_hint') }}</p>

            @if ($available->isEmpty())
                <p class="text-muted">{{ __('supervisor.no_available_projects') }}</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{ __('student.project') }}</th>
                                <th>{{ __('archive.students') }}</th>
                                <th>{{ __('supervisor.position') }}</th>
                                <th>{{ __('supervisor.start_time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($available as $index => $project)
                                @php $slot = $slots->get($project->id); @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" data-slot-toggle
                                               @checked($slot !== null)
                                               aria-label="{{ $project->title }}">
                                        <input type="hidden" name="projects[{{ $index }}][project_id]" value="{{ $project->id }}"
                                               @disabled($slot === null)>
                                    </td>
                                    <td>{{ $project->title }}</td>
                                    <td class="text-muted">
                                        {{ $project->team->members->where('status', 'accepted')->pluck('user.name')->join('، ') }}
                                    </td>
                                    <td>
                                        <input type="number" name="projects[{{ $index }}][position]" min="1" max="20"
                                               value="{{ $slot?->position ?? $loop->iteration }}"
                                               @disabled($slot === null) style="width: 70px">
                                    </td>
                                    <td>
                                        <input type="time" name="projects[{{ $index }}][start_time]" step="300"
                                               value="{{ $slot ? substr($slot->start_time, 0, 5) : '' }}"
                                               @disabled($slot === null)>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @error('projects') <p class="form-field__error">{{ $message }}</p> @enderror
        </x-card>

        <x-button type="submit" icon="calendar">
            {{ $session ? __('supervisor.save') : __('supervisor.book_session') }}
        </x-button>
    </form>
@endsection
