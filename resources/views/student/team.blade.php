@extends('layouts.app')

@section('title', __('student.my_team'))

@section('content')
    <x-page-header :title="__('student.my_team')" />

    @foreach ($invitations as $invitation)
        <x-alert type="info" icon="users">
            {{ __('student.invite_from', ['team' => $invitation->team->name]) }}
            @if ($invitation->expires_at)
                <span class="text-muted">
                    ({{ __('student.days_left', ['count' => (int) now()->diffInDays($invitation->expires_at, false)]) }})
                </span>
            @endif

            <form method="POST" action="{{ route('student.team.respond', $invitation) }}" class="alert__actions">
                @csrf
                <button name="answer" value="accept" class="btn btn-primary btn-sm">{{ __('student.accept') }}</button>
                <button name="answer" value="decline" class="btn btn-secondary btn-sm">{{ __('student.decline') }}</button>
            </form>
        </x-alert>
    @endforeach

    @if (! $team)
        <x-card>
            <x-slot:title>{{ __('student.create_team') }}</x-slot:title>

            <p class="text-muted">{{ __('student.no_team_text') }}</p>

            <form method="POST" action="{{ route('student.team.store') }}">
                @csrf
                <x-form-field name="name" :label="__('student.team_name')" required />
                <x-button type="submit" icon="users">{{ __('student.create_team') }}</x-button>
            </form>
        </x-card>
    @else
        <x-card>
            <x-slot:title>{{ $team->name }}</x-slot:title>
            <x-slot:actions>
                <x-badge color="info">{{ __('student.members_count', [
                    'count' => $team->members->where('status', 'accepted')->count(),
                    'max' => $maxSize,
                ]) }}</x-badge>
            </x-slot:actions>

            <ul class="member-list">
                @foreach ($team->members as $member)
                    <li class="member">
                        <span class="member__name">
                            {{ $member->user->name }}
                            @if ($member->user_id === $team->leader_id)
                                <x-badge color="info">{{ __('student.leader') }}</x-badge>
                            @endif
                        </span>

                        <x-badge :color="match ($member->status) {
                            'accepted' => 'success',
                            'pending' => 'warning',
                            default => 'neutral',
                        }">{{ __('student.status_' . $member->status) }}</x-badge>

                        @if ($member->status === 'pending' && $member->expires_at)
                            <small class="text-muted">
                                {{ __('student.days_left', ['count' => max(0, (int) now()->diffInDays($member->expires_at, false))]) }}
                            </small>
                        @endif

                        @if ($isLeader && $member->user_id !== $team->leader_id)
                            <form method="POST" action="{{ route('student.team.members.destroy', $member) }}"
                                  data-confirm="{{ __('student.remove_member_confirm') }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">{{ __('student.remove_member') }}</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>

        @if ($isLeader && $team->members->where('status', 'accepted')->count() < $maxSize)
            <x-card>
                <x-slot:title>{{ __('student.invitations') }}</x-slot:title>

                @if ($available->isEmpty())
                    <p class="text-muted">{{ __('student.no_classmates') }}</p>
                @else
                    <form method="POST" action="{{ route('student.team.invite') }}" class="inline-form">
                        @csrf
                        <label class="sr-only" for="invite-user">{{ __('student.invite') }}</label>
                        <select name="user_id" id="invite-user" required>
                            @foreach ($available as $classmate)
                                <option value="{{ $classmate->id }}">{{ $classmate->name }} — {{ $classmate->university_id }}</option>
                            @endforeach
                        </select>
                        <x-button type="submit" icon="users">{{ __('student.invite') }}</x-button>
                    </form>
                @endif
            </x-card>
        @endif
    @endif
@endsection
