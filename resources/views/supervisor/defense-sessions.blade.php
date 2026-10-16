@extends('layouts.app')

@section('title', __('supervisor.defense_sessions'))

@section('content')
    <x-page-header :title="__('supervisor.defense_sessions')">
        <x-slot:actions>
            <x-button :href="route('supervisor.defense-sessions.create')" icon="calendar">
                {{ __('supervisor.book_session') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($sessions->isEmpty())
        <x-card>
            <x-empty-state icon="scale" :title="__('supervisor.sessions_empty')" />
        </x-card>
    @else
        @foreach ($sessions as $session)
            <x-card>
                <x-slot:title>
                    {{ __('nav.section') }} {{ $session->section->number }} —
                    {{ $session->session_date->translatedFormat('l j F Y') }}
                </x-slot:title>
                <x-slot:actions>
                    <x-badge :color="match ($session->status) {
                        'scheduled' => 'success',
                        'cancelled' => 'danger',
                        default => 'neutral',
                    }">{{ __('supervisor.session_status_' . $session->status) }}</x-badge>
                </x-slot:actions>

                <dl class="meta-list meta-list--inline">
                    <div>
                        <dt>{{ __('supervisor.session_time') }}</dt>
                        <dd class="num">{{ substr($session->start_time, 0, 5) }} — {{ substr($session->end_time, 0, 5) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('supervisor.room') }}</dt>
                        <dd>{{ $session->room->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('supervisor.committee') }}</dt>
                        <dd>
                            @foreach ($session->committee as $member)
                                {{ $member->name }}@if ($member->pivot->role === 'chair') ({{ __('supervisor.chair') }})@endif{{ ! $loop->last ? '، ' : '' }}
                            @endforeach
                        </dd>
                    </div>
                </dl>

                @if ($session->sessionProjects->isNotEmpty())
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('supervisor.position') }}</th>
                                    <th>{{ __('student.project') }}</th>
                                    <th>{{ __('archive.students') }}</th>
                                    <th>{{ __('supervisor.session_time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($session->sessionProjects as $slot)
                                    <tr>
                                        <td class="num">{{ $slot->position }}</td>
                                        <td>{{ $slot->project->title }}</td>
                                        <td class="text-muted">
                                            {{ $slot->project->team->members->where('status', 'accepted')->pluck('user.name')->join('، ') }}
                                        </td>
                                        <td class="num">{{ substr($slot->start_time, 0, 5) }} — {{ substr($slot->end_time, 0, 5) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <x-slot:footer>
                    @if ($session->status !== 'cancelled')
                        <div class="decision">
                            <x-button :href="route('supervisor.defense-sessions.edit', $session)" variant="secondary" icon="refresh">
                                {{ __('supervisor.edit_session') }}
                            </x-button>

                            <details class="versions">
                                <summary>{{ __('supervisor.cancel_session') }}</summary>
                                <form method="POST" action="{{ route('supervisor.defense-sessions.destroy', $session) }}"
                                      class="inline-form" data-confirm="{{ __('supervisor.cancel_session_confirm') }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="text" name="reason" required minlength="10" maxlength="500"
                                           placeholder="{{ __('supervisor.cancel_reason') }}">
                                    <button class="btn btn-danger btn-sm">{{ __('supervisor.cancel_session') }}</button>
                                </form>
                            </details>
                        </div>
                    @endif
                </x-slot:footer>
            </x-card>
        @endforeach
    @endif
@endsection
