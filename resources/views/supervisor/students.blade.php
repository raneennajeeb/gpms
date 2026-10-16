@extends('layouts.app')

@section('title', __('supervisor.students'))

@section('content')
    <x-page-header :title="__('supervisor.students')">
        <x-slot:actions>
            <x-button :href="route('supervisor.students.import')" icon="upload">{{ __('supervisor.add_students') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('supervisor.students') }}" class="filters">
        <div class="filters__row">
            <label class="filters__field">
                <span>{{ __('supervisor.search_students') }}</span>
                <input type="search" name="q" value="{{ request('q') }}">
            </label>

            <label class="filters__field">
                <span>{{ __('supervisor.section') }}</span>
                <select name="section">
                    <option value="">{{ __('supervisor.all_sections') }}</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected(request('section') == $section->id)>
                            {{ $section->number }}
                        </option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="btn btn-secondary">{{ __('archive.apply') }}</button>
        </div>
    </form>

    <x-card>
        @if ($rows->isEmpty())
            <x-empty-state icon="users" :title="__('supervisor.no_students')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('student.university_id') }}</th>
                            <th>{{ __('nav.section') }}</th>
                            <th>{{ __('student.my_team') }}</th>
                            <th>{{ __('student.project') }}</th>
                            <th>{{ __('supervisor.current_stage') }}</th>
                            <th>{{ __('student.status') }}</th>
                            <th>{{ __('supervisor.last_update') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr @if ($row['project']) class="row-link" data-href="{{ route('supervisor.projects.show', $row['project']) }}" @endif>
                                <td>{{ $row['student']->name }}</td>
                                <td class="num">{{ $row['student']->university_id }}</td>
                                <td>{{ $row['student']->section?->number ?? '—' }}</td>
                                <td class="text-muted">{{ $row['team']?->name ?? __('supervisor.no_team') }}</td>
                                <td>{{ $row['project']?->title ?? __('supervisor.no_project') }}</td>
                                <td class="text-muted">{{ $row['stage']?->name_ar ?? '—' }}</td>
                                <td>
                                    @if ($row['health'])
                                        <x-badge :color="$row['health']->color()">
                                            {{ $row['health']->level === 'good' ? __('supervisor.state_on_track') : __('supervisor.state_late') }}
                                        </x-badge>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $row['project']?->updated_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{ $students->links() }}
@endsection
