@extends('layouts.app')

@section('title', __('student.my_section'))

@section('content')
    <x-page-header :title="__('student.my_section')"
                   :subtitle="__('student.section_subtitle', ['number' => auth()->user()->section?->number])" />

    <x-card>
        @if ($classmates->isEmpty())
            <x-empty-state icon="users" :title="__('student.no_classmates')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            {{-- This page is private to the section, so the ID may be shown --}}
                            <th>{{ __('student.university_id') }}</th>
                            <th>{{ __('student.in_team') }}</th>
                            <th>{{ __('student.their_project') }}</th>
                            @if ($isLeader)
                                <th>{{ __('common.actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classmates as $row)
                            <tr>
                                <td>{{ $row['user']->name }}</td>
                                <td class="num">{{ $row['user']->university_id }}</td>
                                <td>
                                    <x-badge :color="$row['in_team'] ? 'neutral' : 'success'">
                                        {{ $row['in_team'] ? __('student.in_team') : __('student.available') }}
                                    </x-badge>
                                </td>
                                {{-- Title only: a classmate's project details stay private --}}
                                <td class="text-muted">{{ $row['project_title'] ?? '—' }}</td>
                                @if ($isLeader)
                                    <td>
                                        @if (! $row['in_team'] && $team->members->count() < $maxSize)
                                            <form method="POST" action="{{ route('student.team.invite') }}">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $row['user']->id }}">
                                                <button class="btn btn-secondary btn-sm">{{ __('student.invite') }}</button>
                                            </form>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@endsection
